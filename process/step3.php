<?php
/**
 * Payment step 3 - Clean up and redirect to the selling module
 *
 * $Horde: incubator/Horde_Payment/process/step3.php,v 1.10 2009/01/28 11:12:56 duck Exp $
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

// Try to get authorization data
$payment_data = $payment_driver->getAuthorization($_SESSION['payment']['process_id']);
if ($payment_data instanceof PEAR_Error) {
    $notification->push($payment_data);
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Unset local
unset($_SESSION['payment']);

// Try to get the return url
$provides = $registry->get('provides', $payment_data['module_name']);
if ($registry->hasMethod($provides . '/authorizationReturn')) {
    $url = $registry->call($provides . '/authorizationReturn', array($payment_data['module_id']));
} else {
    $url = $registry->get('webroot', $payment_data['module_name']);
}

header('Location: ' . $url);
exit;
