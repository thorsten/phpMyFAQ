<?php
/**
 * The PMF_Ldap class provides methods and functions for a LDAP database
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Ldap
 * @author     Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Alberto Cabello Sánchez <alberto@unex.es>
 * @since      2004-12-16
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

/**
 * PMF_Ldap
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Ldap
 * @author     Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Alberto Cabello Sánchez <alberto@unex.es>
 * @since      2004-12-16
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id$
 */
class PMF_Ldap
{
    /**
     * The connection handle
     *
     */
    private $ds = false;

    /**
     * The LDAP base
     *
     */
    private $base = null;

    /**
     * Errorlog
     *
     * @var string
     */
    private $error = null;

    /**
     * Constructor
     *
     * Connects and binds to LDAP server
     *
     * @param  string  $ldap_server   Server name
     * @param  integer $ldap_port     Port number
     * @param  string  $ldap_base     Base DN
     * @param  string  $ldap_user     LDAP user
     * @param  string  $ldap_password LDAP password
     * @return void
     */
    public function __construct($ldap_server, $ldap_port, $ldap_base, $ldap_user = '', $ldap_password = '')
    {
        $this->base = $ldap_base;

        if (!isset($ldap_user) || !isset($ldap_server) || $ldap_server == "" || !isset($ldap_port) || $ldap_port == "" || !isset($ldap_base) || $ldap_base == "" || !isset($ldap_password)) {
            return false;
		}

        $this->ds = ldap_connect($ldap_server, $ldap_port);
        if (!$this->ds) {
            $this->error = 'Unable to connect to LDAP server (Error: '.ldap_error($this->ds).')';
        }

        $ldapbind = ldap_bind($this->ds, $ldap_user, $ldap_password);
        if (!$ldapbind) {
            $this->error = 'Unable to bind to LDAP server (Error: '.ldap_error($this->ds).')';
        }
    }

    /**
     * Returns the user's email address from LDAP
     *
     * @param  string $username Username
     * @return string
     */
    public function getMail($username)
    {
        if (!$this->ds) {
            return '';
        }

        $sr = ldap_search($this->ds, $this->base, $username, array('mail'));
        if (!$sr) {
            $this->error = 'Unable to search for "'.$username.'" (Error: '.ldap_error($this->ds).')';
        }
        $entryId = ldap_first_entry($this->ds, $sr);
        $values  = ldap_get_values($this->ds, $entryId, 'mail');
        return $values[0];
    }

    /**
     * Returns the user's email address from LDAP
     *
     * @param  string $username Username
     * @return string
     */
    public function getCompleteName($username)
    {
        if (!$this->ds) {
            return '';
        }

        $sr = ldap_search($this->ds, $this->base, $username, array('cn'));
        if (!$sr) {
            $this->error = 'Unable to search for "'.$username.'" (Error: '.ldap_error($this->ds).')';
        }
        $entryId = ldap_first_entry($this->ds, $sr);
        $values  = ldap_get_values($this->ds, $entryId, 'cn');
        return $values[0];
    }

    /**
     * Returns the LDAP error message of the last LDAP command
     *
     * @return string
     */
    public function error()
    {
        return ldap_error($this->ds);
    }
}