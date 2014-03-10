<?php
/**
 * Allow user to revoke the payment
 *
 * $Horde: incubator/Horde_Payment/process/revoke.php,v 1.17 2009/06/10 17:33:21 slusarz Exp $
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

if (empty($_SESSION['payment']['process_id'])) {
    header('Location: ' . Horde::applicationUrl('process/enter.php'));
    exit;
}

// Try to get order data
$payment_data = $payment_driver->getAuthorization($_SESSION['payment']['process_id']);

// Prepare form
$title =_("Are you sure to revoke this order?");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Payment_Form_Data($vars, $title, $payment_data);
$form->setButtons(array(_("Revoke"), _("Cancel")));

if ($conf['payment']['captcha']) {
    $form->addVariable(_("Spam protection"), 'captcha', 'figlet', true, null, null,
                      array(Horde_Payment::getCAPTCHA(!$form->isSubmitted()), $conf['payment']['figlet_font']));
}

if ($form->validate($vars)) {

    // Cancel
    if (Horde_Util::getFormData('submitbutton') == _("Cancel")) {
        $notification->push(_("Order was not revoked."), 'horde.success');
        header('Location: ' . Horde::applicationUrl('process/index.php'));
        exit;
    }

    // Revoke
    $result = Horde_Payment::changeStatus(Horde_Payment::REVOKED, $_SESSION['payment']['process_id']);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
        header('Location: ' . Horde::applicationUrl('process/revoke.php'));
        exit;
    }

    if (is_string($result)) {
        unset($_SESSION['payment']['process_id']);
        header('Location: ' . $result);
        exit;
    }

    $notification->push(_("Order successly revoked."), 'horde.success');
}

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/process/header.inc';

if (!isset($result)) {
    $form->renderActive(null, null, Horde::applicationUrl('process/revoke.php'), 'post');
} else {
    require PAYMENT_TEMPLATES . '/process/revoked.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
