<?php
/**
 * The PMF_Ldap class provides methods and functions for a LDAP database
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Ldap
 * @author     Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Alberto Cabello Sánchez <alberto@unex.es>
 * @author     Lars Scheithauer <larsscheithauer@googlemail.com>
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
 * @author     Lars Scheithauer <larsscheithauer@googlemail.com>
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
    	global $PMF_LDAP;
    	
        $this->base = $ldap_base;

        if (!isset($ldap_user) || !isset($ldap_server) || $ldap_server == "" || !isset($ldap_port) || $ldap_port == "" || !isset($ldap_base) || $ldap_base == "" || !isset($ldap_password)) {
            return false;
		}

        $this->ds = ldap_connect($ldap_server, $ldap_port);
        if (!$this->ds) {
            $this->error = 'Unable to connect to LDAP server (Error: '.ldap_error($this->ds).')';
            $this->errno = ldap_errno($this->ds);
        }

        // optionally set Bind version
        foreach ($PMF_LDAP['ldap_options'] as $key => $value) { 
            if (!ldap_set_option($this->ds, $key, $value)) {
                $this->error = 'Unable to set LDAP option "'.$key.'" to "'.$value.'" (Error: '.ldap_error($this->ds).')';
                $this->errno = ldap_errno($this->ds);
            }
        }

        $ldapbind = ldap_bind($this->ds, $ldap_user, $ldap_password);

        if (!$ldapbind) {
            $this->error = 'Unable to bind to LDAP server (Error: '.ldap_error($this->ds).')';
            $this->errno = ldap_errno($this->ds);
            $this->ds = false;
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
        return $this->getLdapData($username, 'mail');
    }

    /**
     * Returns the user's full name from LDAP
     *
     * @param  string $username Username
     * @return string
     */
    public function getCompleteName($username)
    {
        return $this->getLdapData($username, "name");
    }

    /**
     * Returns the LDAP error message of the last LDAP command
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
     * @param  string $username Username
     * @param  string $data     MapKey
     * @return string
     */
    private function getLdapData($username, $data)
    {
        global $PMF_LDAP;
       
        if (!array_key_exists($data, $PMF_LDAP['ldap_mapping'])) {
            $this->error = 'requested datafield "'.$data.'" does not exist in $PMF_LDAP["ldap_mapping"].';
            return '';
        }
 
        $filter = "(" . $PMF_LDAP['ldap_mapping']['username'] . "=" . $username . ")";
        $fields = array($PMF_LDAP['ldap_mapping'][$data]);
        
         $sr = ldap_search($this->ds, $this->base, $filter, $fields);
         if (!$sr) {
            $this->errno = ldap_errno($this->ds);
            $this->error = 'Unable to search for "'.$username.'" (Error: '.ldap_error($this->ds).')';
         }
         
         $entryId = ldap_first_entry($this->ds, $sr);
         $values  = ldap_get_values($this->ds, $entryId, $fields[0]);
         
         return $values[0];
    }
    
}