<?php
/**
 * $Horde: incubator/Horde_Payment/index.php,v 1.7 2009/01/06 17:50:34 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */

define('PAYMENT_BASE', dirname(__FILE__));
$payment_configured = (is_readable(PAYMENT_BASE . '/config/conf.php') &&
                       is_readable(PAYMENT_BASE . '/config/prefs.php'));

if (!$payment_configured) {
    require PAYMENT_BASE . '/../../lib/Test.php';
    Horde_Test::configFilesMissing('Payment', PAYMENT_BASE,
                                   array('conf.php', 'prefs.php'));
}

require PAYMENT_BASE . '/authorizations/list.php';
