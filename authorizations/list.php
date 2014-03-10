<?php
/**
 * List authorizations requests
 *
 * $Horde: incubator/Horde_Payment/authorizations/list.php,v 1.29 2009/12/10 17:42:31 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */

require_once dirname(__FILE__) . '/../lib/base.php';

// Get access permissions
$apps = Horde_Payment::getPermissions();
if (empty($apps)) {
    $notification->push("No application linked to the payment system.");
    if (Horde_Auth::isAdmin()) {
        header('Location: ' . Horde::applicationUrl('admin/matrix.php'));
        exit;
    } else {
        Horde_Auth::authenticateFailure('Horde_Payment');
    }
}

// Links
$void_url = Horde::applicationUrl('authorizations/void.php');
$delete_url = Horde::applicationUrl('authorizations/delete.php');
$details_url = Horde::applicationUrl('authorizations/details.php');
$img_dir = $registry->getImageDir('horde');

// Prepare form
$title = _("Authorizations");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Search authorizations"));
$form->setButtons(_("Search"));

$form->addVariable(_("Module"), 'module_name', 'enum', false, false, false, array($apps, true));
$form->addVariable(_("Module ID"), 'module_id', 'int', false);
$form->addVariable(_("Order ID"), 'authorization_id', 'text', false, false, false, array('', 20, 13));
$form->addVariable(_("User"), 'user_uid', 'text', false);
$form->addVariable(_("Amount"), 'amount', 'number', false);

$statuses = array(
    Horde_Payment::SUCCESSFUL => Horde_Payment::getStatusName(Horde_Payment::SUCCESSFUL),
    Horde_Payment::PENDING => Horde_Payment::getStatusName(Horde_Payment::PENDING),
    Horde_Payment::REVOKED => Horde_Payment::getStatusName(Horde_Payment::REVOKED),
    Horde_Payment::VOID => Horde_Payment::getStatusName(Horde_Payment::VOID)
);

$v = $form->addVariable(_("Status"), 'status', 'enum', false, false, false, array($statuses, true));
$v->setDefault(Horde_Payment::SUCCESSFUL);

// Get methods
$methods = Horde_Payment::getMethods();
$form->addVariable(_("Method"), 'method', 'enum', false, false, false, array($methods, true));
$form->addVariable(_("Date from"), 'date_from', 'monthdayyear', false);
$form->addVariable(_("Date to"), 'date_to', 'monthdayyear', false);

// Get data
if ($form->validate()) {
    $form->getInfo(null, $criteria);
    $prefs->setValue('default_app', $criteria['module_name']);

    $list = $payment_driver->listAuthorizations($criteria);
    if ($list instanceof PEAR_Error) {
        $notification->push($list);
        $list = array();
    } elseif (empty($list)) {
        $notification->push(_("There are no authorizations for the selected criteria."), 'horde.warning');
    }

    $pager = new Horde_Ui_Pager('page',
                                Horde_Variables::getDefaultVariables(),
                                array('num' => sizeof($list),
                                      'url' => 'authorizations/list.php',
                                      'page_count' => 10,
                                      'perpage' => 20));
} elseif (!$form->isSubmitted()) {
    $vars->set('module_name', $prefs->getValue('default_app'));
}

Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('popup.js', 'horde', true);

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/menu.inc';

if (!empty($list)) {
    $delete = Horde_Auth::isAdmin('Horde_Payment:admin');
    require PAYMENT_TEMPLATES . '/authorizations/list/header.inc';
    foreach ($list as $row) {
        $void = Horde_Payment::hasPermission($row['module_name'], Horde_Perms::DELETE);
        $read = Horde_Payment::hasPermission($row['module_name'], Horde_Perms::READ);
        $provides = $registry->get('provides', $row['module_name']);
        $referrence_name = $registry->hasMethod($provides . '/getName');
        require PAYMENT_TEMPLATES . '/authorizations/list/row.inc';
    }
    require PAYMENT_TEMPLATES . '/authorizations/list/footer.inc';
}

$form->renderActive(null, null, Horde::applicationUrl('authorizations/list.php'), 'get');

require $registry->get('templates', 'horde') . '/common-footer.inc';
