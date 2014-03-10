<?php
/**
 * $Horde: incubator/Horde_Payment/admin/config.php,v 1.18 2009/07/09 08:18:07 slusarz Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */

define('PAYMENT_BASE', dirname(__FILE__) . '/../');
require_once PAYMENT_BASE . '/lib/base.php';
require_once PAYMENT_BASE . '/lib/Method.php';

/**
 * Extend config class to handle methods
 */
class Method_Config extends Horde_Config {

    function Method_Config()
    {
        parent::Horde_Config($GLOBALS['registry']->getApp());
    }

    function readXMLConfig($custom_conf = null)
    {
        if (is_null($this->_xmlConfigTree) || $custom_conf) {
            global $registry;
            $path = PAYMENT_BASE . '/lib/Methods/' . Horde_Util::getFormData('method');

            if ($custom_conf) {
                $this->_currentConfig = $custom_conf;
            } else {
                /* Fetch the current conf.php contents. */
                @eval($this->getPHPConfig());
                if (isset($conf)) {
                    $this->_currentConfig = $conf;
                }
            }

            /* Load the DOM object. */
            $doc = Horde_DOM_Document::factory(array('filename' => $path . '/conf.xml'));

            /* Check if there is a CVS version tag and store it. */
            $node = $doc->first_child();
            while (!empty($node)) {
                if ($node->type == XML_COMMENT_NODE) {
                    if (preg_match('/\$.*?conf\.xml,v .*? .*\$/', $node->node_value(), $match)) {
                        $this->_versionTag = $match[0] . "\n";
                        break;
                    }
                }
                $node = $node->next_sibling();
            }

            /* Parse the config file. */
            $this->_xmlConfigTree = array();
            $root = $doc->root();
            if ($root->has_child_nodes()) {
                $this->_parseLevel($this->_xmlConfigTree, $root->child_nodes(), '');
            }
        }

        return $this->_xmlConfigTree;
    }

}

/**
 * Extend config class to handle methods
 */
class MethodConfigForm extends ConfigForm {

    function MethodConfigForm(&$vars, $method)
    {
        parent::Horde_Form($vars);

        $this->_xmlConfig = &new Method_Config($GLOBALS['registry']->getApp());
        $this->_vars = &$vars;
        $config = $this->_xmlConfig->readXMLConfig();
        $this->addHidden('', 'method', 'text', true);
        $this->_buildVariables($config);
    }
}

if (!Horde_Auth::isAdmin()) {
    Horde::fatal('Forbidden.', __FILE__, __LINE__);
}

if (!Util::extensionExists('domxml') && !Util::extensionExists('dom')) {
    Horde::fatal('You need the domxml or dom PHP extension to use the configuration tool.', __FILE__, __LINE__);
}

// Try to create the payment method
$method = Horde_Util::getFormData('method');
$method_class = Horde_Payment_Method::singleton($method);
if ($method_class instanceof PEAR_Error) {
    $notification->push($method_class);
    header('Location: ' . Horde::applicationUrl('admin/setup.php'));
    exit;
}

$methodname = $method_class->get('name');
$title = sprintf(_("%s Setup"), $methodname);
$path = PAYMENT_BASE . '/lib/Methods/' . $method;

$vars = Horde_Variables::getDefaultVariables();
$form = new MethodConfigForm($vars, $method);
$form->_xmlConfig->readXMLConfig($path . '/conf.xml');

$form->setButtons(sprintf(_("Generate %s Configuration"), $methodname));

if (file_exists($path . 'conf.bak.php')) {
    $form->methodendButtons(_("Revert Configuration"));
}

$php = '';
if (Horde_Util::getFormData('submitbutton') == _("Revert Configuration")) {
    if (@copy($configfile . '.bak', $configfile . '/conf.php')) {
        $notification->push(_("Successfully reverted configuration."), 'horde.success');
        @unlink($path . '/conf.bak.php');
    } else {
        $notification->push(_("Could not revert configuration."), 'horde.error');
    }
} elseif ($form->validate()) {
    $config = new Method_Config();
    $php = $config->generatePHPConfig($vars);
    if (file_exists($path . '/conf.php')) {
        if (@copy($path . '/conf.php', $path . '/conf.bak.php')) {
            $notification->push(sprintf(_("Successfully saved the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.success');
        } else {
            $notification->push(sprintf(_("Could not save the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.warning');
        }
    }
    if ($fp = @fopen($path . '/conf.php', 'w')) {
        /* Can write, so output to file. */
        fwrite($fp, Horde_String::convertCharset($php, Horde_Nls::getCharset(), 'iso-8859-1'));
        fclose($fp);
        $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($path . '/conf.php')), 'horde.success');
        $registry->clearCache();
        header('Location: ' . Horde::methodlicationUrl('admin/setup/index.php', true));
        exit;
    } else {
        /* Cannot write. */
        $notification->push(sprintf(_("Could not save the configuration file %s. You can either use one of the options to save the code back on %s or copy manually the code below to %s."), Horde_Util::realPath($path . '/conf.php'), Horde::link(Horde::url('index.php') . '#update', _("Setup")) . _("Setup") . '</a>', Horde_Util::realPath($path . '/conf.php')), 'horde.warning', array('content.raw'));
    }
} elseif ($form->isSubmitted()) {
    $notification->push(_("There was an error in the configuration form. Perhaps you left out a required field."), 'horde.error');
}

/* Render the configuration form. */
$renderer = $form->getRenderer();
$renderer->setAttrColumnWidth('50%');
$form = Horde_Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'config.php', 'post');

/* Set up the template. */
$template = new Horde_Template();
$template->set('php', htmlspecialchars($php), true);
/* Create the link for the diff popup only if stored in session. */
$diff_link = '';

$template->set('diff_popup', $diff_link, true);
$template->set('form', $form);
$template->set('title', $title);
$template->setOption('gettext', true);

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/menu.inc';

echo $template->fetch(PAYMENT_TEMPLATES . '/admin/config.html');

require $registry->get('templates', 'horde') . '/common-footer.inc';
