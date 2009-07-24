<?php
/**
 * Main update script
 *
 * @package    phpMyFAQ 
 * @subpackage Installation
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Thomas Melchinger <t.melchinger@uni.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2002-01-10
 * @version    SVN: $Id$
 * @copyright  2002-2009 phpMyFAQ Team
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

define('NEWVERSION', '2.6.0-alpha');
define('COPYRIGHT', '&copy; 2001-2009 <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> | All rights reserved.');
define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

require_once PMF_ROOT_DIR.'/inc/autoLoader.php';
require_once PMF_ROOT_DIR.'/inc/constants.php';

$step    = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query   = array();

/**
 * Print out the HTML Footer
 *
 * @return   void
 * @access   public
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function HTMLFooter()
{
    printf('<p class="center">%s</p></body></html>', COPYRIGHT);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ <?php print NEWVERSION; ?> Update</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
    <style type="text/css"><!--
    body {
        margin: 10px;
        padding: 0px;
        font-size: 12px;
        font-family: "Bitstream Vera Sans", "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        background: #ffffff;
        color: #000000;
    }
    #header {
        margin: auto;
        padding: 25px;
        background: #E1F0A6;
        color: #234361;
        font-size: 36px;
        font-weight: bold;
        text-align: center;
        border-right: 3px solid silver;
        border-bottom: 3px solid silver;
        -moz-border-radius: 20px 20px 20px 20px;
        border-radius: 20px 20px 20px 20px;
    }
    #header h1 {
        font-family: "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        margin: auto;
        text-align: center;
    }
    .center {
        text-align: center;
    }
    fieldset.installation {
        margin: auto;
        border: 1px solid black;
        width: 550px;
        margin-top: 10px;
    }
    legend.installation {
        border: 1px solid black;
        background-color: #D5EDFF;
        padding: 4px 8px 4px 8px;
        font-size: 14px;
        font-weight: bold;
        -moz-border-radius: 5px 5px 5px 5px;
        border-radius: 5px 5px 5px 5px;
    }
    .input {
        width: 200px;
        background-color: #f5f5f5;
        border: 1px solid black;
        margin-bottom: 8px;
    }
    span.text {
        width: 250px;
        float: left;
        padding-right: 10px;
        line-height: 20px;
    }
    #admin {
        line-height: 20px;
        font-weight: bold;
    }
    .help {
        cursor: help;
        border-bottom: 1px dotted Black;
        font-size: 14px;
        font-weight: bold;
        padding-left: 5px;
    }
    .button {
        background-color: #89AC15;
        border: 3px solid #000000;
        color: #ffffff;
        font-weight: bold;
        font-size: 24px;
        padding: 10px 30px 10px 30px;
        -moz-border-radius: 10px 10px 10px 10px;
        border-radius: 10px 10px 10px 10px;
    }
    .error {
        margin: auto;
        margin-top: 20px;
        width: 600px;
        text-align: center;
        padding: 10px;
        line-height: 20px;
        background-color: #f5f5f5;
        border: 1px solid black;
    }
    --></style>
</head>
<body>

<h1 id="header">phpMyFAQ <?php print NEWVERSION; ?> Update</h1>
<?php
if (!@is_readable(PMF_ROOT_DIR.'/inc/data.php')) {
    print '<p class="center">It seems you never run a version of phpMyFAQ.<br />Please use the <a href="installer.php">install script</a>.</p>';
    HTMLFooter();
    die();
}
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
    print '<p class="center">You need PHP 5.2.0 or later!</p>';
    HTMLFooter();
    die();
}

require PMF_ROOT_DIR . '/inc/data.php';
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
</ul>
<p>This update will <strong>not</strong> work for the following versions:</p>
<ul type="square">
    <li>phpMyFAQ 0.x</li>
    <li>phpMyFAQ 1.x</li>
</ul>
<p><strong>Please make a full backup of your SQL tables before running this update.</strong></p>

<h3 align="center">Your current phpMyFAQ version: <?php print $version; ?></p>
<input name="version" type="hidden" value="<?php print $version; ?>"/>

<p class="center"><input type="submit" value="Go to step 2 of 4" class="button" /></p>
</fieldset>
</form>
<?php
    HTMLFooter();
}

/**************************** STEP 2 OF 4 ***************************/
if ($step == 2) {
    $test1 = $test2 = 0;

    if (!@is_writeable(PMF_ROOT_DIR."/inc/data.php")) {
        print "<p class=\"error\"><strong>Error:</strong> The file ../inc/data.php or the directory ../inc is not writeable. Please correct this!</p>";
    } else {
        $test1 = 1;
    }
    if (!@copy(PMF_ROOT_DIR."/inc/data.php", PMF_ROOT_DIR."/inc/data.bak.php")) {
        print "<p class=\"error\"><strong>Error:</strong> The backup file ../inc/data.bak.php could not be written. Please correct this!</p>";
    } else {
        $test2 = 1;
    }

    // is everything is okay?
    if ($test1 == 1 && $test2  == 1) {
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
        $query[] = "CREATE TABLE ".SQLPREFIX."faqsearches (
                    id INTEGER NOT NULL ,
                    lang VARCHAR(5) NOT NULL ,
                    searchterm VARCHAR(255) NOT NULL ,
                    searchdate TIMESTAMP,
                    PRIMARY KEY (id, lang))";
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
        	default:
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata ADD sticky INTEGER NOT NULL AFTER active";
                $query[] = "ALTER TABLE ".SQLPREFIX."faqdata_revisions ADD sticky INTEGER NOT NULL AFTER active";
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
    // UPDATES FROM 2.6.0-alpha
    //
    if (version_compare($version, '2.6.0-alpha', '<')) {
        
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
    
    // Perform the queries for updating/migrating the database from 2.x
    if (isset($query)) {
        ob_flush();
        flush();
        $count = 0;
        foreach ($query as $current_query) {
            $result = @$db->query($current_query);
            print '| ';
            if (!$result) {
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".PMF_htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            wait(25);
            $count++;
            if (!($count % 10)) {
                ob_flush();
                flush();
            }
        }
        ob_flush();
        flush();
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
        foreach ($query as $current_query) {
            $result = $db->query($current_query);
            printf('<span title="%s">|</span> ', $current_query);
            if (!$result) {
                print "\n<div class=\"error\">\n";
                print "<p><strong>DB error:</strong> ".$db->error()."</p>\n";
                print "<div style=\"text-align: left;\"><p>Query:\n";
                print "<pre>".PMF_htmlentities($current_query)."</pre></p></div>\n";
                print "</div>";
                die();
            }
            wait(25);
        }
    }

    print "</p>\n";

    print '<p class="center">The database was updated successfully.</p>';
    print '<p class="center"><a href="../index.php">phpMyFAQ</a></p>';
    foreach (glob(PMF_ROOT_DIR.'/inc/*.bak.php') as $filename) {
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
    // Remove 'installer.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/installer.php')) {
        print "<p class=\"center\">The file <em>./install/installer.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/installer.php</em> manually.</p>\n";
    }
    // Remove 'update.php' file
    if (@unlink(dirname($_SERVER['PATH_TRANSLATED']).'/update.php')) {
        print "<p class=\"center\">The file <em>./install/update.php</em> was deleted automatically.</p>\n";
    } else {
        print "<p class=\"center\">Please delete the file <em>./install/update.php</em> manually.</p>\n";
    }

    HTMLFooter();
}
