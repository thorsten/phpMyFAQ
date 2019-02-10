<?php
/**
 * Main update script.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Melchinger <t.melchinger@uni.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-01-10
 */

define('COPYRIGHT', '&copy; 2001-2018 <a target="_blank" href="https://www.phpmyfaq.de/">phpMyFAQ Team</a>');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));
define('IS_VALID_PHPMYFAQ', null);

if (version_compare(PHP_VERSION, '5.5.0') < 0) {
    die('Sorry, but you need PHP 5.5.0 or later!'); // Die hard because of "use"
}

set_time_limit(0);

require PMF_ROOT_DIR.'/inc/Bootstrap.php';

$step = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query = [];

if (file_exists(PMF_ROOT_DIR.'/inc/data.php')) {
    require PMF_ROOT_DIR.'/inc/data.php'; // before 2.6.0-alpha
} else {
    if (!file_exists(PMF_ROOT_DIR.'/config/database.php')) {
        header('Location: setup.php');
        exit();
    }
    require PMF_ROOT_DIR.'/config/database.php'; // after 2.6.0-alpha
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>phpMyFAQ <?php echo PMF_System::getVersion(); ?> Update</title>

    <meta name="viewport" content="width=device-width;">
    <meta name="application-name" content="phpMyFAQ <?php echo PMF_System::getVersion(); ?>">
    <meta name="copyright" content="(c) 2001-<?php echo date('Y'); ?> phpMyFAQ Team">

    <link rel="stylesheet" href="../admin/assets/css/style.min.css?v=1">

    <script src="../assets/js/modernizr.min.js"></script>
    <script src="../assets/js/phpmyfaq.min.js"></script>

    <link rel="shortcut icon" href="../assets/template/default/favicon.ico">
</head>
<body>

<div class="navbar navbar-default navbar-static-top">
    <nav class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#phpmyfaq-navbar-collapse" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="phpmyfaq-navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a target="_blank" href="https://www.phpmyfaq.de/documentation">Documentation</a></li>
                <li><a target="_blank" href="https://www.phpmyfaq.de/support">Support</a></li>
                <li><a target="_blank" href="http://forum.phpmyfaq.de/">Forums</a></li>
                <li><a target="_blank" href="http://faq.phpmyfaq.de/">FAQ</a></li>
                <li class="divider-vertical"></li>
                <li><a href="../">Back to your FAQ</a></li>
            </ul>
        </div>
    </nav>
</div>

<section id="content">
    <div class="container">
        <div class="row">
            <div class="jumbotron text-center">
                <h1>phpMyFAQ <?php echo PMF_System::getVersion(); ?> Update</h1>
                <?php
                $version = $faqConfig->get('main.currentVersion');
                ?>
            </div>
        </div>
<?php

$installer = new PMF_Installer();
$installer->checkPreUpgrade($DB['type']);

/**************************** STEP 1 OF 3 ***************************/
if ($step === 1) {
    ?>
        <form action="update.php?step=2" method="post">
        <input name="version" type="hidden" value="<?php echo $version;
    ?>">
        <div class="row form-group">
            <div class="col-lg-12">
                <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                    <li class="active">
                        <a href="#">
                            <h4 class="list-group-item-heading">Step 1 of 3</h4>
                            <p class="list-group-item-text">Update information</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="update.php?step=2">
                            <h4 class="list-group-item-heading">Step 2 of 3</h4>
                            <p class="list-group-item-text">File backups</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 3 of 3</h4>
                            <p class="list-group-item-text">Database updates</p>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row setup-content" id="step1">
            <div class="col-lg-12">
                <p class="alert alert-info text-center">
                    <strong>
                        <i aria-hidden="true" class="fa fa-info-circle"></i> Please create a full backup of your database, your templates,
                        attachments and uploaded images before running this update.
                    </strong>
                </p>

                <p>This update script will work <strong>only</strong> for the following versions:</p>
                <ul>
                    <li>phpMyFAQ 2.6.x (out of support since end of 2011)</li>
                    <li>phpMyFAQ 2.7.x (out of support since end of 2013)</li>
                    <li>phpMyFAQ 2.8.x</li>
                    <li>phpMyFAQ 2.9.x</li>
                </ul>

                <p>This update script <strong>will not</strong> work for the following versions:</p>
                <ul>
                    <li>phpMyFAQ 0.x</li>
                    <li>phpMyFAQ 1.x</li>
                    <li>phpMyFAQ 2.0.x</li>
                    <li>phpMyFAQ 2.5.x</li>
                </ul>
                <?php
                // 2.5 versions only
                if (version_compare($version, '2.6.0-alpha', '<') && !is_writeable('../template')) {
                    echo '<p class="alert alert-danger text-center"><strong>Please change the directory ../template '.
                         'and its contents writable (777 on Linux/UNIX).</strong></p>';
                }

                // We only support updates from 2.6+
                if (version_compare($version, '2.6.0', '>')) {
                    printf(
                        '<p class="alert alert-success text-center">Your current phpMyFAQ version: %s %s</p>',
                        $version,
                        '<i aria-hidden="true" class="fa fa-check"></i>'
                    );
                } else {
                    printf(
                        '<p class="alert alert-danger text-center">Your current phpMyFAQ version: %s</p>',
                        $version
                    );
                    echo '<p>Please update to the latest phpMyFAQ 2.7 version first.</p>';
                }
    if ('hash' !== PMF_ENCRYPTION_TYPE) {
        printf(
                        '<p class="alert alert-info text-center">Your passwords are currently encoded with a %s() method.</p>',
                        PMF_ENCRYPTION_TYPE
                    );
    }
    ?>

                <p style="text-align: center">
                    <button class="btn btn-primary btn-lg" type="submit">
                        Go to step 2 of 3
                    </button>
                </p>
            </div>
        </div>
        </form>
<?php
    PMF_System::renderFooter();
}

/**************************** STEP 2 OF 3 ***************************/
if ($step == 2) {
    $checkDatabaseSetupFile = $checkLdapSetupFile = $checkElasticsearchSetupFile = false;
    $updateMessages = [];

    // Backup of config/database.php
    if (file_exists(PMF_ROOT_DIR.'/config/database.php')) {
        if (!copy(PMF_ROOT_DIR.'/config/database.php', PMF_ROOT_DIR.'/config/database.bak.php')) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ../config/database.bak.php '.
                  'could not be written. Please correct this!</p>';
        } else {
            $checkDatabaseSetupFile = true;
            $updateMessages[] = 'A backup of your database configuration file has been made.';
        }
    }

    // Backup of config/ldap.php if exists
    if (file_exists(PMF_ROOT_DIR.'/config/ldap.php')) {
        if (!copy(PMF_ROOT_DIR.'/config/ldap.php', PMF_ROOT_DIR.'/config/ldap.bak.php')) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ../config/ldap.bak.php '.
                'could not be written. Please correct this!</p>';
        } else {
            $checkLdapSetupFile = true;
            $updateMessages[] = 'A backup of your LDAP configuration file has been made.';
        }
    } else {
        $checkLdapSetupFile = true;
    }

    // Backup of config/elasticsearch.php if exists
    if (file_exists(PMF_ROOT_DIR.'/config/elasticsearch.php')) {
        if (!copy(PMF_ROOT_DIR.'/config/elasticsearch.php', PMF_ROOT_DIR.'/config/elasticsearch.bak.php')) {
            echo '<p class="alert alert-danger"><strong>Error:</strong> The backup file ' .
                '../config/elasticsearch.bak.php could not be written. Please correct this!</p>';
        } else {
            $checkElasticsearchSetupFile = true;
            $updateMessages[] = 'A backup of your Elasticsearch configuration file has been made.';
        }
    } else {
        $checkElasticsearchSetupFile = true;
    }

    // is everything is okay?
    if ($checkDatabaseSetupFile && $checkLdapSetupFile && $checkElasticsearchSetupFile) {
        ?>
        <form action="update.php?step=3" method="post">
        <input type="hidden" name="version" value="<?php echo $version;
        ?>">
        <div class="row form-group">
            <div class="col-lg-12">
                <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 1 of 3</h4>
                            <p class="list-group-item-text">Update information</p>
                        </a>
                    </li>
                    <li class="active"><a href="#">
                            <h4 class="list-group-item-heading">Step 2 of 3</h4>
                            <p class="list-group-item-text">File backups</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="update.php?step=3">
                            <h4 class="list-group-item-heading">Step 3 of 3</h4>
                            <p class="list-group-item-text">Database updates</p>
                        </a>
                   </li>
                </ul>
            </div>
        </div>
        <div class="row setup-content" id="step2">
            <div class="col-lg-12">
                <?php foreach ($updateMessages as $updateMessage) {
                    printf('<p><i aria-hidden="true" class="fa fa-check-circle"></i> %s</p>', $updateMessage);
                } ?>
                <p>Your phpMyFAQ configuration will be updated after the next step.</p>
                <p style="text-align: center;">
                    <button class="btn btn-primary btn-lg" type="submit">
                        Go to step 3 of 3
                    </button>
                </p>
            </div>
        </div>
        </form>
<?php
        PMF_System::renderFooter();
    } else {
        echo '<p class="alert alert-danger"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>';
        PMF_System::renderFooter();
    }
}

/**************************** STEP 3 OF 3 ***************************/
if ($step == 3) {
    ?>

        <div class="row form-group">
            <div class="col-lg-12">
                <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 1 of 3</h4>
                            <p class="list-group-item-text">Update information</p>
                        </a>
                    </li>
                    <li class="disabled"><a href="#">
                            <h4 class="list-group-item-heading">Step 2 of 3</h4>
                            <p class="list-group-item-text">File backups</p>
                        </a>
                    </li>
                    <li class="active"><a href="#">
                            <h4 class="list-group-item-heading">Step 3 of 3</h4>
                            <p class="list-group-item-text">Database updates</p>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row setup-content" id="step2">
            <div class="col-lg-12">
<?php
    $images = [];
    $prefix = PMF_Db::getTablePrefix();
    $faqConfig->getAll();

    //
    // Enable maintenance mode
    //
    if ($faqConfig->set('main.maintenanceMode', 'true')) {
        echo "<p class='alert alert-info'><i class='fa fa-info-circle'></i> Activating maintenance mode ...</p>";
    }

    //
    // UPDATES FROM 2.6.99
    //
    if (version_compare($version, '2.6.99', '<')) {
        $faqConfig->add('search.relevance', 'thema,content,keywords');
        $faqConfig->add('search.enableRelevance', 'false');
        $faqConfig->add('main.enableGoogleTranslation', 'false');
        $faqConfig->add('main.googleTranslationKey', 'INSERT-YOUR-KEY');
    }

    //
    // UPDATES FROM 2.7.0-alpha
    //
    if (version_compare($version, '2.7.0-alpha', '<')) {
        // Add new config values
        $faqConfig->add('socialnetworks.enableTwitterSupport', 'false');
        $faqConfig->add('socialnetworks.twitterConsumerKey', '');
        $faqConfig->add('socialnetworks.twitterConsumerSecret', '');
        $faqConfig->add('socialnetworks.twitterAccessTokenKey', '');
        $faqConfig->add('socialnetworks.twitterAccessTokenSecret', '');
        $faqConfig->add('socialnetworks.enableFacebookSupport', 'false');

        // Migrate faqquestion table to new structure
        switch ($DB['type']) {
            case 'pgsql':
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions RENAME COLUMN ask_username TO username';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions RENAME COLUMN ask_usermail TO email';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions RENAME COLUMN ask_rubrik TO category_id';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions RENAME COLUMN ask_content TO question';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions RENAME COLUMN ask_date TO created';
                break;
            case 'mssql':
            case 'sqlsrv':
                $query[] = "EXEC sp_RENAME '".$prefix."faqquestions.ask_username', 'username', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '".$prefix."faqquestions.ask_usermail', 'email', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '".$prefix."faqquestions.ask_rubrik', 'category_id', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '".$prefix."faqquestions.ask_content', 'question', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '".$prefix."faqquestions.ask_date', 'created', 'COLUMN'";
                break;
            case 'mysqli':
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions CHANGE ask_username username VARCHAR(100) NOT NULL';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions CHANGE ask_usermail email VARCHAR(100) NOT NULL';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions CHANGE ask_rubrik category_id INT(11) NOT NULL';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions CHANGE ask_content question TEXT NOT NULL';
                $query[] = 'ALTER TABLE '.$prefix.'faqquestions CHANGE ask_date created VARCHAR(20) NOT NULL';
                break;
        }

        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (34, 'addattachment', 'Right to add attachments', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (35, 'editattachment', 'Right to edit attachments', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (36, 'delattachment', 'Right to delete attachments', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (37, 'dlattachment', 'Right to download attachments', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 34)';
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 35)';
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 36)';
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 37)';
    }

    //
    // UPDATES FROM 2.7.0-alpha2
    //
    if (version_compare($version, '2.7.0-alpha2', '<')) {
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (38, 'reports', 'Right to generate reports', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 38)';
    }

    //
    // UPDATES FROM 2.7.0-beta
    //
    if (version_compare($version, '2.7.0-beta', '<')) {
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'search.numberSearchTerms'
            WHERE config_name = 'main.numberSearchTerms'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'search.useAjaxSearchOnStartpage'
            WHERE config_name = 'main.useAjaxSearchOnStartpage'";
    }

    //
    // UPDATES FROM 2.7.0-beta2
    //
    if (version_compare($version, '2.7.0-beta2', '<')) {
        $faqConfig->add('security.ssoSupport', 'false');
        $faqConfig->add('security.ssoLogoutRedirect', '');
        $faqConfig->add('main.dateFormat', 'Y-m-d H:i');
        $faqConfig->add('security.enableLoginOnly', 'false');
    }

    //
    // UPDATES FROM 2.7.0-RC
    //
    if (version_compare($version, '2.7.0-RC', '<')) {
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.numberOfRecordsPerPage'
            WHERE config_name = 'main.numberOfRecordsPerPage'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.numberOfShownNewsEntries'
            WHERE config_name = 'main.numberOfShownNewsEntries'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.orderingPopularFaqs'
            WHERE config_name = 'main.orderingPopularFaqs'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.disableAttachments'
            WHERE config_name = 'main.disableAttachments'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.maxAttachmentSize'
            WHERE config_name = 'main.maxAttachmentSize'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.attachmentsPath'
            WHERE config_name = 'main.attachmentsPath'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.attachmentsStorageType'
            WHERE config_name = 'main.attachmentsStorageType'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.enableAttachmentEncryption'
            WHERE config_name = 'main.enableAttachmentEncryption'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'records.defaultAttachmentEncKey'
            WHERE config_name = 'main.defaultAttachmentEncKey'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.permLevel'
            WHERE config_name = 'main.permLevel'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.ipCheck'
            WHERE config_name = 'main.ipCheck'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.enableLoginOnly'
            WHERE config_name = 'main.enableLoginOnly'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.ldapSupport'
            WHERE config_name = 'main.ldapSupport'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.bannedIPs'
            WHERE config_name = 'main.bannedIPs'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.ssoSupport'
            WHERE config_name = 'main.ssoSupport'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.ssoLogoutRedirect'
            WHERE config_name = 'main.ssoLogoutRedirect'";
        $query[] = 'UPDATE '.$prefix."faqconfig SET config_name = 'security.useSslForLogins'
            WHERE config_name = 'main.useSslForLogins'";
    }

    //
    // UPDATES FROM 2.7.1
    //
    if (version_compare($version, '2.7.1', '<')) {
        $faqConfig->add('security.useSslOnly', 'false');
    }

    //
    // UPDATES FROM 2.7.3
    //
    if (version_compare($version, '2.7.3', '<')) {
        $query[] = 'DELETE FROM '.$prefix.'faqright WHERE right_id = 18 AND right_id = 19';
        $query[] = 'DELETE FROM '.$prefix.'faquser_right WHERE right_id = 18 AND right_id = 19';
        $query[] = 'DELETE FROM '.$prefix.'faqgroup_right WHERE right_id = 18 AND right_id = 19';
    }

    //
    // UPDATES FROM 2.8.0-alpha
    //
    if (version_compare($version, '2.8.0-alpha', '<')) {
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (39, 'addfaq', 'Right to add FAQs in frontend', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 39)';
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (40, 'addquestion', 'Right to add questions in frontend', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 40)';
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description, for_users, for_groups) VALUES
            (41, 'addcomment', 'Right to add comments in frontend', 1, 1)";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 41)';

        $faqConfig->add('cache.varnishEnable', 'false');
        $faqConfig->add('cache.varnishHost', '127.0.0.1');
        $faqConfig->add('cache.varnishPort', '2000');
        $faqConfig->add('cache.varnishSecret', '');
        $faqConfig->add('cache.varnishTimeout', '500');

        $faqConfig->add('security.forcePasswordUpdate', 'true');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE '.$prefix.'faqquestions ADD COLUMN answer_id INT NOT NULL DEFAULT 0';
        } else {
            $query[] = 'ALTER TABLE '.$prefix.'faqquestions ADD answer_id INT NOT NULL DEFAULT 0';
        }

        $faqConfig->add('records.enableCloseQuestion', 'false');
        $faqConfig->add('records.enableDeleteQuestion', 'false');

        $query[] = 'CREATE TABLE '.$prefix.'faquserlogin_temp (
            login varchar(128) NOT NULL,
            pass varchar(80) NOT NULL,
            PRIMARY KEY (login))';
        $query[] = 'INSERT INTO '.$prefix.'faquserlogin_temp SELECT * FROM '.$prefix.'faquserlogin';
        $query[] = 'DROP TABLE '.$prefix.'faquserlogin';
        $query[] = 'CREATE TABLE '.$prefix.'faquserlogin (
            login varchar(128) NOT NULL,
            pass varchar(80) NOT NULL,
            PRIMARY KEY (login))';
        $query[] = 'INSERT INTO '.$prefix.'faquserlogin SELECT * FROM '.$prefix.'faquserlogin_temp';
        $query[] = 'DROP TABLE '.$prefix.'faquserlogin_temp';
    }

    //
    // UPDATES FROM 2.8.0-alpha2
    //
    if (version_compare($version, '2.8.0-alpha2', '<')) {
        switch ($DB['type']) {
            case 'pgsql':
                $query[] = 'CREATE TABLE '.$prefix.'faqinstances (
                    id int4 NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    modified TIMESTAMP NOT NULL,
                    PRIMARY KEY (id))';
                $query[] = 'CREATE TABLE '.$prefix."faqinstances_config (
                    instance_id int4 NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name))";
                break;
            case 'mssql':
            case 'sqlsrv':
            case 'sqlite3':
                $query[] = 'CREATE TABLE '.$prefix.'faqinstances (
                    id INT NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created DATETIME NOT NULL,
                    modified DATETIME NOT NULL,
                    PRIMARY KEY (id))';
                $query[] = 'CREATE TABLE '.$prefix."faqinstances_config (
                    instance_id INT NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name))";
                break;
            case 'mysqli':
                $query[] = 'CREATE TABLE '.$prefix.'faqinstances (
                    id INT(11) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    instance VARCHAR(255) NOT NULL,
                    comment TEXT NULL,
                    created TIMESTAMP DEFAULT 0,
                    modified TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
                $query[] = 'CREATE TABLE '.$prefix."faqinstances_config (
                    instance_id INT(11) NOT NULL,
                    config_name VARCHAR(255) NOT NULL default '',
                    config_value VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (instance_id, config_name)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                break;
        }

        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description) VALUES
            (42, 'editinstances', 'Right to edit multi-site instances')";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 42)';
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description) VALUES
            (43, 'addinstances', 'Right to add multi-site instances')";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 43)';
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description) VALUES
            (44, 'delinstances', 'Right to delete multi-site instances')";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 44)';

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE '.$prefix.'faquser ADD COLUMN remember_me VARCHAR(150) NULL DEFAULT \'\'';
        } else {
            $query[] = 'ALTER TABLE '.$prefix.'faquser ADD remember_me VARCHAR(150) NULL DEFAULT \'\'';
        }
    }

    // Perform the queries for updating/migrating the database
    if (isset($query)) {
        echo '<div class="text-center">';
        $count = 0;
        foreach ($query as $key => $executeQuery) {
            $result = $faqConfig->getDb()->query($executeQuery);
            echo '.';
            if (!($key % 100)) {
                echo '<br />';
            }
            if (!$result) {
                echo '</div>';
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please update your version of phpMyFAQ '.
                      'once again or send us a <a href="http://bugs.phpmyfaq.de" target="_blank">bug report</a>.'.
                      '</p>';
                printf(
                    '<p class="alert alert-danger"><strong>DB error:</strong> %s</p>',
                    $faqConfig->getDb()->error()
                );
                printf(
                    '<code>%s</code>',
                    htmlentities($executeQuery)
                );
                PMF_System::renderFooter();
            }
            usleep(10000);
            ++$count;
            if (!($count % 10)) {
                ob_flush();
            }
        }
        echo '</div>';
    }

    // Clear the array with the queries
    unset($query);
    $query = [];

    //
    // 2nd UPDATES FROM 2.8.0-alpha2
    //
    if (version_compare($version, '2.8.0-alpha2', '<')) {
        $link = new PMF_Link(null, $faqConfig);

        $instanceData = array(
            'url' => $link->getSystemUri($_SERVER['SCRIPT_NAME']),
            'instance' => $link->getSystemRelativeUri('setup/update.php'),
            'comment' => $faqConfig->get('main.titleFAQ'),
        );
        $faqInstance = new PMF_Instance($faqConfig);
        $faqInstance->addInstance($instanceData);

        $faqInstanceMaster = new PMF_Instance_Master($faqConfig);
        $faqInstanceMaster->createMaster($faqInstance);

        $faqConfig->add('records.autosaveActive', 'false');
        $faqConfig->add('records.autosaveSecs', '180');
        $faqConfig->add('main.maintenanceMode', 'false');
        $faqConfig->add('security.salt', md5($faqConfig->getDefaultUrl()));
    }

    //
    // UPDATES FROM 2.8.0-alpha3
    //
    if (version_compare($version, '2.8.0-alpha3', '<')) {
        $query[] = 'DROP TABLE '.$prefix.'faqlinkverifyrules';
        $query[] = 'INSERT INTO '.$prefix."faqright (right_id, name, description) VALUES
            (45, 'export', 'Right to export the complete FAQ')";
        $query[] = 'INSERT INTO '.$prefix.'faquser_right (user_id, right_id) VALUES (1, 45)';
    }

    //
    // UPDATES FROM 2.8.0-beta
    //
    if (version_compare($version, '2.8.0-beta', '<')) {
        $faqConfig->add('records.randomSort', 'false');
        $faqConfig->add('main.enableWysiwygEditorFrontend', 'false');
    }

    //
    // UPDATED FROM 2.8.0-beta2
    //
    if (version_compare($version, '2.8.0-beta2', '<')) {
        $faqConfig->delete('main.enableGoogleTranslation');
        $faqConfig->delete('main.googleTranslationKey');
    }

    //
    // UPDATED FROM 2.8.0-beta3
    //
    if (version_compare($version, '2.8.0-beta3', '<')) {
        $faqConfig->add('main.enableGravatarSupport', 'false');
    }

    //
    // UPDATED FROM 2.8.15
    //
    if (version_compare($version, '2.8.16', '<')) {
        $query[] = 'CREATE INDEX index_time ON '.$prefix.'faqsessions (time)';
    }
    //
    // UPDATES FROM 2.9.0-alpha
    //
    if (version_compare($version, '2.9.0-alpha', '<')) {
        $faqConfig->delete('cache.varnishEnable');
        $faqConfig->delete('cache.varnishHost');
        $faqConfig->delete('cache.varnishPort');
        $faqConfig->delete('cache.varnishSecret');
        $faqConfig->delete('cache.varnishTimeout');

        $faqConfig->add('search.enableHighlighting', 'true');
        $faqConfig->add('main.enableRssFeeds', 'true');
        $faqConfig->add('records.allowCommentsForGuests', 'true');
        $faqConfig->add('records.allowQuestionsForGuests', 'true');
        $faqConfig->add('records.allowNewFaqsForGuests', 'true');
        $faqConfig->add('records.hideEmptyCategories', 'false');
        $faqConfig->add('search.searchForSolutionId', 'true');
        $faqConfig->add('socialnetworks.disableAll', 'false');
        $faqConfig->add('main.enableGzipCompression', 'true');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE ' . $prefix . 'faquser ADD COLUMN success INT(1) NULL DEFAULT 1';
        } elseif ('pgsql' === $DB['type']) {
            $query[] = 'ALTER TABLE '.$prefix.'faquser ADD success SMALLINT NULL DEFAULT 1';
        } else {
            $query[] = 'ALTER TABLE '.$prefix.'faquser ADD success INTEGER NULL DEFAULT 1';
        }
    }

    //
    // UPDATES FROM 2.9.0-alpha2
    //
    if (version_compare($version, '2.9.0-alpha2', '<')) {
        $faqConfig->add('seo.metaTagsHome', 'index, follow');
        $faqConfig->add('seo.metaTagsFaqs', 'index, follow');
        $faqConfig->add('seo.metaTagsCategories', 'index, follow');
        $faqConfig->add('seo.metaTagsPages', 'index, follow');
        $faqConfig->add('seo.metaTagsAdmin', 'noindex, nofollow');
        $faqConfig->add('main.enableLinkVerification', 'true');
        $faqConfig->add('spam.manualActivation', 'true');
        $faqConfig->add('mail.remoteSMTP', 'false');
        $faqConfig->add('mail.remoteSMTPServer', '');
        $faqConfig->add('mail.remoteSMTPUsername', '');
        $faqConfig->add('mail.remoteSMTPPassword', '');
        $faqConfig->add('security.enableRegistration', 'true');
        $faqConfig->delete('search.useAjaxSearchOnStartpage');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE '.$prefix.'faqcategories ADD COLUMN active INT(1) NULL DEFAULT 1';
        } else {
            $query[] = 'ALTER TABLE '.$prefix.'faqcategories ADD active INT NULL DEFAULT 1';
        }
    }

    //
    // UPDATES FROM 2.9.0-alpha3
    //
    if (version_compare($version, '2.9.0-alpha3', '<')) {
        $faqConfig->add('main.customPdfHeader', '');
        $faqConfig->add('main.customPdfFooter', '');
        $faqConfig->add('records.allowDownloadsForGuests', 'false');
        $faqConfig->add('main.enableMarkdownEditor', 'false');
        $faqConfig->add('main.enableSmartAnswering', 'true');
        $faqConfig->add('records.numberMaxStoredRevisions', '10');

        if ('sqlite3' === $DB['type']) {
            $query[] = 'ALTER TABLE '.$prefix.'faqquestions ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT \'\'';
            $query[] = 'ALTER TABLE '.$prefix.'faqcategories ADD COLUMN group_id INT NULL DEFAULT -1';
        } else {
            $query[] = 'ALTER TABLE '.$prefix.'faqquestions ADD lang VARCHAR(5) NOT NULL DEFAULT \'\'';
            $query[] = 'ALTER TABLE '.$prefix.'faqcategories ADD group_id INT NULL DEFAULT -1';
        }
        $query[] = 'UPDATE '.$prefix."faqquestions SET lang = '".$faqConfig->getDefaultLanguage()."'";
    }

    //
    // UPDATES FROM 2.9.0-alpha4
    //
    if (version_compare($version, '2.9.0-alpha4', '<')) {
        switch ($DB['type']) {
            case 'pgsql':
                $query[] = 'ALTER TABLE '.$prefix.'faqdata RENAME COLUMN datum TO updated';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions RENAME COLUMN datum TO updated';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                break;
            case 'mssql':
            case 'sqlsrv':
                $query[] = "EXEC sp_RENAME '".$prefix."faqdata.datum', 'updated', 'COLUMN'";
                $query[] = "EXEC sp_RENAME '".$prefix."faqdata_revisions.datum', 'updated', 'COLUMN'";
                $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD created DATETIME DEFAULT CURRENT_TIMESTAMP';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions ADD created DATETIME DEFAULT CURRENT_TIMESTAMP';
            break;
            case 'mysqli':
                $query[] = 'ALTER TABLE '.$prefix.'faqdata CHANGE datum updated VARCHAR(15) NOT NULL';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions CHANGE datum updated VARCHAR(15) NOT NULL';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions ADD created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                break;
        }
        if ('sqlite3' === $DB['type']) {
            $query[] = 'CREATE TABLE '.$prefix."faqdata_temp (
                id INTEGER NOT NULL,
                lang VARCHAR(5) NOT NULL,
                solution_id INTEGER NOT NULL,
                revision_id INTEGER NOT NULL DEFAULT 0,
                active char(3) NOT NULL,
                sticky INTEGER NOT NULL,
                keywords text DEFAULT NULL,
                thema text NOT NULL,
                content text DEFAULT NULL,
                author VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                comment char(1) default 'y',
                updated VARCHAR(15) NOT NULL,
                links_state VARCHAR(7) DEFAULT NULL,
                links_check_date INTEGER DEFAULT 0 NOT NULL,
                date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
                date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
                PRIMARY KEY (id, lang))";
            $query[] = 'INSERT INTO '.$prefix.'faqdata_temp
                SELECT
                    id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email,
                    comment, datum, links_state, links_check_date, date_start, date_end
                FROM '.$prefix.'faqdata';
            $query[] = 'DROP TABLE '.$prefix.'faqdata';
            $query[] = 'ALTER TABLE '.$prefix.'faqdata_temp RENAME TO '.$prefix.'faqdata';

            $query[] = 'CREATE TABLE '.$prefix."faqdata_revision_temp (
                id INTEGER NOT NULL,
                lang VARCHAR(5) NOT NULL,
                solution_id INTEGER NOT NULL,
                revision_id INTEGER NOT NULL DEFAULT 0,
                active char(3) NOT NULL,
                sticky INTEGER NOT NULL,
                keywords text DEFAULT NULL,
                thema text NOT NULL,
                content text DEFAULT NULL,
                author VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                comment char(1) default 'y',
                updated VARCHAR(15) NOT NULL,
                links_state VARCHAR(7) DEFAULT NULL,
                links_check_date INTEGER DEFAULT 0 NOT NULL,
                date_start VARCHAR(14) NOT NULL DEFAULT '00000000000000',
                date_end VARCHAR(14) NOT NULL DEFAULT '99991231235959',
                PRIMARY KEY (id, lang, solution_id, revision_id))";
            $query[] = 'INSERT INTO '.$prefix.'faqdata_revision_temp
                SELECT
                    id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email,
                    comment, datum, links_state, links_check_date, date_start, date_end
                FROM '.$prefix.'faqdata_revisions';
            $query[] = 'DROP TABLE '.$prefix.'faqdata_revisions';
            $query[] = 'ALTER TABLE '.$prefix.'faqdata_revision_temp RENAME TO '.$prefix.'faqdata_revisions';
            $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD COLUMN created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions ADD COLUMN created TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        }
    }

    //
    // UPDATES FROM 2.9.0-beta
    //
    if (version_compare($version, '2.9.0-beta2', '<')) {
        $faqConfig->add('search.enableElasticsearch', 'false');
    }

    //
    // UPDATES FROM 2.9.0-RC
    //
    if (version_compare($version, '2.9.0-RC', '<')) {
        if ($DB['type'] === 'mssql' || $DB['type'] === 'sqlsrv') {
            $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD notes VARCHAR(MAX) DEFAULT NULL';
            $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions ADD notes VARCHAR(MAX) DEFAULT NULL';
        } else {
            $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD notes text DEFAULT NULL';
            $query[] = 'ALTER TABLE '.$prefix.'faqdata_revisions ADD notes text DEFAULT NULL';
        }
    }

    //
    // UPDATES FROM 2.9.6
    //
    if (version_compare($version, '2.9.6', '<')) {
        if ($DB['type'] === 'mysqli') {
            $query[] = 'ALTER TABLE '.$prefix.'faqdata ADD FULLTEXT(keywords,thema,content);';
            $query[] = 'ALTER TABLE '.$prefix.'faqquestions CHANGE COLUMN lang lang VARCHAR(5) AFTER id';
        }
    }

    // Always the last step: Update version number
    if (version_compare($version, PMF_System::getVersion(), '<')) {
        $faqConfig->update(array('main.currentVersion' => PMF_System::getVersion()));
    }

    // optimize tables if possible
    switch ($DB['type']) {
        case 'mysqli':
            // Get all table names
            $faqConfig->getDb()->getTableNames($prefix);
            foreach ($faqConfig->getDb()->tableNames as $tableName) {
                $query[] = 'OPTIMIZE TABLE '.$tableName;
            }
            break;
        case 'pgsql':
            $query[] = 'VACUUM ANALYZE;';
            break;
    }

    // Perform the queries for optimizing the database
    if (isset($query)) {
        echo '<div class="center">';
        foreach ($query as $executeQuery) {
            $result = $faqConfig->getDb()->query($executeQuery);
            printf('<span title="%s"><i aria-hidden="true" class="fa fa-circle"></i></span>', $executeQuery);
            if (!$result) {
                echo '<p class="alert alert-danger"><strong>Error:</strong> Please install your version of phpMyFAQ once again '.
                      'or send us a <a href="http://bugs.phpmyfaq.de" target="_blank">bug report</a>.</p>';
                printf('<p class="error"><strong>DB error:</strong> %s</p>', $faqConfig->getDb()->error());
                printf('<code>%s</code>', htmlentities($executeQuery));
                PMF_System::renderFooter();
            }
            usleep(10000);
        }
        echo '</div>';
    }

    //
    // Disable maintenance mode
    //
    if ($faqConfig->set('main.maintenanceMode', 'false')) {
        echo "<p class='alert alert-info'><i class='fa fa-info-circle'></i> Deactivating maintenance mode ...</p>";
    }

    echo "</p>\n";
    echo '<p class="alert alert-success">The database was updated successfully. Thank you very much for updating.</p>';
    echo '<h3>Back to your updated <a href="../index.php">phpMyFAQ installation</a> and have fun! :-)</h3>';

    // Remove backup files
    foreach (glob(PMF_ROOT_DIR.'/config/*.bak.php') as $filename) {
        if (!@unlink($filename)) {
            printf("<p class=\"alert alert-info\">Please remove the backup file %s manually.</p>\n", $filename);
        }
    }
    // Remove 'setup.php' file
    if (is_writeable(__DIR__.'/index.php') && @unlink(__DIR__.'/index.php')) {
        echo "<p class=\"alert alert-success\">The file <em>./setup/index.php</em> was deleted automatically.</p>\n";
    } else {
        echo "<p class=\"alert alert-danger\">Please delete the file <em>./setup/index.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (is_writeable(__DIR__.'/update.php') && @unlink(__DIR__.'/update.php')) {
        echo "<p class=\"alert alert-success\">The file <em>./setup/update.php</em> was deleted automatically.</p>\n";
    } else {
        echo "<p class=\"alert alert-danger\">Please delete the file <em>./setup/update.php</em> manually.</p>\n";
    }

    PMF_System::renderFooter();
}
