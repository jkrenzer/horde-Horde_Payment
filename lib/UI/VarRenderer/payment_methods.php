<?php
/**
 * Horde_UI_VarRenderer_datetime_xhtml class, extends Horde_Ui_VarRenderer_Html.
 *
 * $Horde: incubator/Horde_Payment/lib/UI/VarRenderer/payment_methods.php,v 1.6 2009/12/10 17:42:31 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_UI_VarRenderer_payment_methods extends Horde_Ui_VarRenderer_Html {

    function _radioButtons($name, $values, $checkedValue = null, $actions = '')
    {
        $i = 0;
        $result = '<table>';
        foreach ($values as $value => $display) {
            $checked = (!is_null($checkedValue) && $value == $checkedValue) ? ' checked="checked"' : '';
            $result .= '<tr valign="top">' . "\n";
            $result .= sprintf('<td><input id="%s%s" type="radio" class="checkbox" name="%s" value="%s"%s%s /></td><td><label for="%s%s">%s</label></td>',
                               @htmlspecialchars($name, ENT_QUOTES, $this->_charset),
                               $i,
                               @htmlspecialchars($name, ENT_QUOTES, $this->_charset),
                               @htmlspecialchars($value, ENT_QUOTES, $this->_charset),
                               $checked,
                               $actions,
                               @htmlspecialchars($name, ENT_QUOTES, $this->_charset),
                               $i,
                               $display);
            $result .= '</tr>' . "\n";
            $i++;
        }

        $result .= '</table>';
        return $result;
    }

}
