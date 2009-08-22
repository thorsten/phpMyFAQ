<?php
/**
 * LDAP  constants for phpMyFAQ.
 *
 * @package   phpMyFAQ
 * @author    Lars Scheithauer <lars.scheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2009-08-05
 * @version   SVN: $Id$
 * @copyright 2009 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 */

// Datamapping - in this example for an ADS
$PMF_LDAP['ldap_mapping'] = array (
    'name'     => 'cn',
    'username' => 'samAccountName',
    'mail'     => 'mail');

// In a multi-domain environment, users may enter a prefix as domain, e.g. "DOMAIN\username"
// If possible, you should use the Microsoft Glocal Catalog as LDAP-Server, which comes
// with every ADS-Installation.
$PMF_LDAP['ldap_use_domain_prefix'] = true;

// LDAP-options to set
// refer to the documentation of ldap_set_option() for information on available options
$PMF_LDAP["ldap_options"] = array (
    LDAP_OPT_PROTOCOL_VERSION => 3,
    LDAP_OPT_REFERRALS        => 0 );