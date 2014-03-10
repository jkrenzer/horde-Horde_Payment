<?php
/**
 * Payment_Driver:: defines an API for implementing storage backends for Payment.
 *
 * $Horde: incubator/Horde_Payment/lib/Driver.php,v 1.10 2010/02/01 10:32:07 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Driver {

    /**
     * Attempts to return a concrete Payment_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete Payment_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Payment_Driver  The newly created concrete Payment_Driver
     *                          instance, or false on an error.
     */
    public static function factory($driver = 'sql', $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if ($params === null) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Horde_Payment_Driver_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Driver/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

    /**
     * Get methods linked to app
     *
     * @param string  $app Aplication to check
     *
     * @return array of avaiable methods for this app
     */
    public function getMethods($app)
    {
        static $methods;

        if (!$methods) {
            $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
            $methods = $cache->get('Horde_Payment_methods', $GLOBALS['conf']['cache']['default_lifetime']);
            if ($methods) {
                $methods = unserialize($methods);
            } else {
                $methods = $this->_getMethods();
                if ($methods instanceof PEAR_Error) {
                    return $methods;
                }
                $cache->set('Horde_Payment_methods', serialize($methods));
            }
        }

        if (array_key_exists($app, $methods)) {
            return $methods[$app];
        } else {
            return array();
        }
    }

    /**
     * Save module method links
     *
     * @param array  $link    Matrix of payment methods
     */
    public function saveMethodLink($link)
    {
        $result = $this->_saveMethodLink($link);
        if ($result instanceof PEAR_Error) {
            return $result;
        }
        $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
        return $cache->expire('Horde_Payment_methods');
    }
}