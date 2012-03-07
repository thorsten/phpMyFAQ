<?php
/**
 * LDAP constants for phpMyFAQ.
 *
 * PHP Version 5.2
 *
 *  Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 *
 basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Ldap
 * @author    Lars Scheithauer <lars.scheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-08-05
 */

// Datamapping - in this example for an ADS
$PMF_LDAP['ldap_mapping'] = array (
    'name'     => 'cn',
    'username' => 'samAccountName',
    'mail'     => 'mail'
);

// In a multi-domain environment, users may enter a prefix as domain, e.g. "DOMAIN\username"
// If possible, you should use the Microsoft Glocal Catalog as LDAP-Server, which comes
// with every ADS-Installation.
$PMF_LDAP['ldap_use_domain_prefix'] = true;

// LDAP-options to set
// refer to the documentation of ldap_set_option() for information on available options
$PMF_LDAP["ldap_options"] = array (
    LDAP_OPT_PROTOCOL_VERSION => 3,
    LDAP_OPT_REFERRALS        => 0
);

// Option for adding a check on LDAP groups
// Default: false
$PMF_LDAP['ldap_use_memberOf'] = false;
$PMF_LDAP['ldap_mapping']['memberOf'] = '';

// Option for binding to LDAP directory using SASL
// Default: false
$PMF_LDAP['ldap_use_sasl'] = false;

// Option to use multiple LDAP servers
// Default: false
$PMF_LDAP['ldap_use_multiple_servers'] = false;

// Option to use anonymous LDAP connection (without username and password)
// Default: false
$PMF_LDAP['ldap_use_anonymous_login'] = false;