<?php
/**
 * Check id of the payment
 *
 * $Horde: incubator/Horde_Payment/process/index.php,v 1.25 2009/07/08 18:29:09 slusarz Exp $
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

// Try to get order data
$id = Horde_Util::getFormData('authorization_id', @$_SESSION['payment']['process_id']);
$module = Horde_Util::getFormData('authorization_module');
$payment_data = $payment_driver->getAuthorization($id, $module);
if ($payment_data instanceof PEAR_Error) {
    $notification->push($payment_data);
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Check statues
if ($payment_data['status'] == Horde_Payment::VOID) {
    $notification->push(sprintf(_("The order id was voided by the seller."), $payment_data['authorization_id']), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Store order id in session
if (!isset($_SESSION['payment']) ||
    !isset($_SESSION['payment']['process_id']) ||
    $payment_data['authorization_id'] != $_SESSION['payment']['process_id']) {
    $_SESSION['payment']['process_id'] = $payment_data['authorization_id'];
}

// Get methods
$methods = Horde_Payment::getMethods($payment_data['module_name'], $payment_data['amount']);
if ($methods instanceof PEAR_Error) {
    $notification->push($methods);
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Get method labels
foreach ($methods as $method => $name) {
    $method_class = Horde_Payment_Method::singleton($method);
    if ($method_class instanceof PEAR_Error) {
        continue;
    }
    $methods[$method] = $method_class->radioLabel();
}

// If ony one avaiable method exits. Redirect the user to it.
if (sizeof($methods) == 1) {
    $_SESSION['payment']['process_step'] = 1;
    $payment_driver->updateAuthorization($payment_data['authorization_id'], array('method' => key($methods)));
    require PAYMENT_BASE . '/process/step1.php';
    exit;
}

// Prepare form
$title = _("Select payment method");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Payment_Form_Data($vars, $title, $payment_data);

$form->setButtons(array(_("Proceed"), _("Revoke")));
$v = $form->addVariable(_("Payment method"), 'method', 'radio', true, false, false, array($methods));
$v->setDefault($prefs->getValue('default_payment'));

if (Horde_Auth::isAuthenticated() && $conf['prefs']['driver'] != 'session') {
    $form->addVariable(_("Set this payment as default?"), 'setdefault', 'boolean', false);
}

if ($form->validate()) {

    $form->getInfo(null, $info);

    // Redirect to revoke confirmation
    if (Horde_Util::getFormData('submitbutton') == _("Revoke")) {
        header('Location: ' . Horde::applicationUrl('process/revoke.php'));
        exit;
    }

    // Save default method
    if (!empty($info['setdefault'])) {
        $prefs->setValue('default_payment', $info['method']);
    }

    // Start payment process
    $_SESSION['payment']['process_step'] = 1;
    $payment_driver->updateAuthorization($payment_data['authorization_id'], array('method' => $info['method']));

    require PAYMENT_BASE . '/process/step1.php';
    exit;
}

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/process/header.inc';

$renderer = new Horde_Form_Renderer(array('varrenderer_driver' => array('Horde_Payment', 'payment_methods')));
$form->renderActive($renderer, null, Horde::selfUrl(), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
