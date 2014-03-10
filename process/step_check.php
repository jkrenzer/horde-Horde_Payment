<?php
/**
 * Payment check - Setup objects and checks for errors
 *
 * $Horde: incubator/Horde_Payment/process/step_check.php,v 1.17 2009/06/10 17:33:21 slusarz Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */

// Includes
require_once PAYMENT_BASE . '/lib/Form/Data.php';
require_once PAYMENT_BASE . '/lib/Method.php';

// Check id
if (!isset($_SESSION['payment']['process_id'])) {
    $notification->push(_("Process id was lost."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Check step
if (!isset($_SESSION['payment']['process_step'])) {
    $notification->push(_("We lost your position."), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('process/index.php'));
    exit;
}

// Try to get authorization data
$payment_data = $payment_driver->getAuthorization($_SESSION['payment']['process_id']);
if ($payment_data instanceof PEAR_Error) {
    $notification->push($payment_data);
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Try to create the payment method
$method = Horde_Payment_Method::singleton($payment_data['method']);
if ($method instanceof PEAR_Error) {
    $notification->push($method);
    exit;
}
