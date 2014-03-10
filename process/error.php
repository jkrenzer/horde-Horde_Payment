<?php
/**
 * Notify error
 *
 * $Horde: incubator/Horde_Payment/process/error.php,v 1.9 2009/06/10 17:33:21 slusarz Exp $
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
require_once PAYMENT_BASE . '/lib/Form/Data.php';

// Try to get authorization data
$payment_data = $payment_driver->getAuthorization($_SESSION['payment']['process_id']);
if ($payment_data instanceof PEAR_Error) {
    $notification->push($payment_data);
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Prepare form
$title = _("Please try again...");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Payment_Form_Data($vars, $title, $payment_data);
$form->setButtons(array(_("Proceed"), _("Change payment method")));

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/process/header.inc';

$form->renderActive(null, null, Horde::applicationUrl('process/index.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
