<?php
/**
 * Main update script
 *
 * PHP Version 5.2
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
 * 
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Melchinger <t.melchinger@uni.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-01-10
 */

define('NEWVERSION', '2.6.11');
define('APIVERSION', 1);
define('MINIMUM_PHP_VERSION', '5.2.0');
define('COPYRIGHT', '&copy; 2001-2010 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ <?php print NEWVERSION; ?> Update</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="shortcut icon" href="../template/default/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/default/favicon.ico" type="image/x-icon" />
    <style media="screen" type="text/css">@import url(style/setup.css);</style>
</head>
<body>

<h1 id="header">phpMyFAQ <?php print NEWVERSION; ?> Update</h1>

<?php

if (version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '<')) {
    printf("<p class=\"center\">Sorry, but you need PHP %s or later!</p>\n", MINIMUM_PHP_VERSION);
    HTMLFooter();
    die();
}

require_once PMF_ROOT_DIR.'/inc/autoLoader.php';
require_once PMF_ROOT_DIR.'/config/constants.php';

$step        = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version     = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query       = array();
$templateDir = '../template';

/**
 * Print out the HTML Footer
 *
 * @return void
 */
function HTMLFooter()
{
    printf('<p class="center">%s</p></body></html>', COPYRIGHT);
}

if (!is_readable(PMF_ROOT_DIR.'/inc/data.php') && !is_readable(PMF_ROOT_DIR.'/config/database.php')) {
    print '<p class="center">It seems you never run a version of phpMyFAQ.<br />' .
          'Please use the <a href="setup.php">install script</a>.</p>';
    HTMLFooter();
    die();
}

if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    // before 2.6.0-alpha
    require PMF_ROOT_DIR . '/inc/data.php';
} else {
    // after 2.6.0-alpha
    require PMF_ROOT_DIR . '/config/database.php';
}
require PMF_ROOT_DIR . '/inc/functions.php';

define('SQLPREFIX', $DB['prefix']);
$db = PMF_Db::dbSelect($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
    
/**************************** STEP 1 OF 4 ***************************/
if ($step == 1) {
    
    $faqconfig = PMF_Configuration::getInstance();
    $version   = $faqconfig->get('main.currentVersion');
?>
<form action="update.php?step=2" method="post">
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 1 of 4)</strong></legend>
<p>This update will work <strong>only</strong> for the following versions:</p>
<ul type="square">
    <li>phpMyFAQ 2.0.x</li>
    <li>phpMyFAQ 2.5.x</li>
    <li>phpMyFAQ 2.6.x</li>
</ul>
<p>This update will <strong>not</strong> work for the following versions:</p>
<ul type="square">
    <li>phpMyFAQ 0.x</li>
    <li>phpMyFAQ 1.x</li>
</ul>
<p><strong>Please make a full backup of your SQL tables before running this update.</strong></p>
<?php 
if (version_compare($version, '2.6.0-alpha', '<') && !is_writeable($templateDir)) {
    printf("<p><strong>Please make the dir %s and its contents writeable (777 on Linux/UNIX).</strong></p>",
        $templateDir);
}
?>
<p align="center">Your current phpMyFAQ version: <?php print $version; ?></p>
<input name="version" type="hidden" value="<?php print $version; ?>"/>

<p class="center"><input type="submit" value="Go to step 2 of 4" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
}

/**************************** STEP 2 OF 4 ***************************/
if ($step == 2) {

    $checkDatabaseSetupFile = $checkLdapSetupFile = $checkTemplateDirectory = false;
    
    // First backup old inc/data.php, then backup new config/bak.database.php and copy inc/data.php 
    // to config/database.php
    if (file_exists(PMF_ROOT_DIR . '/inc/data.php')) {
        if (!copy(PMF_ROOT_DIR . '/inc/data.php', PMF_ROOT_DIR . '/config/database.bak.php') ||
            !copy(PMF_ROOT_DIR . '/inc/data.php', PMF_ROOT_DIR . '/config/database.php')) {
            print "<p class=\"error\"><strong>Error:</strong> The backup file ../config/database.bak.php could " .
                  "not be written. Please correct this!</p>";
        } else {
            $checkDatabaseSetupFile = true;
        }
    }
    
    // The backup an existing config/database.php
    if (file_exists(PMF_ROOT_DIR . '/config/database.php')) {
        if (!copy(PMF_ROOT_DIR . '/config/database.php', PMF_ROOT_DIR . '/config/database.bak.php')) {
            print "<p class=\"error\"><strong>Error:</strong> The backup file ../config/database.bak.php could " .
                  "not be written. Please correct this!</p>";
        } else {
            $checkDatabaseSetupFile = true;
        }
    }
    
    // Now backup and move LDAP setup if available
    if (file_exists(PMF_ROOT_DIR . '/inc/dataldap.php')) {
        if (!copy(PMF_ROOT_DIR . '/inc/dataldap.php', PMF_ROOT_DIR . '/config/ldap.bak.php') ||
            !copy(PMF_ROOT_DIR . '/inc/dataldap.php', PMF_ROOT_DIR . '/config/ldap.php')) {
            print "<p class=\"error\"><strong>Error:</strong> The backup file ../config/ldap.bak.php could " .
                  "not be written. Please correct this!</p>";
        } else {
            $checkLdapSetupFile = true;
        }
    }
    

    $notWritableFiles = array();
    foreach (new DirectoryIterator($templateDir) as $item) {
        if ($item->isFile() && !$item->isWritable()) {
            $notWritableFiles[] = "$templateDir/{$item->getFilename()}";
        }
    }
    if (version_compare($version, '2.6.0-alpha', '<') && (!is_writeable($templateDir) || !empty($notWritableFiles))) {
        if (!is_writeable($templateDir)) {
            printf("<p><strong>The dir %s isn't writeable.</strong></p>\n", $templateDir);
        }
        if (!empty($notWritableFiles)) {
            foreach ($notWritableFiles as $item) {
                printf("<p><strong>The file %s isn't writeable.</strong></p>\n", $item);
            }
        }
        
    } else {
        $checkTemplateDirectory = true;
    }
    
    // is everything is okay?
    if ($checkDatabaseSetupFile && $checkTemplateDirectory) {
?>
<form action="update.php?step=3" method="post">
<input type="hidden" name="version" value="<?php print $version; ?>" />
<fieldset class="installation">
    <legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 2 of 4)</strong></legend>
    <p>A backup of your database configuration file has been made.</p>
    <p class="center"><input type="submit" value="Go to step 3 of 4" class="button" /></p>
</fieldset>
</form>
<?php
        HTMLFooter();
    } else {
        print "<p class=\"error\"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>\n";
        HTMLFooter();
        die();
    }
}

/**************************** STEP 3 OF 4 ***************************/
if ($step == 3) {
?>
<form action="update.php?step=4" method="post">
<input type="hidden" name="version" value="<?php print $version; ?>" />
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 3 of 4)</strong></legend>
<p class="center">The configuration will be updated after the next step.</p>
<p class="center"><input type="submit" value="Go to step 4 of 4" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
}

/**************************** STEP 4 OF 4 ***************************/
if ($step == 4) {
    
    require_once PMF_ROOT_DIR . '/inc/Configuration.php';
    require_once PMF_ROOT_DIR . '/inc/Db.php';
    require_once PMF_ROOT_DIR . '/inc/PMF_DB/Driver.php';
    require_once PMF_ROOT_DIR . '/inc/Link.php';
    
    $images = array();

    //
    // UPDATES FROM 2.0.2
    //
    if (version_compare($version, '2.0.2', '<')) {
        $query[] = 'CREATE INDEX '.SQLPREFIX.'idx_user_time ON '.SQLPREFIX.'faqsessions (user_id, time)';
    }

    //
    // UPDATES FROM 2.5.0-alpha2
    //
    if (version_compare($version, '2.5.0-alpha2', '<')) {
        switch($DB['type']) {
            case 'pgsql':
                $query[] = "CREATE TABLE ".SQLPREFIX."faqsearches (
                            id SERIAL NOT NULL ,
                            lang VARCHAR(5) NOT NULL ,
                            searchterm VARCHAR(255) NOT NULL ,
                            searchdate TIMESTAMP,
                            PRIMARY KEY (id, lang))";
                break;

            default:
                $query[] = "CREATE TABLE ".SQLPREFIX."faqsearches (
                            id INTEGER NOT NULL ,
                            lang VARCHAR(5) NOT NULL ,
                            searchterm VARCHAR(255) NOT NULL ,
                            searchdate TIMESTAMP,
                            PRIMARY KEY (id, lang))";
                break;
        }
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.enableWysiwygEditor', 'true')";
    }
    
    //
    // UPDATES FROM 2.5.0-beta
    //
    if (version_compare($version, '2.5.0-beta', '<')) {
        $query[] = "CREATE TABLE ".SQLPREFIX."faqstopwords (
                    id INTEGER NOT NULL,
                    lang VARCHAR(5) NOT NULL,
                    stopword VARCHAR(64) NOT NULL,
                    PRIMARY KEY (id, lang))";
        
        // Add stopwords list
        require 'stopwords.sql.php';

        switch($DB['type']) {
            case 'sqlite':
                $query[] = "BEGIN TRANSACTION";
                $query[] = "CREATE TEMPORARY TABLE ".SQLPREFIX."faqdata_temp (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_temp SELECT * FROM ".SQLPREFIX."faqdata";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata";
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    sticky INTEGER NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata SELECT id, lang, solution_id, revision_id, active, NULL,
                    keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start,
                    date_end FROM ".SQLPREFIX."faqdata_temp";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_temp";
                $query[] = "COMMIT";
                
                $query[] = "BEGIN TRANSACTION";
                $query[] = "CREATE TEMPORARY TABLE ".SQLPREFIX."faqdata_revisions_temp (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_revisions_temp SELECT * FROM ".SQLPREFIX."faqdata_revisions";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_revisions";
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata_revisions (
                    id int(11) NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int(11) NOT NULL,
                    revision_id int(11) NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    sticky INTEGER NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content longtext DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state VARCHAR(7) DEFAULT NULL,
                    links_check_date INT(11) DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_revisions SELECT id, lang, solution_id, revision_id, active, NULL,
                    keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start,
                    date_end FROM ".SQLPREFIX."faqdata_revisions_temp";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_revisions_temp";
                $query[] = "COMMIT";
            break;
            case 'pgsql':
                $query[] = "CREATE TEMPORARY TABLE ".SQLPREFIX."faqdata_temp (
                    id SERIAL NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int4 NOT NULL,
                    revision_id int4 NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content text DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state varchar(7) DEFAULT NULL,
                    links_check_date int4 DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_temp SELECT * FROM ".SQLPREFIX."faqdata";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata";
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata (
                    id SERIAL NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int4 NOT NULL,
                    revision_id int4 NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    sticky INTEGER NOT NULL DEFAULT 0,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content text DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) NOT NULL default 'y',
                    datum varchar(15) NOT NULL,
                    links_state varchar(7) DEFAULT NULL,
                    links_check_date int4 DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata SELECT id, lang, solution_id, revision_id, active, 0,
                    keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start,
                    date_end FROM ".SQLPREFIX."faqdata_temp";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_temp";
                $query[] = "SELECT setval('".SQLPREFIX."faqdata_id_seq', (SELECT MAX(id) FROM ".SQLPREFIX."faqdata)+1)";

                
                $query[] = "CREATE TEMPORARY TABLE ".SQLPREFIX."faqdata_revisions_temp (
                    id SERIAL NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int4 NOT NULL,
                    revision_id int4 NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content text DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) default 'y',
                    datum varchar(15) NOT NULL,
                    links_state varchar(7) DEFAULT NULL,
                    links_check_date int4 DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang, solution_id, revision_id))";

                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_revisions_temp SELECT * FROM ".SQLPREFIX."faqdata_revisions";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_revisions";
                $query[] = "CREATE TABLE ".SQLPREFIX."faqdata_revisions (
                    id SERIAL NOT NULL,
                    lang varchar(5) NOT NULL,
                    solution_id int4 NOT NULL,
                    revision_id int4 NOT NULL DEFAULT 0,
                    active char(3) NOT NULL,
                    sticky INTEGER NOT NULL  DEFAULT 0,
                    keywords text DEFAULT NULL,
                    thema text NOT NULL,
                    content text DEFAULT NULL,
                    author varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    comment char(1) default 'y',
                    datum varchar(15) NOT NULL,
                    links_state varchar(7) DEFAULT NULL,
                    links_check_date int4 DEFAULT 0 NOT NULL,
                    date_start varchar(14) NOT NULL DEFAULT '00000000000000',
                    date_end varchar(14) NOT NULL DEFAULT '99991231235959',
                    PRIMARY KEY (id, lang, solution_id, revision_id))";
                $query[] = "INSERT INTO ".SQLPREFIX."faqdata_revisions SELECT id, lang, solution_id, revision_id, active, 0,
                    keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start,
                    date_end FROM ".SQLPREFIX."faqdata_revisions_temp";
                $query[] = "DROP TABLE ".SQLPREFIX."faqdata_revisions_temp";
                $query[] = "SELECT setval('".SQLPREFIX."faqdata_revisions_id_seq', (SELECT MAX(id) FROM ".SQLPREFIX."faqdata_revisions)+1)";
            break;

            default:
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata ADD sticky INTEGER DEFAULT 0 NOT NULL AFTER active";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions ADD sticky INTEGER DEFAULT 0 NOT NULL AFTER active";
                break;
        }
    }
    
    //
    // UPDATES FROM 2.5.0-RC
    //
    if (version_compare($version, '2.5.0-RC', '<')) {
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (30, 'addtranslation', 'Right to add translation', 1, 1)"; 
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (31, 'edittranslation', 'Right to edit translation', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (32, 'deltranslation', 'Right to delete translation', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 30)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 31)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 32)";
    }

    //
    // UPDATES FROM 2.5.0-RC3
    //
    if(version_compare($version, '2.5.0-RC3', '<')) {
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES 
            (33, 'approverec', 'Right to approve records', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 33)";
        
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('main.attachmentsPath', 'attachments')";
    }
    
    //
    // UPDATES FROM 2.5.1
    //
    if (version_compare($version, '2.5.1', '<')) {
        // Truncate table and re-import all stopwords with the new Lithuanian ones
        $query[] = "DELETE FROM ".SQLPREFIX."faqstopwords";
        require 'stopwords.sql.php';
    }
    
    //
    // UPDATES FROM 2.5.2
    if (version_compare($version, '2.5.3', '<')) {
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'spam.enableCaptchaCode' WHERE config_name = 'spam.enableCatpchaCode'";
    }
    
    //
    // UPDATES FROM 2.6.0-alpha
    //
    if (version_compare($version, '2.6.0-alpha', '<')) {
        
        require '../lang/' . PMF_Configuration::getInstance()->get('main.language');
        
        if (isset($PMF_LANG['metaCharset']) && strtolower($PMF_LANG['metaCharset']) != 'utf-8') {
            // UTF-8 Migration
            switch($DB['type']) {
            case 'mysql':
                include 'mysql.utf8migration.php';
                break;
                
            case 'mysqli':
                include 'mysqli.utf8migration.php';
                break;
                
            default:
                print '<p>Please read <a target="_blank" href="../docs/documentation.en.html">documenation</a> about migration to UTF-8.</p>';
                break; 
            }
        }
        
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.enableUpdate', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.useSslForLogins', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.currentApiVersion', '" . APIVERSION . "')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.templateSet', 'default')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.numberSearchTerms', '10')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.orderingPopularFaqs', 'visits')";
        /**
         * We did check in the first and second steps,
         * if the $templateDir and its contents are writable,
         * so now lets just backup existing templates
         */
        $templateBackupDir = "$templateDir/backup";
        while (file_exists($templateBackupDir)) {
            $templateBackupDir = $templateBackupDir . mt_rand();
        }

        if (!mkdir($templateBackupDir, 0777)) {
            die("Couldn't create the templates backup dir.");
        }
        
        foreach (new DirectoryIterator($templateDir) as $item) {
            if ($item->isFile() && $item->isWritable()) {
                rename("$templateDir/{$item->getFilename()}", "$templateBackupDir/{$item->getFilename()}");
            }
        }
        
        /**
         * Attachments stuff
         */
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.attachmentsStorageType', '0')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.enableAttachmentEncryption', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.defaultAttachmentEncKey', '')";
        switch($DB['type']) {
            case 'pgsql':
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqattachment (
                    id SERIAL NOT NULL,
                    record_id int4 NOT NULL,
                    record_lang varchar(5) NOT NULL,
                    real_hash char(32) NOT NULL,
                    virtual_hash char(32) NOT NULL,
                    password_hash char(40) NULL,
                    filename varchar(255) NOT NULL,
                    filesize int NOT NULL,
                    encrypted int NOT NULL DEFAULT FALSE,
                    mime_type varchar(255) NULL,
                    PRIMARY KEY (id))";
                
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqattachment_file (
                    virtual_hash char(32) NOT NULL,
                    contents bytea,
                    PRIMARY KEY (virtual_hash))";
                break;
                
            case 'mysqli':
            case 'mysql':
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqattachment (
                    id int(11) NOT NULL,
                    record_id int(11) NOT NULL,
                    record_lang varchar(5) NOT NULL,
                    real_hash char(32) NOT NULL,
                    virtual_hash char(32) NOT NULL,
                    password_hash char(40) NULL,
                    filename varchar(255) NOT NULL,
                    filesize int NOT NULL,
                    encrypted tinyint NOT NULL DEFAULT 0,
                    mime_type varchar(255) NULL,
                    PRIMARY KEY (id))";
                
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqattachment_file (
                    virtual_hash char(32) NOT NULL,
                    contents blob NOT NULL,
                    PRIMARY KEY (virtual_hash))";
                break;
                
            default:
                /**
                 * Just try standard SQL and hope for the best
                 */
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqattachment (
                    id int NOT NULL,
                    record_id int NOT NULL,
                    record_lang varchar(5) NOT NULL,
                    hash char(33) NOT NULL,
                    filename varchar(255) NOT NULL,
                    file_contents blob,
                    encrypted int,
                    PRIMARY KEY (id))";
                break;
        }

    }

    //
    // UPDATES FROM 2.6.0-RC
    //
    if (version_compare($version, '2.6.0-RC', '<')) {
        
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.optionalMailAddress', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.useAjaxSearchOnStartpage', 'false')";
    }
    
    // Perform the queries for updating/migrating the database from 2.x
    if (isset($query)) {
        print '<div class="center">';
        ob_flush();
        flush();
        $count = 0;
        foreach ($query as $key => $current_query) {
            $result = @$db->query($current_query);
            print '.';
            if (!($key % 100)) {
                print '<br />';
            }
            if (!$result) {
                print "</div>";
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            usleep(10000);
            $count++;
            if (!($count % 10)) {
                ob_flush();
                flush();
            }
        }
        ob_flush();
        flush();
        print "</div>";
    }

    // Clear the array with the queries
    unset($query);
    $query = array();

    // Always the last step: Update version number
    if (version_compare($version, NEWVERSION, '<')) {
        $oPMFConf = PMF_Configuration::getInstance();
        $oPMFConf->update(array('main.currentVersion' => NEWVERSION));
    }

    // optimize tables
    switch ($DB["type"]) {
        case 'mssql':
        case 'sybase':
            // Get all table names
            $db->getTableNames(SQLPREFIX);
            foreach ($db->tableNames as $tableName) {
                $query[] = 'DBCC DBREINDEX ('.$tableName.')';
            }
            break;
        case 'mysql':
        case 'mysqli':
            // Get all table names
            $db->getTableNames(SQLPREFIX);
            foreach ($db->tableNames as $tableName) {
                $query[] = 'OPTIMIZE TABLE '.$tableName;
            }
            break;
        case 'pgsql':
            $query[] = "VACUUM ANALYZE;";
            break;
    }

    // Perform the queries for optimizing the database
    if (isset($query)) {
        print '<div class="center">';
        foreach ($query as $current_query) {
            $result = $db->query($current_query);
            printf('<span title="%s">.</span>', $current_query);
            if (!$result) {
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            usleep(10000);
        }
        print "</div>";
    }

    print "</p>\n";

    print '<p class="center">The database was updated successfully.</p>';
    print '<p class="center"><a href="../index.php">phpMyFAQ</a></p>';
    foreach (glob(PMF_ROOT_DIR.'/config/*.bak.php') as $filename) {
        if (!@unlink($filename)) {
            print "<p class=\"center\">Please manually remove the backup file '".$filename."'.</p>\n";
        }
    }

    // Remove 'scripts' folder: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/scripts') && is_dir(PMF_ROOT_DIR.'/scripts')) {
        @rmdir(PMF_ROOT_DIR.'/scripts');
    }
    // Remove 'phpmyfaq.spec' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR.'/phpmyfaq.spec')) {
        @unlink(PMF_ROOT_DIR.'/phpmyfaq.spec');
    }
    // Remove 'setup.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/setup.php')) {
        print "<p class=\"center\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/update.php')) {
        print "<p class=\"center\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }

    HTMLFooter();
}
