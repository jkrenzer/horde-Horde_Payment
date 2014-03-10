<?php
/**
 * Directly enter the order if we know the order ID
 *
 * $Horde: incubator/Horde_Payment/process/enter.php,v 1.14 2009/06/10 17:33:21 slusarz Exp $
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

$title = _("Enter order ID");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title, 'enterid');
$form->setButtons(_("Proceed"));

$form->addVariable(_("Order ID"), 'authorization_id', 'text', true, false, false, array('', 20, 13));

if (!empty($conf['payment']['captcha'])) {
    $form->addVariable(_("Spam protection"), 'captcha', 'figlet', true, null, null,
                      array(Horde_Payment::getCAPTCHA(!$form->isSubmitted()), $conf['payment']['figlet_font']));
}

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $payment_data = $payment_driver->getAuthorization($info['authorization_id']);
    if ($payment_data instanceof PEAR_Error) {
        $notification->push($payment_data);
    } else {
        header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('process/index.php'), 'authorization_id', $info['authorization_id'], false));
        exit;
    }
}

require PAYMENT_TEMPLATES . '/common-header.inc';
require PAYMENT_TEMPLATES . '/process/header.inc';

$form->renderActive(null, null, Horde::applicationUrl('process/enter.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
