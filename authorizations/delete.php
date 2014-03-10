<?php
/**
 * Delete authorization
 *
 * $Horde: incubator/Horde_Payment/authorizations/delete.php,v 1.4 2009/07/08 18:29:08 slusarz Exp $
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
require_once PAYMENT_BASE . '/lib/Form/Data.php';

// This is only for admins
if (!Horde_Auth::isAdmin('Horde_Payment:admin')) {
    $notification->push(_("Only administrators can delete authorization requests."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    exit;
}

// Try to get order data
$id = Horde_Util::getFormData('authorization_id');
$payment_data = $payment_driver->getAuthorization($id);
if ($payment_data instanceof PEAR_Error) {
    $notification->push($payment_data);
    header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    exit;
}

// Perms
if (!Horde_Auth::isAdmin('Horde_Payment:admin')) {
    $msg = sprintf(_("You don't have permission to delete authorizations in %s."), $registry->get('name', $payment_data['module_name']));
    $notification->push($msg, 'horde.warning');
    header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    exit;
}

// Prepare form
$title =_("Are you sure to delete this authorization?");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Payment_Form_Data($vars, $title, $payment_data);
$form->setButtons(array(_("Delete"), _("Cancel")));
$form->addHidden('', 'authorization_id', 'text', $id);

if ($form->validate()) {

    // Cancel
    if (Horde_Util::getFormData('submitbutton') == _("Cancel")) {
        $notification->push(_("Authorization was not deleted."), 'horde.success');
        header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
        exit;
    }

    // Delete
    $name = $registry->get('name', $payment_data['module_name']);
    $result = $GLOBALS['payment_driver']->deleteAuthorization($id);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } elseif ($result) {
        $notification->push(sprintf(_("Authorization \"%s\" from module \"%s\" was successfully deleted."), $id, $name), 'horde.success');
    } else {
        $notification->push(sprintf(_("Authorization \"%s\" from module \"%s\" was not deleted."), $id, $name), 'horde.warning');
    }

    header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('authorizations/list.php'), 'module_name', $payment_data['module_name']));
    exit;
}

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, Horde::applicationUrl('authorizations/delete.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
