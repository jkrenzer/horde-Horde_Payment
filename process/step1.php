<?php
/**
 * Payment step 1 - Supply additional data
 *
 * $Horde: incubator/Horde_Payment/process/step1.php,v 1.18 2009/06/10 17:33:21 slusarz Exp $
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

// We need to supply a local from? Otherwise we have nothing to do here
if (!$method->hasLocalForm()) {
    $_SESSION['payment']['process_step'] = 2;
    require PAYMENT_BASE . '/process/step2.php';
    exit;
}

// Prepare form
$title = sprintf(_("Please enter addtional data for: %s"), $method->get('name'));
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Payment_Form_Data($vars, $title, $payment_data);
$form->setButtons(array(_("Proceed"), _("Change payment method")));

// Fill form with method specific variables
$desc = $method->get('desc');
if ($desc) {
    $form->addVariable($method->get('desc'), 'description', 'description', false);
}
$method->fillFormVariables($form);

// Redirect to index page to select different method
if (Horde_Util::getFormData('submitbutton') == _("Change payment method")) {
    header('Location: ' . Horde::applicationUrl('process/index.php'));
    exit;
}

// Validate and go to step2
if (Horde_Util::getPost('method') === null && $form->validate()) {
    $form->getInfo(null, $info);
    if ($payment_driver->hasAuthorizationAttributes($_SESSION['payment']['process_id'])) {
        $result = $payment_driver->updateAuthorizationAttributes($_SESSION['payment']['process_id'], $info);
    } else {
        $result = $payment_driver->addAuthorizationAttributes($_SESSION['payment']['process_id'], $info);
    }
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getMessage() . ':' . $result->getDebugInfo(), 'horde.error');
    } else {
        require PAYMENT_BASE . '/process/step2.php';
        exit;
    }
}

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/process/header.inc';

$form->renderActive(null, null, 'step1.php', 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
