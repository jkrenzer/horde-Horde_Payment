<?php
/**
 * Horde_Payment external API interface.
 *
 * $Horde: incubator/Horde_Payment/lib/Api.php,v 1.2 2009/09/15 09:35:35 duck Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'show' => '%application%/process/index.php?authorization_id=|id|&authorization_module=|module|'
    );

    /**
     * Get authorization methods linked to an application
     *
     * @param string $app     The Horde application that is doing the authorization
     * @param float  $amount  Amount to process
     *
     * @return array|PEAR_Error authorization ID or PEAR_Error on failure.
     */
    public function getMethods($app, $amount = 0)
    {
        require_once dirname(__FILE__) . '/base.php';
        $methods = $GLOBALS['payment_driver']->getMethods($app, $amount);

        if (empty($methods)) {
            return PEAR::raiseError(_("No methods available."));
        }

        return $methods;
    }

    /**
     * Get available statuses
     *
     * @return array of statuses
     */
    public function getStatuses()
    {
        require_once dirname(__FILE__) . '/Payment.php';

        $statuses = array(
            Horde_Payment::PENDING => _("Pending"),
            Horde_Payment::REVOKED => _("Revoked"),
            Horde_Payment::SUCCESSFUL => _("Successful"),
            Horde_Payment::VOID => _("Void"));

        return $statuses;
    }

    /**
     * Add a request to authorization mechanism
     *
     * @param string $app     The Horde application that is doing the authorization
     * @param string $id      Horde appliaction internal id
     * @param float  $amount  Amount for atuherisation request
     * @param array  $params  Addition data passed by selling app
     *                        avaiable to the selling app in the current session.
     *
     * @return boolean|PEAR_Error of authorization ID or PEAR_Error on failure.
     */
    public function authorizationRequest($app, $id, $amount, $params = array())
    {
        require_once dirname(__FILE__) . '/base.php';

        // Store the Authorization into DB
        $authorizationID = $GLOBALS['payment_driver']->addAuthorization($app, $id, $amount);
        if ($authorizationID instanceof PEAR_Error) {
            return $authorizationID;
        }

        // Add additional data to session
        if (!empty($params)) {
            $_SESSION['payment']['process_params'] = $params;
        }

        return $authorizationID;
    }

    /**
     * Get basic authorization data
     *
     * @param string $id      Authorization id
     * @param string $module  Module to check
     *
     * @return boolean|PEAR_Error of authorization data or PEAR_Error on failure.
     */
    public function getAuthorization($id, $module = null)
    {
        require_once dirname(__FILE__) . '/base.php';

        $authorisation = $GLOBALS['payment_driver']->getAuthorization($id, $module);
        if ($authorisation instanceof PEAR_Error) {
            return $authorisation;
        }

        $attributes = $GLOBALS['payment_driver']->getAuthorizationAttributes($authorisation['authorization_id']);
        if ($attributes instanceof PEAR_Error) {
            return $attributes;
        }

        return array_merge($authorisation, $attributes);
    }

    /**
     * List authorizations
     *
     * @param array $criteria       Authorization details
     * @param intiger $from         Start from records
     * @param intiger $count        The number of records to fetch
     *
     * @return array on success, PEAR_Error on failure.
     */
    public function listAuthorizations($criteria = array(), $from = 0, $count = 0)
    {
        require_once dirname(__FILE__) . '/base.php';

        if (!Horde_Auth::isAuthenticated()) {
            return PEAR::raiseError(_("Only authenticated users can use this method."));
        }

        if (empty($criteria['user_uid']) && !Horde_Auth::isAdmin('Horde_Payment:admin')) {
            $criteria['user_uid'] = Horde_Auth::getAuth();
        }

        return $GLOBALS['payment_driver']->listAuthorizations($criteria, $from, $count);
    }

    /**
     * Void an authorization
     *
     * @param string $id      Authorization id
     * @param float  $amount  Amount for atuherisation request
     *
     * @return boolean|PEAR_Error  True or PEAR_Error on failure.
     */
    public function voidRequest($id, $amount = 0)
    {
        require_once dirname(__FILE__) . '/base.php';
        return Horde_Payment::void($id, $amount);
    }

    /**
     * Wrapper for show method, just to be available RPC
     *
     * @param string $id      Authorization id
     *
     * @return string   Url for the authorization
     */
    public function show($id)
    {
        $url = Horde::applicationUrl('/process/index.php', true, -1);
        return Horde_Util::addParameter($url, 'authorization_id', $id);
    }
}