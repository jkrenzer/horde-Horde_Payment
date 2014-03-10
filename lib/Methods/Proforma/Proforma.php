<?php
/**
 * Proforma bill
 *
 * $Horde: incubator/Horde_Payment/lib/Methods/Proforma/Proforma.php,v 1.14 2009/07/09 08:18:07 slusarz Exp $
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Method_Proforma extends Horde_Payment_Method {

    /**
     * Creator
     */
    public function __construct()
    {
        parent::__construct();

        if (!isset($this->_params['general']['name'])) {
            $this->_params['general']['name'] = _("Proforma Bill");
        }

        if (!isset($this->_params['general']['desc'])) {
            $this->_params['general']['desc'] = _("We will issue you a proforma bill which can be paid on you e-bank or on your nearest post or bank office.");
        }
    }

    /**
     * Call the payment method processing mechanism.
     */
    public function process()
    {
        return true;
    }

    /**
     * True if we need additional data to be supplied
     */
    public function hasLocalForm()
    {
        $attr = array();
        foreach ($this->_getFields() as $id => $name) {
            if ($this->sessionParam($id, 'billing') === null) {
                return true;
            }
            $attr[$id] = $this->sessionParam($id, 'billing');
        }

        // All data exists, so just save attributes
        $result = $GLOBALS['payment_driver']->addAuthorizationAttributes($_SESSION['payment']['process_id'], $attr);
        if ($result instanceof PEAR_Error) {
            return true;
        }

        return false;
    }

    /**
     * Fill the payment form with variables
     */
    public function fillFormVariables(&$form)
    {
        foreach ($this->_getFields() as $id => $name) {
            if ($id == 'country') {
                $desc = _("This payment method is available only for customers or deliveries to the listing countries. If your country is not listed, please use another payment method.");
                $v = &$form->addVariable($name, $id, 'enum', true, false, null, array($this->_getCountries(), true));
            } else {
                $v = &$form->addVariable($name, $id, 'text', true);
            }
            if (($default = $this->sessionParam($id, 'billing')) !== null) {
                $v->setDefault($this->sessionParam($id, 'billing'));
            }
        }
    }

    /**
     * Retrun fields needed by proforma bill
     */
    private function _getFields()
    {
        $fields = array(
            'first_name' => _("First name: "),
            'last_name' => _("Last name: "),
            'address' => _("Address: "),
            'zip' => _("Postal code: "),
            'town' => _("Town: "),
            'state' => _("state: "),
            'country' => _("Country: ")
        );

        if (!$this->_params['proforma']['state']) {
            unset($fields['state']);
        }

        if (!$this->_params['proforma']['country']) {
            unset($fields['country']);
        }

        return $fields;
    }

    /**
     * Get avaiable countries list
     */
    private function _getCountries()
    {
        if (empty($this->_params['general']['countries'])) {
            return Horde_Nls::getCountryISO();
        }

        $nls_countries = Horde_Nls::getCountryISO();
        $countries = array();
        foreach ($this->_params['general']['countries'] as $country) {
            $countries[$country] = $nls_countries[$country];
        }

        return $countries;
    }

    /**
     * If true afterProcessMessage() method will be called
     * to outpout additonal content after paymant status is changed
     */
    public function hasAfterProcessMessage()
    {
        return true;
    }

    /**
     * Constract after process content
     */
    public function afterProcessMessage($payment_data, $status)
    {
        require PAYMENT_TEMPLATES . '/methods/Proforma/bill.inc';
    }

}
