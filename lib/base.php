<?php
/**
 * Payment base application file.
 *
 * $Horde: incubator/Horde_Payment/lib/base.php,v 1.16 2009/09/29 13:38:28 duck Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../../..');
}

// Load the Horde Framework core, and set up inclusion paths and autoloading.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('Horde_Payment');
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('Horde_Payment', $e);
}
$conf = &$GLOBALS['conf'];
define('PAYMENT_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Horde_Notification::singleton();
$notification->attach('status');

// Define the base file path of Payment.
if (!defined('PAYMENT_BASE')) {
    define('PAYMENT_BASE', dirname(__FILE__) . '/..');
}

// Payment base library
require_once PAYMENT_BASE . '/lib/Payment.php';
require_once PAYMENT_BASE . '/lib/Driver.php';
require_once PAYMENT_BASE . '/lib/Method.php';
$GLOBALS['payment_driver'] = Horde_Payment_Driver::factory();

// Start output compression.
Horde::compressOutput();
