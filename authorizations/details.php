<?php
/**
 * Authorizations details
 *
 * $Horde: incubator/Horde_Payment/authorizations/details.php,v 1.13 2009/12/01 12:52:46 jan Exp $
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

// Try to get order data
$id = Horde_Util::getFormData('authorization_id');
$payment_data = $payment_driver->getAuthorization($id);
if ($payment_data instanceof PEAR_Error) {
    $notification->push($payment_data);
    header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    exit;
}

// Perms
if (!Horde_Payment::hasPermission($payment_data['module_name'], Horde_Perms::READ)) {
    $msg = sprintf(_("You don't have permission to read attributes in %s."), $registry->get('name', $payment_data['module_name']));
    $notification->push($msg, 'horde.warning');
    header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    exit;
}

// Try to get attributes
$payment_attributes = $payment_driver->getAuthorizationAttributes($id);
if ($payment_attributes instanceof PEAR_Error) {
    $notification->push($payment_attributes);
    header('Location: ' . Horde::applicationUrl('authorizations/list.php'));
    exit;
}

$title = sprintf(_("Details for %s"), $id);
Horde::addScriptFile('stripe.js', 'horde', true);
require PAYMENT_TEMPLATES . '/common-header.inc';

require PAYMENT_TEMPLATES . '/authorizations/details/header.inc';
foreach ($payment_attributes as $key => $value) {
    require PAYMENT_TEMPLATES . '/authorizations/details/row.inc';
}
require PAYMENT_TEMPLATES . '/authorizations/details/footer.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';