<?php
/**
 * The PMF_Ldap class provides methods and functions for a LDAP database
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Ldap
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello Sanchez <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @copyright 2004-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-12-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Ldap
 *
 * @category  phpMyFAQ
 * @package   PMF_Ldap
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello Sanchez <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @copyright 2004-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-12-16
 */
class PMF_Ldap
{
    /**
     * @var array
     */
    private $_ldapConfig = array();

    /**
     * The connection handle
     *
     * @return resource
     */
    private $ds = false;

    /**
     * The LDAP base
     *
     * @var string
     */
    private $base = null;

    /**
     * Errorlog
     *
     * @var string
     */
    public $error = null;

    /**
     * LDAP error number
     *
     * @var integer
     */
    public $errno = null;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Ldap
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_ldapConfig = $config->getLdapConfig();
    }

    /**
     * Connects to given LDAP server with given credentials
     *
     * @param string  $ldapServer
     * @param integer $ldapPort
     * @param string  $ldapBase
     * @param string  $ldapUser
     * @param string  $ldapPassword
     *
     * @return boolean
     */
    public function connect($ldapServer, $ldapPort, $ldapBase, $ldapUser = '', $ldapPassword = '')
    {
        // Sanity checks
        if ('' === $ldapServer || '' === $ldapPort || '' === $ldapBase) {
            return false;
        }

        $this->base = $ldapBase;
        $this->ds   = ldap_connect($ldapServer, $ldapPort);

        if (! $this->ds) {
            $this->error = sprintf(
                'Unable to connect to LDAP server (Error: %s)',
                ldap_error($this->ds)
            );
            $this->errno = ldap_errno($this->ds);
            return false;
        }

        // optionally set Bind version
        if (isset($this->_ldapConfig['ldap_options'])) {
            foreach ($this->_ldapConfig['ldap_options'] as $key => $value) {
                if (! ldap_set_option($this->ds, $key, $value)) {
                    $this->errno = ldap_errno($this->ds);
                    $this->error = sprintf(
                        'Unable to set LDAP option "%s" to "%s" (Error: %s).',
                        $key,
                        $value,
                        ldap_error($this->ds)
                    );
                }
            }
        }


        if (isset($this->_ldapConfig['ldap_use_anonymous_login']) && $this->_ldapConfig['ldap_use_anonymous_login']) {
            $ldapBind = ldap_bind($this->ds); // Anonymous LDAP login
        } else {
            $ldapBind = ldap_bind($this->ds, $ldapUser, $ldapPassword);
        }

        if (! $ldapBind) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to bind to LDAP server (Error: %s).',
                ldap_error($this->ds)
            );
            $this->ds = false;
            return false;
        }

        return true;
    }

    /**
     * Returns the user's email address from LDAP
     *
     * @param string $username Username
     *
     * @return string
     */
    public function getMail($username)
    {
        return $this->getLdapData($username, 'mail');
    }

    /**
     * Returns the user's DN
     *
     * @param string $username Username
     *
     * @return string
     */
    public function getDn($username)
    {
        return $this->getLdapDn($username);
    }

    /**
     * Returns the user's full name from LDAP
     *
     * @param string $username Username
     *
     * @return string
     */
    public function getCompleteName($username)
    {
        return $this->getLdapData($username, 'name');
    }

    /**
     * Returns the LDAP error message of the last LDAP command
     *
     * @param resource $ds LDAP resource
     *
     * @return string
     */
    public function error($ds = null)
    {
        if ($ds === null) {
            $ds = $this->ds;
        }
        return ldap_error($ds);
    }

    /**
     * Returns specific data from LDAP
     *
     * @param string $username Username
     * @param string $data     MapKey
     *
     * @return string
     */
    private function getLdapData ($username, $data)
    {
        if (!array_key_exists($data, $this->_ldapConfig['ldap_mapping'])) {
            $this->error = sprintf(
                'The requested datafield "%s" does not exist in LDAP mapping configuration.',
                $data);
            return '';
        }

        $filter = sprintf(
            '(%s=%s)',
            $this->_ldapConfig['ldap_mapping']['username'],
            $this->quote($username)
        );

        if (true === $this->_ldapConfig['ldap_use_memberOf']) {
            $filter = sprintf(
                '(&%s(memberof=%s))',
                $filter,
                $this->_ldapConfig['ldap_mapping']['memberOf']
            );
        }

        $fields = array($this->_ldapConfig['ldap_mapping'][$data]);
        $sr     = ldap_search($this->ds, $this->base, $filter, $fields);

        if (!$sr) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to search for "%s" (Error: %s)',
                $username,
                ldap_error($this->ds)
            );
        }

        $entryId = ldap_first_entry($this->ds, $sr);

        if (!is_resource($entryId)) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Cannot get the value(s). Error: %s',
                ldap_error($this->ds)
            );
        }

        $values = ldap_get_values($this->ds, $entryId, $fields[0]);

        return $values[0];
    }

    /**
     * Returns the DN from LDAP
     *
     * @param string $username Username
     *
     * @return string
     */
    private function getLdapDn($username)
    {
        $filter = sprintf('(%s=%s)',
            $this->_ldapConfig['ldap_mapping']['username'],
            $this->quote($username)
        );
        $sr = ldap_search($this->ds, $this->base, $filter);

        if (! $sr) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to search for "%s" (Error: %s)',
                $username,
                ldap_error($this->ds)
            );
        }

        $entryId = ldap_first_entry($this->ds, $sr);

        if (! $entryId) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Cannot get the value(s). Error: %s',
                ldap_error($this->ds)
            );
        }

        return ldap_get_dn($this->ds, $entryId);
    }

    /**
     * Quotes LDAP strings in accordance with the RFC 2254
     *
     * @param string $string
     *
     * @return string
     */
    public function quote($string)
    {
        return str_replace(
            array( '\\', ' ', '*', '(', ')' ),
            array( '\\5c', '\\20', '\\2a', '\\28', '\\29' ),
            $string
        );
    }
}