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
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-01-10
 */

define('NEWVERSION', '2.7.0-RC1');
define('APIVERSION', 1);
define('MINIMUM_PHP_VERSION', '5.2.3');
define('COPYRIGHT', '&copy; 2001-2011 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

?>
<!doctype html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]> <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]> <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>phpMyFAQ <?php print NEWVERSION; ?> Update</title>

    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print NEWVERSION; ?>">
    <meta name="copyright" content="(c) 2001-2011 phpMyFAQ Team">

    <link rel="shortcut icon" href="../template/default/favicon.ico">
    <link rel="apple-touch-icon" href="../template/default/apple-touch-icon.png">
    <link rel="stylesheet" href="css/setup.css?v=1">
    <script type="text/javascript" src="../inc/js/jquery.min.js"></script>
</head>
<body>

<header id="header">
    <h1>
        <h1>phpMyFAQ <?php print NEWVERSION; ?>  Update</h1>
    </h1>
</header>

<nav>
    <ul>
        <li><a target="_blank" href="http://www.phpmyfaq.de//documentation.php">Documentation</a></li>
        <li><a target="_blank" href="http://www.phpmyfaq.de//support.php">Support</a></li>
        <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
        <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
    </ul>
</nav>

<div id="content">
    <div id="mainContent">
<?php

if (version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '<')) {
    printf("<p>Sorry, but you need PHP %s or later!</p>\n", MINIMUM_PHP_VERSION);
    HTMLFooter();
    die();
}

require_once PMF_ROOT_DIR . '/inc/autoLoader.php';
require_once PMF_ROOT_DIR . '/config/constants.php';

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
    printf('</div></div><footer><div><p id="copyrightnote">%s</p><div></footer></body></html>', COPYRIGHT);
}

if (!is_readable(PMF_ROOT_DIR.'/inc/data.php') && !is_readable(PMF_ROOT_DIR.'/config/database.php')) {
    print '<p class="error">It seems you never run a version of phpMyFAQ.<br />' .
          'Please use the <a href="setup.php">install script</a>.</p>';
    HTMLFooter();
    die();
}

if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    require PMF_ROOT_DIR . '/inc/data.php'; // before 2.6.0-alpha
} else {
    require PMF_ROOT_DIR . '/config/database.php'; // after 2.6.0-alpha
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
            <input name="version" type="hidden" value="<?php print $version; ?>"/>
            <fieldset>
                <legend>
                    <strong>
                        phpMyFAQ <?php print NEWVERSION; ?> Update (Step 1 of 4)
                    </strong>
                </legend>

                <p>This update will work <strong>only</strong> for the following versions</p>
                <ul type="square">
                    <li>phpMyFAQ 2.0.x</li>
                    <li>phpMyFAQ 2.5.x</li>
                    <li>phpMyFAQ 2.6.x</li>
                    <li>phpMyFAQ 2.7.x</li>
                </ul>

                <p>This update <strong>will not</strong> work for the following versions.</p>
                <ul type="square">
                    <li>phpMyFAQ 0.x</li>
                    <li>phpMyFAQ 1.x</li>
                </ul>
                <p class="hint">
                    <strong>Please make a full backup of your SQL tables before running this update.</strong>
                </p>
                <?php
                if (version_compare($version, '2.6.0-alpha', '<') && !is_writeable($templateDir)) {
                    printf('<p class="error"><strong>Please make the dir %s and its contents writeable (777 on Linux/UNIX).</strong></p>',
                        $templateDir);
                }
                if (version_compare($version, '2.0.0', '>')) {
                    printf('<p class="success">Your current phpMyFAQ version: %s</p>',
                        $version);
                } else {
                    printf('<p class="error">Your current phpMyFAQ version: %s</p>',
                        $version);
                }
                ?>

                <p>
                    <input class="submit" type="submit" value="Go to step 2 of 4" />
                </p>
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
        <fieldset>
            <legend><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 2 of 4)</strong></legend>
            <p>A backup of your database configuration file has been made.</p>
            <p>
                <input class="submit" type="submit" value="Go to step 3 of 4" />
            </p>
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
        <fieldset>
            <legend><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 3 of 4)</strong></legend>
            <p>The configuration will be updated after the next step.</p>
            <p>
                <input class="submit" type="submit" value="Go to step 4 of 4" />
            </p>
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
        
        $query[] = "INSERT INTO ".SQLPREFIX."faqconfig VALUES ('records.attachmentsPath', 'attachments')";
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
                print '<p class="hint">Please read <a target="_blank" href="../docs/documentation.en.html">documenation</a> about migration to UTF-8.</p>';
                break; 
            }
        }
        
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.enableUpdate', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('security.useSslForLogins', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.currentApiVersion', '" . APIVERSION . "')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.templateSet', 'default')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.numberSearchTerms', '10')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('records.orderingPopularFaqs', 'visits')";
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
            die('<p class="error">Couldn\'t create the templates backup directory.</p>');
            HTMLFooter();
        }
        
        foreach (new DirectoryIterator($templateDir) as $item) {
            if ($item->isFile() && $item->isWritable()) {
                rename("$templateDir/{$item->getFilename()}", "$templateBackupDir/{$item->getFilename()}");
            }
        }
        
        /**
         * Attachments stuff
         */
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('records.attachmentsStorageType', '0')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('records.enableAttachmentEncryption', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('records.defaultAttachmentEncKey', '')";
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

    //
    // UPDATES FROM 2.6.99
    //
    if (version_compare($version, '2.6.99', '<')) {
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('search.relevance', 'thema,content,keywords')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('search.enableRelevance', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.enableGoogleTranslation', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.googleTranslationKey', 'INSERT-YOUR-KEY')";
    }

    //
    // UPDATES FROM 2.7.0-alpha
    //
    if (version_compare($version, '2.7.0-alpha', '<')) {
        // Add new config values
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('socialnetworks.enableTwitterSupport', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('socialnetworks.twitterConsumerKey', '')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('socialnetworks.twitterConsumerSecret', '')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('socialnetworks.twitterAccessTokenKey', '')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('socialnetworks.twitterAccessTokenSecret', '')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('socialnetworks.enableFacebookSupport', 'false')";

        // Migrate faqquestion table to new structure

        switch($DB['type']) {

            case 'ibase':
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions ALTER ask_username TO username";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions ALTER ask_usermail TO email";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions ALTER ask_rubrik TO category_id";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions ALTER ask_content TO question";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions ALTER ask_date TO created";
                break;

            case 'ibm_db2':
            case 'pgsql':
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_username TO username";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_usermail TO email";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_rubrik TO category_id";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_content TO question";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_date TO created";
                break;

            case 'mssql':
            case 'sqlsrv':
            case 'sybase':
                $query[] = "EXEC sp_RENAME '" . SQLPREFIX . "faqquestions.ask_username', 'username', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . SQLPREFIX . "faqquestions.ask_usermail', 'email', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . SQLPREFIX . "faqquestions.ask_rubrik', 'category_id', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . SQLPREFIX . "faqquestions.ask_content', 'question', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '" . SQLPREFIX . "faqquestions.ask_date', 'created', 'COLUMN'";
                break;

            case 'mysql':
            case 'mysqli':
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions CHANGE ask_username username VARCHAR(100) NOT NULL";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions CHANGE ask_usermail email VARCHAR(100) NOT NULL";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions CHANGE ask_rubrik category_id INT(11) NOT NULL";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions CHANGE ask_content question TEXT NOT NULL";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions CHANGE ask_date created VARCHAR(20) NOT NULL";
                break;
           
            case 'sqlite':
                $query[] = "BEGIN TRANSACTION";
                $query[] = "CREATE TEMPORORY TABLE " . SQLPREFIX . "faqquestions_temp (
                                id int(11) NOT NULL,
                                username varchar(100) NOT NULL,
                                email varchar(100) NOT NULL,
                                category_id int(11) NOT NULL,
                                question text NOT NULL,
                                created varchar(20) NOT NULL,
                                is_visible char(1) default 'Y',
                                PRIMARY KEY (id))";
                $query[] = "INSERT INTO " . SQLPREFIX . "faqquestions_temp SELECT * FROM " . SQLPREFIX . "faqquestions";
                $query[] = "DROP TABLE " . SQLPREFIX . "faqquestions";
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqquestions (
                                id int(11) NOT NULL,
                                username varchar(100) NOT NULL,
                                email varchar(100) NOT NULL,
                                category_id int(11) NOT NULL,
                                question text NOT NULL,
                                created varchar(20) NOT NULL,
                                is_visible char(1) default 'Y',
                                PRIMARY KEY (id))";
                $query[] = "INSERT INTO " . SQLPREFIX . "faqquestions SELECT * FROM " . SQLPREFIX . "faqquestions_temp";
                $query[] = "DROP TABLE " . SQLPREFIX . "faqquestions_temp";
                $query[] = "COMMIT";
                break;
        }


        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES
            (34, 'addattachment', 'Right to add attachments', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES
            (35, 'editattachment', 'Right to edit attachments', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES
            (36, 'delattachment', 'Right to delete attachments', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES
            (37, 'dlattachment', 'Right to download attachments', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 34)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 35)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 36)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 37)";
    }

    //
    // UPDATES FROM 2.7.0-alpha2
    //
    if (version_compare($version, '2.7.0-alpha2', '<')) {
        $query[] = "INSERT INTO ".SQLPREFIX."faqright (right_id, name, description, for_users, for_groups) VALUES
            (38, 'reports', 'Right to generate reports', 1, 1)";
        $query[] = "INSERT INTO ".SQLPREFIX."faquser_right (user_id, right_id) VALUES (1, 38)";
    }

    //
    // UPDATES FROM 2.7.0-beta
    //
    if (version_compare($version, '2.7.0-beta', '<')) {
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'search.numberSearchTerms'
            WHERE config_name = 'main.numberSearchTerms'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'search.useAjaxSearchOnStartpage'
            WHERE config_name = 'main.useAjaxSearchOnStartpage'";
    }

    //
    // UPDATES FROM 2.7.0-beta2
    //
    if (version_compare($version, '2.7.0-beta2', '<')) {
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('security.ssoSupport', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('security.ssoLogoutRedirect', '')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.dateFormat', 'Y-m-d H:i')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('security.enableLoginOnly', 'false')";
    }

    //
    // UPDATES FROM 2.7.0-RC
    //
    if (version_compare($version, '2.7.0-RC', '<')) {
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.numberOfRecordsPerPage'
            WHERE config_name = 'main.numberOfRecordsPerPage'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.numberOfShownNewsEntries'
            WHERE config_name = 'main.numberOfShownNewsEntries'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.orderingPopularFaqs'
            WHERE config_name = 'main.orderingPopularFaqs'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.disableAttachments'
            WHERE config_name = 'main.disableAttachments'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.maxAttachmentSize'
            WHERE config_name = 'main.maxAttachmentSize'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.attachmentsPath'
            WHERE config_name = 'main.attachmentsPath'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.attachmentsStorageType'
            WHERE config_name = 'main.attachmentsStorageType'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.enableAttachmentEncryption'
            WHERE config_name = 'main.enableAttachmentEncryption'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'records.defaultAttachmentEncKey'
            WHERE config_name = 'main.defaultAttachmentEncKey'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.permLevel'
            WHERE config_name = 'main.permLevel'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.ipCheck'
            WHERE config_name = 'main.ipCheck'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.enableLoginOnly'
            WHERE config_name = 'main.enableLoginOnly'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.ldapSupport'
            WHERE config_name = 'main.ldapSupport'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.bannedIPs'
            WHERE config_name = 'main.bannedIPs'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.ssoSupport'
            WHERE config_name = 'main.ssoSupport'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.ssoLogoutRedirect'
            WHERE config_name = 'main.ssoLogoutRedirect'";
        $query[] = "UPDATE ".SQLPREFIX."faqconfig SET config_name = 'security.useSslForLogins'
            WHERE config_name = 'main.useSslForLogins'";
    }


    // Perform the queries for updating/migrating the database from 2.x
    if (isset($query)) {
        print '<div class="center">';
        $count = 0;
        foreach ($query as $key => $current_query) {
            $result = @$db->query($current_query);
            print '.';
            if (!($key % 100)) {
                print '<br />';
            }
            if (!$result) {
                print "</div>";
                print '<p class="error"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
                us a <a href=\"http://bugs.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
                printf('<p class="error"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($each_query[1]));
                HTMLFooter();
                die();
            }
            usleep(10000);
            $count++;
            if (!($count % 10)) {
                ob_flush();
            }
        }
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
                print '<p class="error"><strong>Error:</strong> Please install your version of phpMyFAQ once again or send
                us a <a href=\"http://www.phpmyfaq.de\" target=\"_blank\">bug report</a>.</p>';
                printf('<p class="error"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($current_query));
                HTMLFooter();
                die();
            }
            usleep(10000);
        }
        print "</div>";
    }

    print "</p>\n";

    print '<p class="success">The database was updated successfully.</p>';
    print '<p>Back to your <a href="../index.php">phpMyFAQ installation.</a></p>';
    foreach (glob(PMF_ROOT_DIR.'/config/*.bak.php') as $filename) {
        if (!@unlink($filename)) {
            print "<p class=\"hint\">Please manually remove the backup file '".$filename."'.</p>\n";
        }
    }

    // Remove 'scripts' folder: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR . '/scripts') && is_dir(PMF_ROOT_DIR . '/scripts')) {
        @rmdir(PMF_ROOT_DIR . '/scripts');
    } else {
        print "<p class=\"hint\">Please delete the folder <em>./scripts</em> manually.</p>\n";
    }
    // Remove 'phpmyfaq.spec' file: no need of prompt anything to the user
    if (file_exists(PMF_ROOT_DIR . '/phpmyfaq.spec')) {
        @unlink(PMF_ROOT_DIR . '/phpmyfaq.spec');
    } else {
        print "<p class=\"hint\">Please delete the file <em>./phpmyfaq.spec</em> manually.</p>\n";
    }
    // Remove 'setup.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']) . '/setup.php')) {
        print "<p class=\"success\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"hint\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']) . '/update.php')) {
        print "<p class=\"success\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"hint\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }

    HTMLFooter();
}
