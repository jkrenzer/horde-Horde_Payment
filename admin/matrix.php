<?php
/**
 * Link methods to applications
 *
 * $Horde: incubator/Horde_Payment/admin/matrix.php,v 1.15 2009/07/08 18:29:08 slusarz Exp $
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

$title =_("Link applications to payment methods");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);

// get Current links
foreach ($registry->listApps(array('notoolbar', 'active', 'hidden'), true) as $app) {
    $apps[$app] = $GLOBALS['registry']->get('name', $app) . ' (' . $app . ')';
}
asort($apps);

$links = array();
if (empty($apps)) {
    $notification->push(_("There are no application to be linked to the payment system"), 'horde.error');
} else {
    foreach (array_keys($apps) as $app) {
        $methods = $payment_driver->getMethods($app);
        if (!empty($methods)) {
            foreach ($methods as $method) {
                $links[$app][$method] = 'on';
            }
        }
    }
}

$form->addVariable($title, 'matrix', 'matrix', true, false, false,
                   array(Horde_Payment::getMethods(), $apps, $links));

if ($form->validate()) {
    $form->getInfo(null, $info);
    $result = $payment_driver->saveMethodLink($info['matrix']);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Links updated."), 'horde.success');
        header('Location: ' . Horde::applicationUrl('admin/matrix.php'));
        exit;
    }
}

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, Horde::applicationUrl('admin/matrix.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
