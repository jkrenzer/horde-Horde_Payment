<?php
/**
 * Link methods to applications
 *
 * $Horde: incubator/Horde_Payment/admin/setup.php,v 1.12 2009/07/08 18:29:08 slusarz Exp $
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

if (!Horde_Auth::isAdmin()) {
    Horde::fatal('Forbidden.', __FILE__, __LINE__);
}

/**
 * Returns the CVS version for a given file.
 */
function _getVersion($file)
{
    $data = @file_get_contents($file);
    if (preg_match('/\$.*?conf\.xml,v (.*?) .*\$/', $data, $match)) {
        return $match[1];
    } else {
        return false;
    }
}

$title =_("Setup payment methods");
$methods = array();
$conf_url = Horde::applicationUrl('admin/config.php');
$i = -1;

/* Set up some icons. */
$success = Horde::img('alerts/success.png', '', '', $registry->getImageDir('horde'));
$warning = Horde::img('alerts/warning.png', '', '', $registry->getImageDir('horde'));
$error = Horde::img('alerts/error.png', '', '', $registry->getImageDir('horde'));

foreach (Horde_Payment::getMethods() as $method => $name) {
    $path = PAYMENT_BASE . '/lib/Methods/' . $method;
    if (!file_exists($path . '/conf.xml')) {
        continue;
    }

    $i++;
    if (is_readable($path . '/version.php')) {
        require $path . '/version.php';
        $version_constant = 'HORDE_PAYMENT_METHOD_' . Horde_String::upper($method) . '_VERSION';
        if (defined($version_constant)) {
            $methods[$i]['version'] = constant($version_constant);
        }
    }

    $conf_link = Horde_Util::addParameter($conf_url, 'method', $method);
    $conf_link = Horde::link($conf_link, sprintf(_("Configure %s"), $method));

    $methods[$i]['sort'] = $name;
    $methods[$i]['name'] = $conf_link . $name . '</a>';

    if (!file_exists($path . '/conf.php')) {
        /* No conf.php exists. */
        $methods[$i]['conf'] = $conf_link . $error . '</a>';
        $methods[$i]['status'] = _("Missing configuration. You must generate it before using this method.");
    } else {
        /* A conf.php exists, get the xml version. */
        if (($xml_ver = _getVersion($path . '/conf.xml')) === false) {
            $methods[$i]['conf'] = $conf_link . $warning . '</a>';
            $methods[$i]['status'] = _("No version found in original configuration. Regenerate configuration.");
            continue;
        }
        /* Get the generated php version. */
        if (($php_ver = _getVersion($path . '/conf.php')) === false) {
            /* No version found in generated php, suggest regenarating
             * just in case. */
            $methods[$i]['conf'] = $conf_link . $warning . '</a>';
            $methods[$i]['status'] = _("No version found in your configuration. Regenerate configuration.");
            continue;
        }

        if ($xml_ver != $php_ver) {
            /* Versions are not the same, configuration is out of date. */
            $methods[$i]['conf'] = $conf_link . $error . '</a>';
            $methods[$i]['status'] = _("Configuration is out of date.");
            continue;
        } else {
            /* Configuration is ok. */
            $methods[$i]['conf'] = $conf_link . $success . '</a>';
            $methods[$i]['status'] = _("Method is ready.");
        }
    }
}

/* Set up the template. */
$template = new Horde_Template();
$template->set('methods', $methods);
$template->setOption('gettext', true);

$title = sprintf(_("%s Setup"), $registry->get('name', 'horde'));
Horde::addScriptFile('stripe.js', 'horde', true);

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/menu.inc';

echo $template->fetch(PAYMENT_TEMPLATES . '/admin/setup.html');

require $registry->get('templates', 'horde') . '/common-footer.inc';
