<?php
/**
 * Horde_Payment Base Class.
 *
 * $Horde: incubator/Horde_Payment/lib/Payment.php,v 1.39 2009/12/01 12:52:46 jan Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment {

    const PENDING = 2;
    const REVOKED = 4;
    const SUCCESSFUL = 6;
    const VOID = 8;

    /**
     * Retruns status name
     *
     * @param intiger $status Status to change the payment to
     */
    static public function getStatusName($status)
    {
        switch ($status) {
        case self::PENDING:
            return _("Pending");

        case self::REVOKED:
            return _("Revoked");

        case self::SUCCESSFUL:
            return _("Successful");

        case self::VOID:
            return _("Void");

        default:
            return _("Error");
        }
    }

    /**
     * Update the status and call back the selling api
     *
     * @param intiger $status Status to change the payment to
     * @param string  $id     Paymant id to the one to change the status to
     *
     * @return mixed True if success, PEAR_Error on failure
     */
    static public function changeStatus($status, $id)
    {
        global $registry, $payment_driver;

        // Pending is the default, noting to do
        if ($status == self::PENDING) {
            return true;
        }

        // Get payment data
        $payment_data = $payment_driver->getAuthorization($id);
        if ($payment_data instanceof PEAR_Error) {
            return $payment_data;
        }

        // Update DB
        $result = $payment_driver->updateAuthorization($id, array('status' => $status));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $provides = $registry->get('provides', $payment_data['module_name']);
        if ($registry->hasMethod($provides . '/authorizationResponse')) {
            $method = Horde_Payment_Method::singleton($payment_data['method']);
            if ($method instanceof PEAR_Error) {
                return $method;
            }
            $params = array('status_id' => $status,
                            'status_name' => Horde_Payment::getStatusName($status),
                            'has_invoicing' => $method->hasInvoicing());
            return $registry->call($provides . '/authorizationResponse', array($payment_data['module_id'], $params));
        }

        return true;
    }

    /**
     * Void an authorization
     *
     * TODO Partial voiding
     *
     * @param string  $id     Paymant id to the one to change the status to
     * @param float   $amount Amount to void. Default all.
     *
     * @return mixed True if success, PEAR_Error on failure
     */
    static public function void($id, $amount = 0)
    {
        // Get payment data
        $payment_data = $GLOBALS['payment_driver']->getAuthorization($id);
        if ($payment_data instanceof PEAR_Error) {
            return $payment_data;
        }

        // Check permission
        if (!Horde_Payment::hasPermission($payment_data['module_name'], Horde_Perms::DELETE)) {
            return PEAR::raiseError(_("You don't have the permission void a request."));
        }

        // Already voided, noting to do
        if ($payment_data['status'] == self::VOID) {
            return PEAR::raiseError(_("Authorization already voided."));
        }

        // Try to create the payment method
        if (!empty($payment_data['method'])) {
            $method = Horde_Payment_Method::singleton($payment_data['method']);
            if ($method instanceof PEAR_Error) {
                return $method;
                exit;
            }

            // Void the authorization
            if ($method->hasVoid()) {
                $result = $method->void($id);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
            }
        }

        // Update DB
        $result = $GLOBALS['payment_driver']->updateAuthorization($id, array('status' => self::VOID));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Callback the selling app
        $provides = $GLOBALS['registry']->get('provides', $payment_data['module_name']);
        if ($GLOBALS['registry']->hasMethod($provides . '/voidResponse')) {
            return $GLOBALS['registry']->call($provides . '/voidResponse', array($payment_data['module_id']));
        }

        return true;
    }

    /**
     * Retruns formatted price
     *
     * @param float   $price    The price value to format
     * @param boolean $symbol   Whether to add the currency symbol.
     *
     * @return string  The locale formatted price string.
     */
    static public function formatPrice($price, $symbol = true)
    {
        require_once HORDE_BASE . '/incubator/Horde_Currencies/Currencies.php';

        $currency = Horde_CurrenciesMapper::getDefaultCurrency();

        return Horde_Currencies::formatPrice($price, $currency, $symbol);
    }

    /**
     * Fomates time according to user prefs
     *
     * @param int     $timestamp Timestamp
     * @param boolean $time      Flase if display only date part
     *
     * @return string $date fromatted date
     */
    static public function formatDate($timestamp, $time = true)
    {
        if (intval($timestamp)<1000000000) {
            $timestamp = strtotime($timestamp);
        }

        $formatted = strftime($GLOBALS['prefs']->getValue('date_format'), $timestamp);
        if ($time) {
            $formatted .= ' ' . date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $timestamp);
        }

        return $formatted;
    }

    /**
     * Returns a new or the current CAPTCHA string.
     *
     * @param boolean $new  If true, a new CAPTCHA is created and returned.
     *                      The current, to-be-confirmed string otherwise.
     *
     * @return string  A CAPTCHA string.
     */
    static public function getCAPTCHA($new = false)
    {
        if ($new || empty($_SESSION['payment']['CAPTCHA'])) {
            $_SESSION['payment']['CAPTCHA'] = '';
            for ($i = 0; $i < 5; $i++) {
                $_SESSION['payment']['CAPTCHA'] .= chr(rand(65, 90));
            }
        }
        return $_SESSION['payment']['CAPTCHA'];
    }

    /**
     * Returns the list of the avaiable payment methods
     *
     * @param string $app    Application to chek
     * @param float  $amount Amount to process
     */
    public function getMethods($app = null, $amount = 0)
    {
        if ($app) {
            $methods = Horde_Array::valuesToKeys($GLOBALS['payment_driver']->getMethods($app));
        } else {
            $ignore_files = array('.', '..', 'CVS');
            foreach (scandir(PAYMENT_BASE . '/lib/Methods/') as $file) {
                if (!in_array($file, $ignore_files)) {
                    $methods[$file] = $file;
                }
            }
        }

        if (empty($methods)) {
            return PEAR::raiseError(_("No methods available."));
        }

        $available = array();
        foreach ($methods as $method) {
            $method_class = Horde_Payment_Method::singleton($method);
            if ($method_class instanceof PEAR_Error) {
                // There was an error creating the method
                if (Horde_Auth::isAdmin('Horde_Payment:admin')) {
                    return $method_class;
                }
                continue;
            } elseif (!$method_class->get('active')) {
                // The module is deactivated
                continue;
            } elseif ($amount) {
                if ($method_class->get('minimum') && $amount < $method_class->get('minimum') ) {
                    // The order amount in lower than the accepted value for this method
                    continue;
                } elseif ($method_class->get('maximum') && $amount > $method_class->get('maximum') ) {
                    // The order amount in higher than the accepted value for this method
                    continue;
                }
            }

            $available[$method] = $method_class->get('name');
        }

        return $available;
    }

    /**
     * Returns true if the user has the permission
     *
     * @param string $app  applicatio to chek
     * @param int    $perm permission to check
     */
    static public function hasPermission($app, $perm = Horde_Perms::SHOW)
    {
        return (Horde_Auth::isAdmin() ||
                $GLOBALS['perms']->hasPermission('Horde_Payment:apps', Horde_Auth::getAuth(), $perm) ||
                $GLOBALS['perms']->hasPermission('Horde_Payment:apps:', Horde_Auth::getAuth(), $perm));
    }

    /**
     * Get apps access list for the current user
     *
     * @param int     $perm permission to check
     */
    static public function getPermissions($perm = Horde_Perms::SHOW)
    {
        static $apps_list;

        if (!empty($apps_list[$perm])) {
            return $apps_list[$perm];
        }

        foreach ($GLOBALS['registry']->listAPIs() as $api) {
            $app = $GLOBALS['registry']->hasInterface($api);
            if ($GLOBALS['registry']->hasMethod($api . '/authorizationResponse') &&
                Horde_Payment::hasPermission($app, $perm)) {
                $apps_list[$perm][$app] = $GLOBALS['registry']->get('name', $app) . ' (' . $app . ')';
            }
        }

        if (isset($apps_list[$perm])) {
            asort($apps_list[$perm]);
        }

        return $apps_list[$perm];
    }

    /**
     * Build Horde_Payment's list of menu items.
     */
    static public function getMenu($returnType = 'object')
    {
        $img_dir = $GLOBALS['registry']->getImageDir('horde');
        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::applicationUrl('process/enter.php'), _("Process"), 'tick.png', $img_dir);

        $apps = Horde_Payment::getPermissions(Horde_Perms::SHOW);
        if (!empty($apps)) {
            $menu->add(Horde::applicationUrl('authorizations/list.php'), _("Authorizations"), 'show_panel.png', $img_dir);
        }

        if (Horde_Auth::isAdmin()) {
            $menu->add(Horde::applicationUrl('admin/matrix.php'), _("Matrix"), 'layout.png', $img_dir);
            $menu->add(Horde::applicationUrl('admin/add.php'), _("Add testing"), 'add_perm.png', $img_dir);
            $menu->add(Horde::applicationUrl('admin/setup.php'), _("Setup methods"), 'administration.png', $img_dir);
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
