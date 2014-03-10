<?php
/**
 * Create proforma bill example
 *
 * $Horde: incubator/Horde_Payment/templates/methods/Proforma/bill.php,v 1.14 2009/01/28 11:34:55 duck Exp $
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */

// Check if we the data expired
define('PAYMENT_BASE', dirname(__FILE__) . '/../../../');
require_once PAYMENT_BASE . '/lib/base.php';

$id = $_SESSION['payment']['process_id'];
if (!$id) {
    die('Expired');
}

// Try to laod the image
$img = imagecreatefromjpeg($registry->get('templates') . '/methods/Proforma/proforma.jpg');
if (empty($img)) {
    die('Cannot load image');
}

// Check payment data
$payment_data = $payment_driver->getAuthorization($id);
if ($payment_data instanceof PEAR_Error) {
    die($payment_data->getMessage());
}

// Check attributes
$payment_attributes = $payment_driver->getAuthorizationAttributes($id);
if ($payment_attributes instanceof PEAR_Error) {
    die($payment_attributes->getMessage() . ': ' . $payment_attributes->getDebugInfo());
}

// Try to create the payment method
$method = &Horde_Payment_Method::singleton($payment_data['method']);
if ($method instanceof PEAR_Error) {
    die($method->getMessage());
    exit;
}

// general settings
$color = imagecolorclosest($img, 0, 0, 0);
$font = loadFont($method->get('font', 'proforma'));

// name
$text = formatString($payment_attributes['first_name'] . ' ' . $payment_attributes['last_name']);
writeString(30, 45, $text);

// adress
$text = formatString($payment_attributes['address']);
writeString(30, 75, $text);

// city
$text = formatString($payment_attributes['zip'] . ' ' . $payment_attributes['town']);
writeString(30, 100, $text);

// intend
$name = $id;
$provides = $registry->get('provides', $payment_data['module_name']);
if ($registry->hasMethod($provides . '/getName')) {
    $name = $registry->call($provides . '/getName', array($payment_data['module_id']));
}
$text = formatString(_("Proforma") . ' ' .  str_replace('/', '-', $name));
writeString(30, 128, $text);

// Reference
$name = formatString($name);
writeString(310, 200, $name);
writeString(270, 200, '00');

// Value
$text = Horde_Payment::formatPrice($payment_data['amount'], false);
$x = 460 - ImageFontWidth($font) * strlen($text);
writeString($x, 100, $text);

// Company name
$name = formatString($method->get('company', 'proforma'));
writeString(35, 170, $name);

// Company address
$name = formatString($method->get('address', 'proforma'));
writeString(35, 200, $name);

// Company town
$name = formatString($method->get('town', 'proforma'));
writeString(35, 227, $name);

// Company account
$name = formatString($method->get('account', 'proforma'));
writeString(350, 170, $name);

// Output image
header("Content-type: image/jpeg");
imagejpeg($img);

/**
 * Fromat string
 */
function formatString($string)
{
    $string = str_replace('/', '-', $string);
    $string = strtoupper($string);

    return $string;
}

/**
 * Write string
 */
function writeString($x, $y, $string)
{
    global $img, $font, $color;

    imagestring($img, $font, $x, $y, $string, $color);
}

/**
 * Load font
 */
function loadFont($fontname)
{
    if (intval($fontname)>0) {
        return $fontname;
    }

    if (!is_readable($fontname)) {
        return 5;
    }

    return imageloadfont($fontname);
}