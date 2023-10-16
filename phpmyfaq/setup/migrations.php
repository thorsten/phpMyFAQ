<?php

/**
 * This script executes all necessary database changes to update phpMyFAQ to a certain version.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-06-25
 */

//
// UPDATES FROM 3.1.0-alpha
//
use phpMyFAQ\Enums\ReleaseType;

if (version_compare($version, '3.1.0-alpha', '<=')) {
    // Add is_visible flag for user data
    if ('sqlite3' === $dbConfig->getType()) {
        $query[] = 'ALTER TABLE ' . $prefix . 'faquserdata ADD COLUMN is_visible INT(1) DEFAULT 0';
    } else {
        $query[] = 'ALTER TABLE ' . $prefix . 'faquserdata ADD is_visible INTEGER DEFAULT 0';
    }

    // Remove RSS support
    $faqConfig->delete('main.enableRssFeeds');

    // Add API-related configuration
    $faqConfig->add('api.enableAccess', true);
    $faqConfig->add('api.apiClientToken', '');

    // Add passlist for domains
    $faqConfig->add('security.domainWhiteListForRegistrations', '');
}

//
// UPDATES FROM 3.1.0-alpha.3
//
if (version_compare($version, '3.1.0-alpha.3', '<=')) {
    // Add "login with email address" configuration
    $faqConfig->add('main.loginWithEmailAddress', false);
}

//
// UPDATES FROM 3.1.0-beta
//
if (version_compare($version, '3.1.0-beta', '<=')) {
    $query[] = match ($dbConfig->getType()) {
        'mysqli' => 'CREATE TABLE ' . $prefix . 'faqcategory_order (
                    category_id int(11) NOT NULL,
                    position int(11) NOT NULL,
                    PRIMARY KEY (category_id))',
        'pgsql', 'sqlite3', 'sqlsrv' => 'CREATE TABLE ' . $prefix . 'faqcategory_order (
                    category_id INTEGER NOT NULL,
                    position INTEGER NOT NULL,
                    PRIMARY KEY (category_id))',
    };
}

//
// UPDATES FROM 3.1.0-RC
//
if (version_compare($version, '3.1.0-RC', '<=')) {
    $faqConfig->delete('records.autosaveActive');
    $faqConfig->delete('records.autosaveSecs');
}

//
// UPDATES FROM 3.2.0-alpha
//
if (version_compare($version, '3.2.0-alpha', '<')) {
    // Azure AD support and 2FA-support
    $faqConfig->add('security.enableSignInWithMicrosoft', false);

    if ('sqlite3' === $dbConfig->getType()) {
        $query[] = 'ALTER TABLE ' . $prefix . 'faquser 
                ADD COLUMN refresh_token TEXT NULL DEFAULT NULL,
                ADD COLUMN access_token TEXT NULL DEFAULT NULL,
                ADD COLUMN code_verifier VARCHAR(255) NULL DEFAULT NULL,
                ADD COLUMN jwt TEXT NULL DEFAULT NULL;';

        $query[] = 'ALTER TABLE ' . $prefix . 'faquserdata
                ADD COLUMN twofactor_enabled INT(1) NULL DEFAULT 0,
                ADD COLUMN secret VARCHAR(128) NULL DEFAULT NULL';
    } else {
        $query[] = 'ALTER TABLE ' . $prefix . 'faquser 
                ADD refresh_token TEXT NULL DEFAULT NULL,
                ADD access_token TEXT NULL DEFAULT NULL,
                ADD code_verifier VARCHAR(255) NULL DEFAULT NULL,
                ADD jwt TEXT NULL DEFAULT NULL;';

        $query[] = 'ALTER TABLE ' . $prefix . 'faquserdata
                ADD twofactor_enabled INT(1) NULL DEFAULT 0,
                ADD secret VARCHAR(128) NULL';
    }

    // New backup
    $query[] = 'CREATE TABLE ' . $prefix . 'faqbackup (
            id INT(11) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            authkey VARCHAR(255) NOT NULL,
            authcode VARCHAR(255) NOT NULL,
            created timestamp NOT NULL,
            PRIMARY KEY (id))';

    // Migrate MySQL from MyISAM to InnoDB
    if ('mysqli' === $dbConfig->getType()) {
        $query[] = 'ALTER TABLE ' . $prefix . 'faqdata ENGINE=INNODB';
    }

    // new options
    $faqConfig->add('main.enableAskQuestions', true);
    $faqConfig->add('main.enableNotifications', true);

    // update options
    $faqConfig->rename('security.loginWithEmailAddress', 'security.loginWithEmailAddress');

    // Google ReCAPTCHAv3 support
    $faqConfig->add('security.enableGoogleReCaptchaV2', false);
    $faqConfig->add('security.googleReCaptchaV2SiteKey', '');
    $faqConfig->add('security.googleReCaptchaV2SecretKey', '');

    // Remove section tables
    $query[] = 'DROP TABLE ' . $prefix . 'faqsections';
    $query[] = 'DROP TABLE ' . $prefix . 'faqsection_category';
    $query[] = 'DROP TABLE ' . $prefix . 'faqsection_group';
    $query[] = 'DROP TABLE ' . $prefix . 'faqsection_news';
}

//
// UPDATES FROM 3.2.0-beta
//
if (version_compare($version, '3.2.0-beta', '<')) {
    $faqConfig->add('mail.remoteSMTPDisableTLSPeerVerification', false);
    $faqConfig->delete('main.enableLinkVerification');

    // Delete link verification columns
    $query[] = 'ALTER TABLE ' . $prefix . 'faqdata DROP COLUMN links_state, DROP COLUMN links_check_date';
    $query[] = 'ALTER TABLE ' . $prefix . 'faqdata_revisions DROP COLUMN links_state, DROP COLUMN links_check_date';

    // Configuration values in a TEXT column
    switch ($dbConfig->getType()) {
        case 'mysqli':
            $query[] = 'ALTER TABLE ' . $prefix . 'faqconfig MODIFY config_value TEXT DEFAULT NULL';
            break;
        case 'pgsql':
            $query[] = 'ALTER TABLE ' . $prefix . 'faqconfig ALTER COLUMN config_value TYPE TEXT';
            break;
        case 'sqlite3':
            $query[] = 'CREATE TABLE ' . $prefix . 'faqconfig_new (config_name VARCHAR(255) NOT NULL default \'\', config_value TEXT DEFAULT NULL, PRIMARY KEY (config_name))';
            $query[] = 'INSERT INTO ' . $prefix . 'faqconfig_new SELECT config_name, config_value FROM ' . $prefix . 'faqconfig';
            $query[] = 'DROP TABLE ' . $prefix . 'faqconfig';
            $query[] = 'ALTER TABLE ' . $prefix . 'faqconfig_new RENAME TO ' . $prefix . 'faqconfig';
            break;
        case 'sqlsrv':
            $query[] = 'ALTER TABLE ' . $prefix . 'faqconfig ALTER COLUMN config_value TEXT';
            break;
    }
}

//
// UPDATES FROM 3.2.0-beta.2
//
if (version_compare($version, '3.2.0-beta.2', '<')) {
    // HTML-support for contactInformation
    $faqConfig->add('main.contactInformationHTML', false);
    $faqConfig->rename('main.contactInformations', 'main.contactInformation');
}

//
// UPDATES FROM 3.2.0-RC
//
if (version_compare($version, '3.2.0-RC', '<')) {
    // HTML-support for contactInformation
    $faqConfig->add('spam.mailAddressInExport', true);
}

//
// UPDATES FROM 4.0.0-alpha
//
if (version_compare($version, '4.0.0-alpha', '<')) {
    // Move everything to the new file layout
    // @todo move attachments in filesystem and database

    // Automatic updates
    $faqConfig->add('upgrade.onlineUpdateEnabled', true);
    $faqConfig->add('upgrade.releaseEnvironment', ReleaseType::DEVELOPMENT->value);
    $faqConfig->add('upgrade.dateLastChecked', '');
    $faqConfig->add('upgrade.lastDownloadedPackage', '');

    // Rewrite rules are now mandatory, social network support removed
    $faqConfig->delete('main.enableRewriteRules');
    $faqConfig->delete('socialnetworks.enableTwitterSupport');
    $faqConfig->delete('socialnetworks.twitterConsumerKey');
    $faqConfig->delete('socialnetworks.twitterConsumerSecret');
    $faqConfig->delete('socialnetworks.twitterAccessTokenKey');
    $faqConfig->delete('socialnetworks.twitterAccessTokenSecret');
    $faqConfig->delete('socialnetworks.disableAll');
    $faqConfig->delete('mail.remoteSMTPEncryption');

    // Bookmarks support
    $query[] = match ($dbConfig->getType()) {
        'mysqli' => 'CREATE TABLE ' . $prefix . 'faqbookmarks (
                    userid int(11) DEFAULT NULL,
                    faqid int(11) DEFAULT NULL',
        'pgsql', 'sqlite3', 'sqlsrv' => 'CREATE TABLE ' . $prefix . 'faqbookmarks (
                    userid INTEGER DEFAULT NULL,
                    faqid INTEGER DEFAULT NULL',
    };
}
