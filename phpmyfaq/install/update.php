<?php
/**
 * Main update script
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Melchinger <t.melchinger@uni.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-01-10
 */

define('COPYRIGHT', '&copy; 2001-2012 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(__DIR__));
define('IS_VALID_PHPMYFAQ', null);

if (! defined('DEBUG')) {
    define('DEBUG', true);
}

if ((@ini_get('safe_mode') != 'On' || @ini_get('safe_mode') !== 1)) {
    set_time_limit(0);
}

require_once PMF_ROOT_DIR . '/inc/Autoloader.php';
require_once PMF_ROOT_DIR . '/config/constants.php';

$step        = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version     = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query       = array();
$templateDir = '../template';

if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    require PMF_ROOT_DIR . '/inc/data.php'; // before 2.6.0-alpha
} else {
    require PMF_ROOT_DIR . '/config/database.php'; // after 2.6.0-alpha
}
require PMF_ROOT_DIR . '/inc/functions.php';

define('SQLPREFIX', $DB['prefix']);
$db = PMF_Db::factory($DB["type"]);
$db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);

$faqConfig = new PMF_Configuration($db);

/**
 * Print out the HTML Footer
 *
 * @return void
 */
function HTMLFooter()
{
    printf('</div></div></section><footer><div class="container"><p class="pull-right">%s</p><div></footer></body></html>', COPYRIGHT);
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

    <title>phpMyFAQ <?php print PMF_System::getVersion(); ?> Update</title>

    <meta name="viewport" content="width=device-width;">
    <meta name="application-name" content="phpMyFAQ <?php print PMF_System::getVersion(); ?>">
    <meta name="copyright" content="(c) 2001-<?php print date('Y'); ?> phpMyFAQ Team">

    <link rel="stylesheet" href="../template/default/css/style.css?v=1">

    <script src="../js/libs/modernizr.min.js"></script>
    <script src="../js/libs/jquery.min.js"></script>

    <link rel="shortcut icon" href="../template/default/favicon.ico">
    <link rel="apple-touch-icon" href="../template/default/apple-touch-icon.png">

</head>
<body>

<!--[if lt IE 8 ]>
<div class="internet-explorer-error">
    Do you know that your Internet Explorer is out of date?<br/>
    Please use Internet Explorer 8+, Mozilla Firefox 4+, Google Chrome, Apple Safari 5+ or Opera 11+
</div>
<![endif]-->

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <nav class="nav-collapse">
                <ul class="nav">
                    <li><a target="_blank" href="http://www.phpmyfaq.de/documentation.php">Documentation</a></li>
                    <li><a target="_blank" href="http://www.phpmyfaq.de/support.php">Support</a></li>
                    <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
                    <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
                    <li class="divider-vertical"></li>
                    <li><a href="../">Back to your FAQ</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<section id="main">
    <div class="container">
        <div class="row" style="padding-left: 20px;">
            <div class="hero-unit hello-phpmyfaq" style="text-align: center; height: 55px;">
                <h1>phpMyFAQ <?php print PMF_System::getVersion(); ?> Update</h1>
            </div>
        </div>
        <div class="row" style="padding-left: 20px;">
            <div class="span1">&nbsp;</div>
            <div class="span10">
<?php

if (version_compare(PHP_VERSION, PMF_System::VERSION_MINIMUM_PHP, '<')) {
    printf('<p class="alert alert-error">Sorry, but you need PHP %s or later!</p>', PMF_System::VERSION_MINIMUM_PHP);
    HTMLFooter();
    die();
}

if (! is_readable(PMF_ROOT_DIR.'/inc/data.php') && ! is_readable(PMF_ROOT_DIR.'/config/database.php')) {
    print '<p class="alert alert-error">It seems you never run a version of phpMyFAQ.<br />' .
          'Please use the <a href="setup.php">install script</a>.</p>';
    HTMLFooter();
    die();
}

/**************************** STEP 1 OF 3 ***************************/
if ($step == 1) {

    $version = $faqConfig->get('main.currentVersion');
?>
        <form action="update.php?step=2" method="post">
            <input name="version" type="hidden" value="<?php print $version; ?>"/>
            <fieldset>
                <legend>
                    <strong>
                        phpMyFAQ <?php print PMF_System::getVersion(); ?> Update (Step 1 of 3)
                    </strong>
                </legend>

                <p class="alert alert-info">
                    <strong>
                        Please create a full backup of your database, your templates, attachments and uploaded images
                        before running this update.
                    </strong>
                </p>

                <p>This update script will work <strong>only</strong> for the following versions:</p>
                <ul type="square">
                    <li>phpMyFAQ 2.5.x (out of support since mid of 2010)</li>
                    <li>phpMyFAQ 2.6.x (out of support since end of 2011)</li>
                    <li>phpMyFAQ 2.7.x</li>
                    <li>phpMyFAQ 2.8.x</li>
                </ul>

                <p>This update script <strong>will not</strong> work for the following versions:</p>
                <ul type="square">
                    <li>phpMyFAQ 0.x</li>
                    <li>phpMyFAQ 1.x</li>
                    <li>phpMyFAQ 2.0.x</li>
                </ul>
                <?php
                if (version_compare($version, '2.6.0-alpha', '<') && !is_writeable($templateDir)) {
                    printf(
                        '<p class="alert alert-error"><strong>Please change the directory %s and its contents ' .
                        'writable (777 on Linux/UNIX).</strong></p>',
                        $templateDir
                    );
                }
                if (version_compare($version, '2.5.0', '>')) {
                    printf(
                        '<p class="alert alert-success">Your current phpMyFAQ version: %s</p>',
                        $version
                    );
                } else {
                    printf(
                        '<p class="alert alert-error">Your current phpMyFAQ version: %s</p>',
                        $version
                    );
                    print '<p>Please update to the latest phpMyFAQ 2.7 version first.</p>';
                }
                if ('hash' !== PMF_ENCRYPTION_TYPE) {
                    printf(
                        '<p class="alert alert-info">Your passwords are currently encoded with a %s() method.</p>',
                        PMF_ENCRYPTION_TYPE
                    );
                }
                ?>

                <p class="alert alert-danger">
                    Dude, this is an early alpha version. Please don't update your production version!
                </p>

                <p style="text-align: center">
                    <input class="btn-primary btn-large" type="submit" value="Go to step 2 of 3" />
                </p>
            </fieldset>
        </form>
<?php
    HTMLFooter();
}

/**************************** STEP 2 OF 3 ***************************/
if ($step == 2) {

    $checkDatabaseSetupFile = $checkLdapSetupFile = $checkTemplateDirectory = false;
    
    // First backup old inc/data.php, then backup new config/bak.database.php and copy inc/data.php 
    // to config/database.php
    if (file_exists(PMF_ROOT_DIR . '/inc/data.php')) {
        if (!copy(PMF_ROOT_DIR . '/inc/data.php', PMF_ROOT_DIR . '/config/database.bak.php') ||
            !copy(PMF_ROOT_DIR . '/inc/data.php', PMF_ROOT_DIR . '/config/database.php')) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> The backup file ../config/database.bak.php " .
                  "could not be written. Please correct this!</p>";
        } else {
            $checkDatabaseSetupFile = true;
        }
    }
    
    // The backup an existing config/database.php
    if (file_exists(PMF_ROOT_DIR . '/config/database.php')) {
        if (!copy(PMF_ROOT_DIR . '/config/database.php', PMF_ROOT_DIR . '/config/database.bak.php')) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> The backup file ../config/database.bak.php " .
                  "could not be written. Please correct this!</p>";
        } else {
            $checkDatabaseSetupFile = true;
        }
    }
    
    // Now backup and move LDAP setup if available
    if (file_exists(PMF_ROOT_DIR . '/inc/dataldap.php')) {
        if (!copy(PMF_ROOT_DIR . '/inc/dataldap.php', PMF_ROOT_DIR . '/config/ldap.bak.php') ||
            !copy(PMF_ROOT_DIR . '/inc/dataldap.php', PMF_ROOT_DIR . '/config/ldap.php')) {
            print "<p class=\"alert alert-error\"><strong>Error:</strong> The backup file ../config/ldap.bak.php " .
                  "could not be written. Please correct this!</p>";
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
            printf("<p class=\"alert alert-error\"><strong>The directory %s isn't writable.</strong></p>\n", $templateDir);
        }
        if (!empty($notWritableFiles)) {
            foreach ($notWritableFiles as $item) {
                printf("<p class=\"alert alert-error\"><strong>The file %s isn't writable.</strong></p>\n", $item);
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
            <legend><strong>phpMyFAQ <?php print PMF_System::getVersion(); ?> Update (Step 2 of 3)</strong></legend>
            <p>A backup of your database configuration file has been made.</p>
            <p>The configuration will be updated after the next step.</p>
            <p style="text-align: center;">
                <input class="btn-primary btn-large" type="submit" value="Go to step 3 of 3" />
            </p>
        </fieldset>
        </form>
<?php
        HTMLFooter();
    } else {
        print "<p class=\"alert alert-error\"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>\n";
        HTMLFooter();
        die();
    }
}

/**************************** STEP 3 OF 3 ***************************/
if ($step == 3) {
    
    require_once PMF_ROOT_DIR . '/inc/Configuration.php';
    require_once PMF_ROOT_DIR . '/inc/Db.php';
    require_once PMF_ROOT_DIR . '/inc/DB/Driver.php';
    require_once PMF_ROOT_DIR . '/inc/Link.php';
    
    $images = array();

    //
    // UPDATES FROM 2.5.1
    //
    if (version_compare($version, '2.5.1', '<')) {
        // Truncate table and re-import all stopwords with the new Lithuanian ones
        $query[] = "DELETE FROM ". SQLPREFIX . "faqstopwords";
        require 'stopwords.sql.php';
    }
    
    //
    // UPDATES FROM 2.5.2
    if (version_compare($version, '2.5.3', '<')) {
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'spam.enableCaptchaCode'
            WHERE config_name = 'spam.enableCatpchaCode'";
    }
    
    //
    // UPDATES FROM 2.6.0-alpha
    //
    if (version_compare($version, '2.6.0-alpha', '<')) {
        
        require '../lang/' . $faqConfig->get('main.language');
        
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
                print '<p class="hint">Please read <a target="_blank" href="../docs/documentation.en.html">' .
                      'documenation</a> about migration to UTF-8.</p>';
                break; 
            }
        }
        
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.enableUpdate', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('security.useSslForLogins', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('main.currentApiVersion', '" . PMF_System::getApiVersion() . "')";
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

            case 'pgsql':
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_username TO username";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_usermail TO email";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_rubrik TO category_id";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_content TO question";
                $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions RENAME COLUMN ask_date TO created";
                break;

            case 'mssql':
            case 'sqlsrv':
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
                $query[] = "CREATE TEMPORARY TABLE " . SQLPREFIX . "faqquestions_temp (
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


        $query[] = "INSERT INTO ". SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (34, 'addattachment', 'Right to add attachments', 1, 1)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (35, 'editattachment', 'Right to edit attachments', 1, 1)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (36, 'delattachment', 'Right to delete attachments', 1, 1)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (37, 'dlattachment', 'Right to download attachments', 1, 1)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 34)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 35)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 36)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 37)";
    }

    //
    // UPDATES FROM 2.7.0-alpha2
    //
    if (version_compare($version, '2.7.0-alpha2', '<')) {
        $query[] = "INSERT INTO ". SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (38, 'reports', 'Right to generate reports', 1, 1)";
        $query[] = "INSERT INTO ". SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 38)";
    }

    //
    // UPDATES FROM 2.7.0-beta
    //
    if (version_compare($version, '2.7.0-beta', '<')) {
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'search.numberSearchTerms'
            WHERE config_name = 'main.numberSearchTerms'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'search.useAjaxSearchOnStartpage'
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
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.numberOfRecordsPerPage'
            WHERE config_name = 'main.numberOfRecordsPerPage'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.numberOfShownNewsEntries'
            WHERE config_name = 'main.numberOfShownNewsEntries'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.orderingPopularFaqs'
            WHERE config_name = 'main.orderingPopularFaqs'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.disableAttachments'
            WHERE config_name = 'main.disableAttachments'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.maxAttachmentSize'
            WHERE config_name = 'main.maxAttachmentSize'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.attachmentsPath'
            WHERE config_name = 'main.attachmentsPath'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.attachmentsStorageType'
            WHERE config_name = 'main.attachmentsStorageType'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.enableAttachmentEncryption'
            WHERE config_name = 'main.enableAttachmentEncryption'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'records.defaultAttachmentEncKey'
            WHERE config_name = 'main.defaultAttachmentEncKey'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.permLevel'
            WHERE config_name = 'main.permLevel'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.ipCheck'
            WHERE config_name = 'main.ipCheck'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.enableLoginOnly'
            WHERE config_name = 'main.enableLoginOnly'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.ldapSupport'
            WHERE config_name = 'main.ldapSupport'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.bannedIPs'
            WHERE config_name = 'main.bannedIPs'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.ssoSupport'
            WHERE config_name = 'main.ssoSupport'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.ssoLogoutRedirect'
            WHERE config_name = 'main.ssoLogoutRedirect'";
        $query[] = "UPDATE ". SQLPREFIX . "faqconfig SET config_name = 'security.useSslForLogins'
            WHERE config_name = 'main.useSslForLogins'";
    }

    //
    // UPDATES FROM 2.7.1
    //
    if (version_compare($version, '2.7.1', '<')) {
        $query[] = "INSERT INTO ". SQLPREFIX . "faqconfig VALUES ('security.useSslOnly', 'false')";
    }

    //
    // UPDATES FROM 2.7.3
    //
    if (version_compare($version, '2.7.3', '<')) {
        $query[] = "DELETE FROM ".SQLPREFIX."faqright WHERE right_id = 18 AND right_id = 19";
        $query[] = "DELETE FROM ".SQLPREFIX."faquser_right WHERE right_id = 18 AND right_id = 19";
        $query[] = "DELETE FROM ".SQLPREFIX."faqgroup_right WHERE right_id = 18 AND right_id = 19";
    }

    //
    // UPDATES FROM 2.8.0-alpha
    //
    if (version_compare($version, '2.8.0-alpha', '<')) {

        $query[] = "INSERT INTO " . SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (39, 'addfaq', 'Right to add FAQs in frontend', 1, 1)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 39)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (40, 'addquestion', 'Right to add questions in frontend', 1, 1)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 40)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqright (right_id, name, description, for_users, for_groups) VALUES
            (41, 'addcomment', 'Right to add comments in frontend', 1, 1)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 41)";

        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('cache.varnishEnable', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('cache.varnishHost', '127.0.0.1')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('cache.varnishPort', '2000')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('cache.varnishSecret', '')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('cache.varnishTimeout', '500')";

        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('security.forcePasswordUpdate', 'true')";

        $query[] = "ALTER TABLE " . SQLPREFIX . "faqquestions ADD answer_id INT NOT NULL DEFAULT 0";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('records.enableCloseQuestion', 'false')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqconfig VALUES ('records.enableDeleteQuestion', 'false')";

        $query[] = "CREATE TEMPORARY TABLE " . SQLPREFIX . "faquserlogin_temp (
                                login varchar(128) NOT NULL,
                                pass varchar(80) NOT NULL,
                                PRIMARY KEY (login))";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquserlogin_temp SELECT * FROM " . SQLPREFIX . "faquserlogin";
        $query[] = "DROP TABLE " . SQLPREFIX . "faquserlogin";
        $query[] = "CREATE TABLE " . SQLPREFIX . "faquserlogin (
                                login varchar(128) NOT NULL,
                                pass varchar(80) NOT NULL,
                                PRIMARY KEY (login))";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquserlogin SELECT * FROM " . SQLPREFIX . "faquserlogin_temp";
        $query[] = "DROP TABLE " . SQLPREFIX . "faquserlogin_temp";
    }

    //
    // UPDATES FROM 2.8.0-alpha2
    //
    if (version_compare($version, '2.8.0-alpha2', '<')) {
        switch($DB['type']) {
            case 'pgsql':
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqinstances (
                    id int4 NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    modified TIMESTAMP NOT NULL,
                    PRIMARY KEY (id))";
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqinstances_config (
                    instance_id int4 NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name))";
                break;

            case 'mssql':
            case 'sqlsrv':
            case 'sqlite':
            case 'sqlite3':
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqinstances (
                    id INT(11) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created DATETIME NOT NULL,
                    modified DATETIME NOT NULL,
                    PRIMARY KEY (id))";
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqinstances_config (
                    instance_id INT(11) NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name))";
                break;

            case 'mysql':
            case 'mysqli':
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqinstances (
                    id INT(11) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created TIMESTAMP DEFAULT 0,
                    modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                $query[] = "CREATE TABLE " . SQLPREFIX . "faqinstances_config (
                    instance_id INT(11) NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                break;
        }

        $query[] = "INSERT INTO " . SQLPREFIX . "faqright (right_id, name, description) VALUES
            (42, 'editinstances', 'Right to edit multi-site instances')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 42)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqright (right_id, name, description) VALUES
            (43, 'addinstances', 'Right to add multi-site instances')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 43)";
        $query[] = "INSERT INTO " . SQLPREFIX . "faqright (right_id, name, description) VALUES
            (44, 'delinstances', 'Right to delete multi-site instances')";
        $query[] = "INSERT INTO " . SQLPREFIX . "faquser_right (user_id, right_id) VALUES (1, 44)";

        $query[] = "ALTER TABLE " . SQLPREFIX . "faquser ADD remember_me VARCHAR(150) NULL";
    }

    // Perform the queries for updating/migrating the database
    if (isset($query)) {
        print '<div class="center">';
        $count = 0;
        foreach ($query as $key => $executeQuery) {
            $result = @$db->query($executeQuery);
            print '.';
            if (!($key % 100)) {
                print '<br />';
            }
            if (!$result) {
                print "</div>";
                print '<p class="alert alert-error"><strong>Error:</strong> Please install your version of phpMyFAQ ' .
                      'once again or send us a <a href="http://bugs.phpmyfaq.de" target="_blank">bug report</a>.' .
                      '</p>';
                printf('<p class="alert alert-error"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
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

    //
    // 2nd UPDATES FROM 2.8.0-alpha2
    //
    if (version_compare($version, '2.8.0-alpha2', '<')) {

        $link = new PMF_Link(null, $faqConfig);

        $instanceData = array(
            'url'      => $link->getSystemUri($_SERVER['SCRIPT_NAME']),
            'instance' => $link->getSystemRelativeUri('install/update.php'),
            'comment'  => $faqConfig->get('main.titleFAQ')
        );
        $faqInstance = new PMF_Instance($faqConfig);
        $faqInstance->addInstance($instanceData);

        $faqInstanceMaster = new PMF_Instance_Master($faqConfig);
        $faqInstanceMaster->createMaster($faqInstance);
    }

    // Always the last step: Update version number
    if (version_compare($version, PMF_System::getVersion(), '<')) {
        $faqConfig->update(array('main.currentVersion' => PMF_System::getVersion()));
    }

    // optimize tables
    switch ($DB["type"]) {
        case 'mssql':
        case 'sqlsrv':
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
        foreach ($query as $executeQuery) {
            $result = $db->query($executeQuery);
            printf('<span title="%s">.</span>', $executeQuery);
            if (!$result) {
                print '<p class="alert alert-error"><strong>Error:</strong> Please install your version of phpMyFAQ once again ' .
                      'or send us a <a href="http://bugs.phpmyfaq.de" target="_blank">bug report</a>.</p>';
                printf('<p class="error"><strong>DB error:</strong> %s</p>', $db->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                HTMLFooter();
                die();
            }
            usleep(10000);
        }
        print "</div>";
    }

    print "</p>\n";

    print '<p class="alert alert-success">The database was updated successfully. Thank you very much for updating.</p>';
    print '<p>Back to your <a href="../index.php">phpMyFAQ installation</a> and have fun! :-)</p>';

    // Remove backup files
    foreach (glob(PMF_ROOT_DIR.'/config/*.bak.php') as $filename) {
        if (!@unlink($filename)) {
            printf("<p class=\"alert alert-info\">Please remove the backup file %s manually.</p>\n", $filename);
        }
    }
    // Remove 'setup.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']) . '/setup.php')) {
        print "<p class=\"alert alert-success\">The file <em>./install/setup.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"alert alert-info\">Please delete the file <em>./install/setup.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']) . '/update.php')) {
        print "<p class=\"alert alert-success\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"alert alert-info\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }

    HTMLFooter();
}