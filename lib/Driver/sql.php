<?php
/**
 * Payment storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'charset'       The database's internal charset.</pre>
 *
 * Required by some database implementations:<pre>
 *      'database'      The name of the database.
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * The table structure can be created by the scripts/drivers/payment_foo.sql
 * script.
 *
 * $Horde: incubator/Horde_Payment/lib/Driver/sql.php,v 1.27 2009/07/08 18:29:09 slusarz Exp $
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Payment
 */
class Horde_Payment_Driver_sql extends Horde_Payment_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    private $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    private $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $db if a separate write database is not required.
     *
     * @var DB
     */
    private $_write_db;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
        $this->_connect();
    }

    /**
     * Returns authorization data
     *
     * @param string $id     Authorization or Module id
     * @param string $module Module, if null assumes is the authorization id
     */
    public function getAuthorization($id, $module = null)
    {
        $sql = 'SELECT authorization_id, module_name, module_id, amount, created, updated, status, method '
             . 'FROM payment_authorizations WHERE ';

        if (empty($module)) {
            $sql .= ' authorization_id = ?';
            $params = array($id);
        } else {
            $sql .= ' module_name = ? AND module_id = ?';
            $params = array($module, $id);
        }

        $result = $this->_db->getRow($sql, $params, DB_FETCHMODE_ASSOC);
        if (empty($result)) {
            return PEAR::raiseError(sprintf(_("No transaction \"%s\" found"), $id));
        }

        return $result;
    }

    /**
     * Deletes authorization
     *
     * @param string $id     Authorization ID
     */
    public function deleteAuthorization($id)
    {
        if (!Horde_Auth::isAdmin('Horde_Payment:admin')) {
            return PEAR::raiseError(_("Only administrators can delete authorization requests."));
        }

        $sql = 'DELETE FROM payment_authorizations WHERE authorization_id = ?';
        $result = $this->_write_db->query($sql, array($id));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $sql = 'DELETE FROM payment_authorizations_attributes WHERE authorization_id = ?';
        return $this->_write_db->query($sql, array($id));
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
        $sql = 'SELECT authorization_id, module_name, module_id, amount, user_uid, created, updated, status, method '
             . 'FROM payment_authorizations ';

        $where = '';
        $params = array();

        if (!empty($criteria['module_name'])) {
            $params['module_name'] = $criteria['module_name'];
            $where .= ' AND module_name = ?';
        }

        if (!empty($criteria['module_id'])) {
            $params['module_id'] = $criteria['module_id'];
            $where .= ' AND module_id = ?';
        }

        if (!empty($criteria['authorization_id'])) {
            $params['authorization_id'] = $criteria['authorization_id'];
            $where .= ' AND authorization_id = ?';
        }

        if (!empty($criteria['user_uid'])) {
            $params['user_uid'] = $criteria['user_uid'];
            $where .= ' AND user_uid = ?';
        }

        if (!empty($criteria['amount'])) {
            $params['amount'] = $criteria['amount'];
            $where .= ' AND amount = ?';
        }

        if (!empty($criteria['status'])) {
            $params['status'] = $criteria['status'];
            $where .= ' AND status = ?';
        }

        if (!empty($criteria['method'])) {
            $params['method'] = $criteria['method'];
            $where .= ' AND method = ?';
        }

        if (!empty($criteria['date_from'])) {
            $params['date_from'] = $criteria['date_from'];
            $where .= ' AND created >= ?';
        }

        if (!empty($criteria['date_to'])) {
            $params['date_to'] = $criteria['date_to'];
            $where .= ' AND created <= ?';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . substr($where, 4);
        }

        $sql .= ' ORDER BY created DESC';

        if ($count) {
            $sql = $this->db->modifyLimitQuery($sql, $from, $count);
            if ($sql instanceof PEAR_Error) {
                return $sql;
            }
        }

        return $this->_db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    }

    /**
     * Save authorization data
     *
     * @param string $api     The Horde application that is doing the authorization
     * @param string $id      Horde appliaction internal id
     * @param float  $amount  Amount for atuherisation request
     */
    public function addAuthorization($api, $id, $amount, $params = array())
    {
        $sql = 'SELECT 1 FROM payment_authorizations WHERE module_name = ? AND module_id = ?';
        $result = $this->_db->getOne($sql, array($api, $id));
        if ($result instanceof PEAR_Error) {
            return $result;
        } elseif ($result) {
            return PEAR::raiseError(sprintf(_("ID \"%s\" already exists in \"%s\""),
                                            $id, $GLOBALS['registry']->get('name', $api)));
        }

        $sql = 'INSERT INTO payment_authorizations '
                . ' (authorization_id, module_name, module_id, amount, user_uid, status, created) '
                . ' VALUES (?, ?, ?, ?, ?, ?, ?)';

        $uid = uniqid();
        $values = array($uid, $api, $id, $amount, Horde_Auth::getAuth(),
                        Horde_Payment::PENDING, $_SERVER['REQUEST_TIME']);

        $result = $this->_write_db->query($sql, $values);
        if ($result instanceof PEAR_Error) {
            return $result;
        } else {
            return $uid;
        }
    }

    /**
     * Update authorization data
     *
     * @param string $id      Authorization ID
     * @param array  $data    Data tu update
     */
    public function updateAuthorization($id, $data)
    {
        $values = array($_SERVER['REQUEST_TIME']);
        $sql = 'UPDATE payment_authorizations SET updated = ? ';

        foreach ($data as $key => $value) {
            $sql .= ', ' . $key . ' = ? ';
            $values[] = $value;
        }

        $sql .=  'WHERE authorization_id = ?';
        $values[] = $id;

        return $this->_write_db->query($sql, $values);
    }

    /**
     * Add authorization attributes
     *
     * @param string $id      Authorization ID
     * @param array  $info    Infomation to store
     */
    public function addAuthorizationAttributes($id, $info)
    {
        $sql = 'INSERT INTO payment_authorizations_attributes '
             . ' (authorization_id, attribute_key, attribute_value, created) VALUES (?, ?, ?, ?)';
        $sth = $this->_write_db->prepare($sql);
        if ($sth instanceof PEAR_Error) {
            return $sth;
        }

        $data = array();
        $timestamp = $_SERVER['REQUEST_TIME'];
        foreach ($info as $key => $value) {
            $data[] = array($id, $key, $value, $timestamp);
        }

        return $this->_write_db->executeMultiple($sth, $data);
    }

    /**
     * Update authorization attributes
     *
     * @param string $id      Authorization ID
     * @param array  $info    Infomation to store
     */
    public function updateAuthorizationAttributes($id, $info)
    {
        $sql = 'UPDATE payment_authorizations_attributes SET '
             . 'attribute_value = ?, updated = ? WHERE authorization_id = ? AND attribute_key = ?';
        $sth = $this->_write_db->prepare($sql);
        if ($sth instanceof PEAR_Error) {
            return $sth;
        }

        $data = array();
        $timestamp = $_SERVER['REQUEST_TIME'];
        foreach ($info as $key => $value) {
            $data[] = array($value, $timestamp, $id, $key);
        }

        return $this->_write_db->executeMultiple($sth, $data);
    }

    /**
     * Get authorization attributes
     *
     * @param string $id      Authorization ID
     * @param array  $info    Infomation to store
     */
    public function getAuthorizationAttributes($id)
    {
        $sql = 'SELECT attribute_key, attribute_value FROM payment_authorizations_attributes WHERE authorization_id = ?';
        return $this->_db->getAssoc($sql, false, array($id));
    }

    /**
     * Check if authorization has any attributes
     *
     * @param string $id      Authorization ID
     */
    public function hasAuthorizationAttributes($id)
    {
        $sql = 'SELECT 1 FROM payment_authorizations_attributes WHERE authorization_id = ?';
        return $this->_db->getOne($sql, array($id));
    }

    /**
     * Get methods linked to app
     *
     * @return Associative array of modules and methods
     */
    protected function _getMethods()
    {
        $sql = 'SELECT module, method FROM payment_methods';
        return $this->_db->getAssoc($sql, false, array(), DB_FETCHMODE_ORDERED, true);
    }

    /**
     * Save module method links
     *
     * @param array  $link    Matrix of payment methods
     */
    protected function _saveMethodLink($link)
    {
        $sql = 'INSERT INTO payment_methods (module, method) VALUES (?, ?)';
        $sth = $this->_write_db->prepare($sql);
        if ($sth instanceof PEAR_Error) {
            return $sth;
        }

        $data = array();
        foreach ($link as $module => $methods) {
            foreach (array_keys($methods) as $method) {
                $data[] = array($module, $method);
            }
        }

        $sql = 'TRUNCATE payment_methods';
        $result = $this->_write_db->query($sql);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return $this->_write_db->executeMultiple($sth, $data);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success; exits (Horde::fatal()) on error.
     */
    private function _connect()
    {
        Horde::assertDriverConfig($this->_params, 'storage',
                                  array('phptype', 'charset'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->_write_db = DB::connect($this->_params,
                                        array('persistent' => !empty($this->_params['persistent'])));
        if ($this->_write_db instanceof PEAR_Error) {
            Horde::fatal($this->_write_db, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
            if ($this->_db instanceof PEAR_Error) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db =& $this->_write_db;
        }

        $this->connected = true;

        return true;
    }
}