<?php
/**
 * Payment step 2 - Actually process the payment
 *
 * $Horde: incubator/Horde_Payment/process/step2.php,v 1.14 2009/06/10 17:33:21 slusarz Exp $
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

// Do basic checks and load data
require_once PAYMENT_BASE . '/process/step_check.php';

// Redirect the user to remote valiadation
if ($method->hasRemoteValidation()) {
    $remote_url = $method->getValidationUrl($payment_data);
    if ($remote_url instanceof PEAR_Error) {
        $notification->push($remote_url);
        $methods = Horde_Payment::getMethods($payment_data['module_name'], $payment_data['amount']);
        if (count($methods) == 1) {
            header('Location: ' . Horde::applicationUrl('process/error.php'));
        } else {
            header('Location: ' . Horde::applicationUrl('process/index.php'));
        }
    } else {
        header('Location: ' . $remote_url);
    }
    exit;
}

// Process the method localy
$new_status = $method->process($payment_data);
if ($new_status instanceof PEAR_Error) {
    $notification->push($new_status);
    header('Location: ' . Horde::applicationUrl('process/step1.php'));
    exit;
}

// Update the paymet status.
$result = Horde_Payment::changeStatus($new_status, $_SESSION['payment']['process_id']);
if ($result instanceof PEAR_Error) {
    $notification->push($result);
    header('Location: ' . Horde::applicationUrl('process/step1.php'));
    exit;
}

// When changing status the user can be redirected to the application specific page
// If not we produce an general status page

$title = sprintf(_("Order status was chagned to: %s"), Horde_Payment::getStatusName($new_status));
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Payment_Form_Data($vars, $title, $payment_data);
$form->addVariable(_("Payment method"), 'method', 'text', false, true);
$form->setButtons(array(sprintf(_("Return to %s"), $registry->get('name', $payment_data['module_name']))));
$vars->set('method', $method->get('name'));

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/process/header.inc';

$form->renderActive(null, null, 'step3.php', 'post');

if ($method->hasAfterProcessMessage()) {
    echo $method->afterProcessMessage($payment_data, $new_status);
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
