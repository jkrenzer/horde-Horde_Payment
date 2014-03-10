<?php
/**
 * Cash on delivery
 *
 * $Horde: incubator/Horde_Payment/lib/Methods/COD/COD.php,v 1.7 2009/01/28 12:23:21 duck Exp $
 *
 * Copyright 2006-2009 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Method_COD extends Horde_Payment_Method {

    /**
     * Creator
     */
    public function __construct()
    {
        parent::__construct();

        if (!isset($this->_params['general']['name'])) {
            $this->_params['general']['name'] = _("Cash on delivery");
        }

        if (!isset($this->_params['general']['desc'])) {
            $this->_params['general']['desc'] = _("You will pay our goods in cash immediately when you will receive it to the postman.");
        }
    }

    /**
     * Call the payment method processing mechanism.
     */
    function process()
    {
        return true;
    }
}
