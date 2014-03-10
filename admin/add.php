<?php
/**
 * Add a testing authorization
 *
 * $Horde: incubator/Horde_Payment/admin/add.php,v 1.16 2009/07/08 18:29:08 slusarz Exp $
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

$title =_("Add a testing authorization");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title);
$form->setButtons(_("Submit"));

$apps = Horde_Payment::getPermissions();
if (empty($apps)) {
    $notification->push("No application linked to the payment system.");
}

$form->addVariable(_("Module"), 'module_name', 'enum', true, false, false, array($apps, false));
$form->addVariable(_("Module ID"), 'module_id', 'int', true);
$form->addVariable(_("Amount"), 'amount', 'number', true);
$form->addVariable(_("Process"), 'process', 'boolean', false);

if ($form->validate()) {
    $form->getInfo(null, $info);
    $authorizationID = $payment_driver->addAuthorization($info['module_name'], $info['module_id'], $info['amount']);
    if ($authorizationID instanceof PEAR_Error) {
        $notification->push($authorizationID);
    } else {
        $notification->push(sprintf(_("Testing authorization addad with ID %s"), $authorizationID), 'horde.success');
    }
    if ($info['process']) {
        header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('process/index.php'), 'authorization_id', $authorizationID));
    } else {
        header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    }
    exit;
}

$vars->set('module_name', $registry->getApp());
$vars->set('module_id', date('His'));
$vars->set('amount', 155.67);
$vars->set('process', true);

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, Horde::applicationUrl('admin/add.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
