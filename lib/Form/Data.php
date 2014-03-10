<?php
/**
 * Form displaying transaction details
 *
 * $Horde: incubator/Horde_Payment/lib/Form/Data.php,v 1.3 2009/01/28 11:34:52 duck Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Form_Data extends Horde_Form {

    /**
     * Creator
     */
    public function __construct($vars, $title, $payment_data)
    {
        $this->_name = 'payment_form_data';
        $this->_vars = $vars;
        $this->_title = $title;

        $this->_fill_form($payment_data);
    }

    /**
     * Fill up form with payment data
     */
    private function _fill_form($payment_data)
    {
        global $registry;

        $this->addVariable(_("Order ID"), 'authorization_id', 'text', false, true);
        $this->_vars->set('authorization_id', $payment_data['authorization_id']);

        $this->addVariable(_("Module"), 'module_name', 'text', false, true);
        $this->_vars->set('module_name', $registry->get('name', $payment_data['module_name']));

        $provides = $registry->get('provides', $payment_data['module_name']);
        if ($registry->hasMethod($provides . '/getName')) {
            $name = $registry->call($provides . '/getName', array($payment_data['module_id']));
            if (!($name instanceof PEAR_Error) && $name) {
                $this->addVariable(_("Reference"), 'module_id', 'text', false, true);
                $this->_vars->set('module_id', $name);
            }
        }

        $this->addVariable(_("Amount"), 'amount', 'text', false, true);
        $this->_vars->set('amount', Horde_Payment::formatPrice($payment_data['amount']));
    }

}