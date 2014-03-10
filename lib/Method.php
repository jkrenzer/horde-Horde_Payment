<?php
/**
 * Payment Method abstract class
 *
 * $Horde: incubator/Horde_Payment/lib/Method.php,v 1.18 2009/01/28 12:23:20 duck Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Method {

    /**
     * Instances
     */
    static private $instances = array();

    /**
     * Hash containing method parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Load configuration
     */
    public function __construct()
    {
        $config_dir = PAYMENT_BASE . '/lib/Methods/' . substr(get_class($this), 21) . '/';
        require $config_dir . 'conf.php';
        if (!empty($GLOBALS['conf']['vhosts'])) {
            $config_file = 'conf-' . $GLOBALS['conf']['server']['name'] . '.php';
            if (file_exists($config_dir . $config_file)) {
                include $config_dir . $config_file;
            }
        }

        $this->_params = $conf;
    }

    /**
     * Tell us if the method needs an add additional data to be supplied
     */
    public function hasLocalForm()
    {
        return false;
    }

    /**
     * Tell us if the processing is done on remote server.
     */
    public function hasRemoteValidation()
    {
        return false;
    }

    /**
     * If true afterProcessMessage() method will be called
     * to outpout additonal content after paymant status is changed
     */
    public function hasAfterProcessMessage()
    {
        return false;
    }

    /**
     * True if the method support voiding authorization
     */
    public function hasVoid()
    {
        return false;
    }

    /**
     * True if the method support partial voiding
     */
    public function hasPartialVoid()
    {
        return false;
    }

    /**
     * True if the invoicer is the payment processor
     *
     * @param array $data Array of payment data
     */
    public function hasInvoicing()
    {
        return false;
    }

    /**
     * The url to redirect the user to.
     *
     * @param array $data Array of payment data
     */
    public function getValidationUrl($data)
    {
        return '';
    }

    /**
     * Returns the payment method attribute
     *
     * @param string $key   Parameter key
     * @param string $group Parameter group
     */
    public function get($key, $group = 'general')
    {
        if (isset($this->_params[$group][$key])) {
            return $this->_params[$group][$key];
        } else {
            return null;
        }
    }

    /**
     * Call the payment method processing mechanism.
     *
     * @return payment status
     */
    public function process()
    {
        return PEAR::raiseError(_("Not supported."));
    }

    /**
     * Returns the label of the radio bootom in the method selection menu
     */
    public function radioLabel()
    {
        return '<strong>' . $this->get('name') . "</strong><br />\n" . $this->get('desc');
    }

    /**
     * Returns session parameter
     */
    public function sessionParam($key, $group = 'billing')
    {
        if (!isset($_SESSION['payment']['process_params'][$group]) ||
            !isset($_SESSION['payment']['process_params'][$group][$key])) {
            return null;
        } else {
            return $_SESSION['payment']['process_params'][$group][$key];
        }
    }

    /**
     * Attempts to return a concrete Payment_Method instance based on $driver.
     *
     * @param string $driver  The type of the concrete Payment_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @return Payment_Driver  The newly created concrete Payment_Driver
     *                          instance, or false on an error.
     */
    public static function factory($driver)
    {
        $class = 'Horde_Payment_Method_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Methods/' . $driver . '/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class();
        } else {
            return PEAR::raiseError(sprintf(_("Payment Method \"%s\" do not exists."), $driver));
        }
    }

    /**
     * Attempts to return a reference to a concrete Payment_Method instance.
     * It will only create a new instance if no Payment_Method instance
     * of selected driver currently exists.
     *
     * This method must be invoked as: $var = Horde_Payment_Method::singleton($driver)
     *
     * @param string $driver  The type of the concrete Payment_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @return object Payment_Method  The concrete Payment_Method reference,
     *                                or false on an error.
     */
    public static function singleton($driver)
    {
        if (!array_key_exists($driver, self::$instances)) {
            self::$instances[$driver] = self::factory($driver);
        }

        return self::$instances[$driver];
    }
}