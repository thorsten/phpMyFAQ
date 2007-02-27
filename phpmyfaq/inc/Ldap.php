<?php
/**
 * $Id: Ldap.php,v 1.3 2007-02-27 20:52:32 thorstenr Exp $
 *
 * The PMF_Ldap class provides methods and functions for a LDAP database
 *
 * @author      Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @package     LDAP
 * @since       2004-12-16
 * @copyright   (c) 2004-2007 phpMyFAQ Team
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

class PMF_Ldap
{
    /**
     * The connection handle
     *
     */
    var $ds = false;

    /**
     * The LDAP base
     *
     */
    var $base = null;
    
    /**
     * Errorlog
     *
     * @var string
     */
    var $error = null;

    /**
     * Constructor
     *
     * Connects and binds to LDAP server
     *
     * @param   string      server name
     * @param   integer     port number
     * @param   string      base dn
     * @param   string      LDAP user
     * @param   string      LDAP password
     * @return  mixed
     * @access  public
     * @auhor   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Ldap($ldap_server, $ldap_port, $ldap_base, $ldap_user = '', $ldap_password = '')
    {
        $this->base = $ldap_base;

        if (!isset($ldap_user) || !isset($ldap_server) || $ldap_server == "" || !isset($ldap_port) || $ldap_port == "" || !isset($ldap_base) || $ldap_base == "" || !isset($ldap_password)) {
            return false;
		}
        
        $this->ds = ldap_connect($ldap_server, $ldap_port);
        if (!$this->ds) {
            $this->error = 'Unable to connect to LDAP server (Error: '.ldap_error().')';
        }
        
        $ldapbind = ldap_bind($this->ds, $ldap_user, $ldap_password);
        if (!$ldapbind) {
            $this->error = 'Unable to bind to LDAP server (Error: '.ldap_error().')';
        }
        
        return $this->ds;
    }

    /**
     * Returns the user's email address from LDAP
     *
     * @param    string
     * @return   string
     * @access   public
     * @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function ldap_getMail($username)
    {
        if ($this->ds) {
            return '';
        }

        $sr = ldap_search($this->ds, $this->base, 'uid='.$username, array('mail'));
        if (!$sr) {
            $this->error = 'Unable to search for "'.$username.'" (Error: '.ldap_error().')';
        }
        $entryId = ldap_first_entry($this->ds, $sr);
        $values = ldap_get_values($this->ds, $entryId, 'mail');
        return $values[0];
    }

    /**
     * Returns the user's email address from LDAP
     *
     * @param    string
     * @return   string
     * @access   public
     * @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function ldap_getCompleteName($username)
    {
        if ($this->ds) {
            return '';
        }

        $sr = ldap_search($this->ds, $this->base, 'uid='.$username, array('cn'));
        if (!$sr) {
            $this->error = 'Unable to search for "'.$username.'" (Error: '.ldap_error().')';
        }
        $entryId = ldap_first_entry($this->ds, $sr);
        $values = ldap_get_values($this->ds, $entryId, 'cn');
        return $values[0];
    }

    /**
     * Returns the LDAP error message of the last LDAP command
     *
     * @return   string
     * @access   public
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function error()
    {
        return ldap_error($this->ds);
    }
}