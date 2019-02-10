<?php

/**
 * The PMF_Ldap class provides methods and functions for a LDAP database.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello Sanchez <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @copyright 2004-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-12-16
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Ldap.
 *
 * @category  phpMyFAQ
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello Sanchez <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @copyright 2004-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-12-16
 */
class PMF_Ldap
{
    /**
     * @var array
     */
    private $_ldapConfig = [];

    /**
     * The connection handle.
     *
     * @return resource
     */
    private $ds = false;

    /**
     * The LDAP base.
     *
     * @var string
     */
    private $base = null;

    /**
     * Errorlog.
     *
     * @var string
     */
    public $error = null;

    /**
     * LDAP error number.
     *
     * @var int
     */
    public $errno = null;

    /**
     * Constructor.
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
     * Connects to given LDAP server with given credentials.
     *
     * @param string $ldapServer
     * @param int    $ldapPort
     * @param string $ldapBase
     * @param string $ldapUser
     * @param string $ldapPassword
     *
     * @return bool
     */
    public function connect($ldapServer, $ldapPort, $ldapBase, $ldapUser = '', $ldapPassword = '')
    {
        // Sanity checks
        if ('' === $ldapServer || '' === $ldapPort || '' === $ldapBase) {
            return false;
        }

        $this->base = $ldapBase;
        $this->ds = ldap_connect($ldapServer, $ldapPort);

        if (!$this->ds) {
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
                if (!ldap_set_option($this->ds, constant($key), $value)) {
                    $this->errno = ldap_errno($this->ds);
                    $this->error = sprintf(
                        'Unable to set LDAP option "%s" to "%s" (Error: %s).',
                        $key,
                        $value,
                        ldap_error($this->ds)
                    );

                    return false;
                }
            }
        }

        if (isset($this->_ldapConfig['ldap_use_dynamic_login']) && $this->_ldapConfig['ldap_use_dynamic_login']) {
            // Check for dynamic user binding
            $ldapRdn = $this->_ldapConfig['ldap_dynamic_login_attribute'].'='.$ldapUser.','.$ldapBase;
            $ldapBind = $this->bind($ldapRdn, $ldapPassword);
        } elseif (isset($this->_ldapConfig['ldap_use_anonymous_login']) && $this->_ldapConfig['ldap_use_anonymous_login']) {
            // Check for anonymous binding
            $ldapBind = $this->bind();
        } else {
            // Check for user binding without RDN
            $ldapBind = $this->bind($ldapUser, $ldapPassword);
        }

        if (false === $ldapBind) {
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
     * Binds to the LDAP directory with specified RDN and password.
     *
     * @param string $rdn
     * @param string $password
     *
     * @return bool
     */
    public function bind($rdn = '', $password = '')
    {
        if (!is_resource($this->ds)) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        if ('' === $rdn && '' === $password) {
            return ldap_bind($this->ds);
        } else {
            return ldap_bind($this->ds, $rdn, $password);
        }
    }

    /**
     * Returns the user's email address from LDAP.
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
     * Returns the user's DN.
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
     * Returns the user's full name from LDAP.
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
     * Returns the LDAP error message of the last LDAP command.
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
     * Returns specific data from LDAP.
     *
     * @param string $username Username
     * @param string $data     MapKey
     *
     * @return string|false
     */
    private function getLdapData($username, $data)
    {
        if (!is_resource($this->ds)) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        if (!array_key_exists($data, $this->_ldapConfig['ldap_mapping'])) {
            $this->error = sprintf(
                'The requested datafield "%s" does not exist in LDAP mapping configuration.',
                $data);

            return false;
        }

        $filter = sprintf(
            '(%s=%s)',
            $this->_ldapConfig['ldap_mapping']['username'],
            $this->quote($username)
        );

        if (true === $this->_ldapConfig['ldap_use_memberOf']) {
            $filter = sprintf(
                '(&%s(memberOf:1.2.840.113556.1.4.1941:=%s))',
                $filter,
                $this->_ldapConfig['ldap_mapping']['memberOf']
            );
        }

        $fields = array($this->_ldapConfig['ldap_mapping'][$data]);
        $sr = ldap_search($this->ds, $this->base, $filter, $fields);

        if (false === $sr) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to search for "%s" (Error: %s)',
                $username,
                ldap_error($this->ds)
            );

            return false;
        }

        $entryId = ldap_first_entry($this->ds, $sr);

        if (false === $entryId) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Cannot get the value(s). Error: %s',
                ldap_error($this->ds)
            );

            return false;
        }

        $values = ldap_get_values($this->ds, $entryId, $fields[0]);

        return $values[0];
    }

    /**
     * Returns the DN from LDAP.
     *
     * @param string $username Username
     *
     * @return string
     */
    private function getLdapDn($username)
    {
        if (!is_resource($this->ds)) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        $filter = sprintf('(%s=%s)',
            $this->_ldapConfig['ldap_mapping']['username'],
            $this->quote($username)
        );
        $sr = ldap_search($this->ds, $this->base, $filter);

        if (false === $sr) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to search for "%s" (Error: %s)',
                $username,
                ldap_error($this->ds)
            );

            return false;
        }

        $entryId = ldap_first_entry($this->ds, $sr);

        if (false === $entryId) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Cannot get the value(s). Error: %s',
                ldap_error($this->ds)
            );

            return false;
        }

        return ldap_get_dn($this->ds, $entryId);
    }

    /**
     * Quotes LDAP strings in accordance with the RFC 2254.
     *
     * @param string $string
     *
     * @return string
     */
    public function quote($string)
    {
        return str_replace(
            array('\\', ' ', '*', '(', ')'),
            array('\\5c', '\\20', '\\2a', '\\28', '\\29'),
            $string
        );
    }
}
