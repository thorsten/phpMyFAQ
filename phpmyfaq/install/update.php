<?php
/**
* $Id: update.php,v 1.5 2004-11-27 10:50:47 thorstenr Exp $
*
* Main update script
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Thomas Melchinger <t.melchinger@uni.de>
* @since        2002-01-10
* @copyright    (c) 2001-2004 phpMyFAQ Team
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

define("NEWVERSION", "1.5.0 alpha1");
define("COPYRIGHT", "&copy; 2001-2004 <a href=\"http://www.phpmyfaq.de/\" target=\"_blank\">phpMyFAQ-Team</a> | All rights reserved.");
define("PMF_ROOT_DIR", dirname(dirname(__FILE__)));

require_once (PMF_ROOT_DIR."/inc/data.php");
require_once (PMF_ROOT_DIR."/inc/config.php");

/**
* A function to read the version number
*
* @return   string
* @access   public
* @since    2004-11-27
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function getVersionNumber()
{
    if (isset($version)) {
        $oldversion = $version;
    } elseif (isset($PMF_CONF["version"])) {
        $oldversion = $PMF_CONF["version"];
    } else {
        return FALSE;
    }
    
    $t = explode(' ', $oldversion);
    return $t[0];
}

if (isset($_GET["step"]) && $_GET["step"] != "") {
    $step = $_GET["step"];
} else {
    $step = 1;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ <?php print NEWVERSION; ?> Update</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <style type="text/css"><!--
    body {
	    margin: 0px;
	    padding: 0px;
	    font-size: 12px;
	    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	    background-color: #B0B0B0;
    }
    #header {
	    margin: auto;
	    padding: 35px;
	    background-color: #6A88B1;
        text-align: center;
    }
    #header h1 {
	    font: bold 36px Garamond, times, serif;
	    margin: auto;
	    color: #f5f5f5;
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
        background-color: #FCE397;
        padding: 4px 4px 4px 4px;
    }
    .input {
        width: 200px;
        background-color: #f5f5f5;
        border: 1px solid black;
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
        background-color: #ff7f50;
        border: 1px solid #000000;
        color: #ffffff;
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
/**************************** STEP 1 OF 5 ***************************/
if ($step == 1) {
?>
<form action="update.php?step=2" method="post">
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 1 of 5)</strong></legend>
<p>This update will work <strong>only</strong> for the following versions:</p>
<ul type="square">
	<li>phpMyFAQ 1.3.x</li>
    <li>phpMyFAQ 1.4.0 alpha1 and later</li>
</ul>
<p>This update will <strong>not</strong> work for the following versions:</p>
<ul type="square">
	<li>phpMyFAQ 0.x</li>
	<li>phpMyFAQ 1.0.x</li>
    <li>phpMyFAQ 1.1.x</li>
    <li>phpMyFAQ 1.2.x</li>
    <li>phpMyFAQ 1.4.0 M1</li>
    <li>phpMyFAQ 1.4.0 M2</li>
</ul>
<p><strong>Please make a full backup of your SQL tables before running this update.</strong></p>
<p>
<?php
    $oldversion = getVersionNumber();
    if ($oldversion != FALSE) {
?>
<span class="text">Your detected current version: phpMyFAQ <?php print $oldversion; ?></span>
<input type="hidden" name="version" value="<?php print $oldversion; ?>" />
<?php
    } else {
?>
<span class="text">Please select your current version:</span>
<select name="version" size="1">
    <option value="1.3.0">phpMyFAQ version 1.3.0</option>
    <option value="1.3.1">phpMyFAQ version 1.3.1</option>
    <option value="1.3.2">phpMyFAQ version 1.3.2</option>
    <option value="1.3.3">phpMyFAQ version 1.3.3 or later</option>
    <option value="1.4.0">phpMyFAQ version 1.4.0 alpha2 or later</option>
    <option value="1.4.1">phpMyFAQ version 1.4.1 and later</option>
</select>
<?php
    }
?>
</p>
<p class="center"><input type="submit" value="Go to step 2 of 5" class="button" /></p>
</fieldset>
</form>
<?php
}

/**************************** STEP 2 OF 5 ***************************/
if ($step == 2) {
	$test1 = 0;
    $test2 = 0;
    $test3 = 0;
    $test4 = 0;
    if (!is_writeable(PMF_ROOT_DIR."/inc/data.php")) {
		print "<p class=\"error\"><strong>Error:</strong> The file ../inc/data.php or the directory ../inc is not writeable. Please correct this!</p>";
    } else {
        $test1 = 1;
	}
	if (!is_writeable(PMF_ROOT_DIR."/inc/config.php")) {
		print "<p class=\"error\"><strong>Error:</strong> The file ../inc/config.php is not writeable. Please correct this!</p>";
    } else {
        $test2 = 1;
    }
    if (!@copy(PMF_ROOT_DIR."/inc/data.php", PMF_ROOT_DIR."/inc/data.php.bak")) {
        print "<p class=\"error\"><strong>Error:</strong> The backup file ../inc/data.php.bak could not be written. Please correct this!</p>";
    } else {
        $test3 = 1;
    }
    if (!@copy(PMF_ROOT_DIR."/inc/config.php", PMF_ROOT_DIR."/inc/config.php.bak")) {
        print "<p class=\"error\"><strong>Error:</strong> The backup file ../inc/config.php.bak could not be written. Please correct this!</p>";
    } else {
        $test4 = 1;
    }
    // is everything is okay?
    if ($test1 == 1 && $test2  == 1 && $test3  == 1 && $test4 == 1 ) {
?>
<form action="update.php?step=3" method="post">
<input type="hidden" name="version" value="<?php print $_POST["version"]; ?>" />
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 2 of 5)</strong></legend>
<p>A backup of your configuration files (config.php and data.php) have been made.</p>
<p>Now the configuration files will be updated.</p>
<p class="center"><input type="submit" value="Go to step 3 of 5" class="button" /></p>
</fieldset>
</form>
<?php
    } else {
        print "<p class=\"error\"><strong>Error:</strong> Your version of phpMyFAQ could not updated.</p>";
    }
}

/**************************** STEP 3 OF 5 ***************************/
if ($step == 3) {
    $ver = version_compare("1.4.1", $_POST["version"]);
?>
<form action="update.php?step=4" method="post">
<input type="hidden" name="version" value="<?php print $_POST["version"]; ?>" />
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 3 of 5)</strong></legend>
<?php
    if ($ver > 0) {
        // Version 1.3.x
?>
<input type="hidden" name="db[server]" value="<?php print $mysql_server; ?>" />
<input type="hidden" name="db[user]" value="<?php print $mysql_user; ?>" />
<input type="hidden" name="db[password]" value="<?php print $mysql_passwort; ?>" />
<input type="hidden" name="db[db]" value="<?php print $mysql_db; ?>" />
<input type="hidden" name="db[prefix]" value="<?php print $sqltblpre; ?>" />

<input type="hidden" name="edit[language]" value="<?php print $sprache; ?>" />
<input type="hidden" name="edit[detection]" value="TRUE" />
<input type="hidden" name="edit[title]" value="<?php print $title; ?>" />
<input type="hidden" name="edit[version]" value="<?php print NEWVERSION; ?>" />
<input type="hidden" name="edit[metaDescription]" value="<?php print $metaDescription; ?>" />
<input type="hidden" name="edit[metaKeywords]" value="<?php print $metaKeywords; ?>" />
<input type="hidden" name="edit[metaPublisher]" value="<?php print $metaPublisher; ?>" />
<input type="hidden" name="edit[adminmail]" value="<?php print $adminmail; ?>" />
<input type="hidden" name="edit[msgContactOwnText]" value="<?php print $msgContactOwnText; ?>" />
<input type="hidden" name="edit[copyright_eintrag]" value="<?php print $copyright_eintrag; ?>" />
<input type="hidden" name="edit[send2friend_text]" value="<?php print $send2friend_text; ?>" />
<input type="hidden" name="edit[attmax]" value="<?php print $attmax; ?>" />
<input type="hidden" name="edit[disatt]" value="<?php print $disatt; ?>" />
<input type="hidden" name="edit[tracking]" value="<?php print $tracking; ?>" />
<input type="hidden" name="edit[enableadminlog]" value="<?php print $enableadminlog; ?>" />
<input type="hidden" name="edit[ipcheck]" value="<?php print $ipcheck; ?>">
<input type="hidden" name="edit[numRecordsPage]" value="10" />
<input type="hidden" name="edit[numNewsArticles]" value="<?php print $numNewsArticles; ?>" />
<input type="hidden" name="edit[bannedIP]" value="<?php print $bannedIP; ?>" />
<input type="hidden" name="edit[parse_php]" value="" />
<input type="hidden" name="edit[mod_rewrite]" value="" />
<?php
    } elseif ($ver == 0) {
        // Version 1.4.x
?>
<input type="hidden" name="edit[language]" value="<?php print $PMF_CONF["language"]; ?>" />
<input type="hidden" name="edit[detection]" value="<?php print $PMF_CONF["detection"]; ?>" />
<input type="hidden" name="edit[title]" value="<?php print $PMF_CONF["title"]; ?>" />
<input type="hidden" name="edit[version]" value="<?php print NEWVERSION; ?>" />
<input type="hidden" name="edit[metaDescription]" value="<?php print $PMF_CONF["metaDescription"]; ?>" />
<input type="hidden" name="edit[metaKeywords]" value="<?php print $PMF_CONF["metaKeywords"]; ?>" />
<input type="hidden" name="edit[metaPublisher]" value="<?php print $PMF_CONF["metaPublisher"]; ?>" />
<input type="hidden" name="edit[adminmail]" value="<?php print $PMF_CONF["adminmail"]; ?>" />
<input type="hidden" name="edit[msgContactOwnText]" value="<?php print $PMF_CONF["msgContactOwnText"]; ?>" />
<input type="hidden" name="edit[copyright_eintrag]" value="<?php print $PMF_CONF["copyright_eintrag"]; ?>" />
<input type="hidden" name="edit[send2friend_text]" value="<?php print $PMF_CONF["send2friend_text"]; ?>" />
<input type="hidden" name="edit[attmax]" value="<?php print $PMF_CONF["attmax"]; ?>" />
<input type="hidden" name="edit[disatt]" value="<?php print $PMF_CONF["disatt"]; ?>" />
<input type="hidden" name="edit[tracking]" value="<?php print $PMF_CONF["tracking"]; ?>" />
<input type="hidden" name="edit[enableadminlog]" value="<?php print $PMF_CONF["enableadminlog"]; ?>" />
<input type="hidden" name="edit[ipcheck]" value="<?php print $PMF_CONF["ipcheck"]; ?>">
<input type="hidden" name="edit[numRecordsPage]" value="<?php print $PMF_CONF["numRecordsPage"]; ?>" />
<input type="hidden" name="edit[numNewsArticles]" value="<?php print $PMF_CONF["numNewsArticles"]; ?>" />
<input type="hidden" name="edit[bannedIP]" value="<?php print $PMF_CONF["bannedIP"]; ?>" />
<input type="hidden" name="edit[parse_php]" value="" />
<input type="hidden" name="edit[mod_rewrite]" value="" />
<?php
    }
?>
<p class="center">The configuration files will be updated after the next step.</p>
<p class="center"><input type="submit" value="Go to step 4 of 5" class="button" /></p>
</fieldset>
</form>
<?php
}

/**************************** STEP 4 OF 5 ***************************/
if ($step == 4) {
?>
<form action="update.php?step=5" method="post">
<input type="hidden" name="version" value="<?php print $_POST["version"]; ?>" />
<fieldset class="installation">
<legend class="installation"><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 4 of 5)</strong></legend>
<?php
    require_once(PMF_ROOT_DIR."/lang/language_en.php");
    
    if (is_array($_REQUEST["db"])) {
        $DB = $_REQUEST["db"];
        if ($fp = @fopen(PMF_ROOT_DIR."/inc/data.php","w")) {
		    @fputs($fp,"<?php\n\$DB[\"server\"] = '".$DB["server"]."';\n\$DB[\"user\"] = '".$DB["user"]."';\n\$DB[\"password\"] = '".$DB["password"]."';\n\$DB[\"db\"] = '".$DB["db"]."';\n\$DB[\"prefix\"] = '".$DB["prefix"]."';\n\$DB[\"type\"] = 'mysql';\n?>");
		    @fclose($fp);
            print "<p class=\"center\">The file ../inc/data.php was successfully updated.</p>";
        } else {
		    print "<p class=\"error\"><strong>Error:</strong> The file ../inc/data.php could not be updated.</p>";
        }
    }
    
	$arrVar = $_REQUEST["edit"];
    
    if ($fp = @fopen(PMF_ROOT_DIR."/inc/config.php", "w")) {
        @fputs($fp, "<?php \n# Created ".date("Y-m-d H:i:s")."\n\n");
        foreach ($arrVar as $key => $value) {
            fputs($fp, "// ".$LANG_CONF[$key][1]."\n\$PMF_CONF[\"".$key."\"] = \"".htmlspecialchars(stripslashes($value))."\";\n\n");
        }
        @fputs($fp, "?>");
	    @fclose($fp);
        print "<p class=\"center\">The file ../inc/config.php was successfully updated.</p>";
    } else {
        print "<p class=\"error\"><strong>Error:</strong> The file ../inc/config.php could not be updated.</p>";
    }
?>
<p class="center"><input type="submit" value="Go to step 5 of 5" class="button" /></p>
</fieldset>
</form>
<?php
}

/**************************** STEP 4 OF 5 ***************************/
if ($step == 5) {
    require_once (PMF_ROOT_DIR."/inc/functions.php");
    require_once (PMF_ROOT_DIR."/inc/mysql.php");
    define("SQLPREFIX", $DB["prefix"]);
    $db = new DB();
    $db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
    
    $version = str_replace(".", "", $_REQUEST["version"]);
    
	if ($version <= "130") {
		$query[] = "ALTER TABLE ".SQLPREFIX."faqadminsessions CHANGE pass pass VARCHAR(64) BINARY NOT NULL";
		$query[] = "ALTER TABLE ".SQLPREFIX."faquser CHANGE pass pass VARCHAR(64) BINARY NOT NULL";
    }
	if ($version <= "131") {
		$query[] = "ALTER TABLE ".SQLPREFIX."faqvoting ADD ip VARCHAR(15) NOT NULL";
    }
	if ($version <= "132") {
		$query[] = "ALTER TABLE ".SQLPREFIX."faqdata ADD comment ENUM('y', 'n') NOT NULL AFTER email";
		$query[] = "ALTER TABLE ".SQLPREFIX."faquser ADD realname VARCHAR(255) NOT NULL AFTER pass, ADD email VARCHAR(255) NOT NULL AFTER realname";
    }
	if ($version <= "133") {
        $query[] = "DROP TABLE ".SQLPREFIX."faqrights";
        $query[] = "DROP TABLE ".SQLPREFIX."faqstatistik";
        // convert categories in table faqdata
        $query[] = "CREATE TABLE ".SQLPREFIX."tempfaqdata (id int(11) NOT NULL auto_increment, lang varchar(5) NOT NULL, active char(3) NOT NULL, rubrik text NOT NULL, keywords text NOT NULL, thema text NOT NULL, content text NOT NULL, author varchar(255) NOT NULL, email varchar(255) NOT NULL, comment enum('y','n') NOT NULL default 'y', datum varchar(15) NOT NULL, FULLTEXT (keywords,thema,content), PRIMARY KEY (id, lang))";
        $query[] = "INSERT INTO ".SQLPREFIX."tempfaqdata SELECT ".SQLPREFIX."faqdata.id AS id, ".SQLPREFIX."faqdata.lang AS lang, ".SQLPREFIX."faqdata.active AS active, ".SQLPREFIX."faqrubrik.id AS rubrik, ".SQLPREFIX."faqdata.keywords AS keywords, ".SQLPREFIX."faqdata.thema AS thema, ".SQLPREFIX."faqdata.content AS content, ".SQLPREFIX."faqdata.author AS author, ".SQLPREFIX."faqdata.email AS email, ".SQLPREFIX."faqdata.comment as comment, ".SQLPREFIX."faqdata.datum AS datum FROM ".SQLPREFIX."faqdata INNER JOIN ".SQLPREFIX."faqrubrik ON ".SQLPREFIX."faqdata.rubrik = ".SQLPREFIX."faqrubrik.rubrik";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqdata RENAME ".SQLPREFIX."faqdataold";
        $query[] = "ALTER TABLE ".SQLPREFIX."tempfaqdata RENAME ".SQLPREFIX."faqdata";
        // convert categories in table faqfragen
        $query[] = "CREATE TABLE ".SQLPREFIX."tempfaqfragen (id int(11) unsigned NOT NULL auto_increment, ask_username varchar(100) NOT NULL, ask_usermail varchar(100) NOT NULL, ask_rubrik varchar(100) NOT NULL, ask_content text NOT NULL, ask_date varchar(20) NOT NULL, PRIMARY KEY (id))";
        $query[] = "INSERT INTO ".SQLPREFIX."tempfaqfragen SELECT ".SQLPREFIX."faqfragen.id AS id, ".SQLPREFIX."faqfragen.ask_username AS ask_username, ".SQLPREFIX."faqfragen.ask_usermail AS ask_usermail, ".SQLPREFIX."faqrubrik.id AS ask_rubrik, ".SQLPREFIX."faqfragen.ask_content AS ask_content, ".SQLPREFIX."faqfragen.ask_date AS ask_date FROM ".SQLPREFIX."faqfragen INNER JOIN ".SQLPREFIX."faqrubrik ON ".SQLPREFIX."faqfragen.ask_rubrik = ".SQLPREFIX."faqrubrik.rubrik";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqfragen RENAME ".SQLPREFIX."faqfragenold";
        $query[] = "ALTER TABLE ".SQLPREFIX."tempfaqfragen RENAME ".SQLPREFIX."faqfragen";
        // convert old categories into the new table
        $query[] = "ALTER TABLE ".SQLPREFIX."faqrubrik RENAME ".SQLPREFIX."faqcategories";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqcategories CHANGE rubrik parent_id INT(11) NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqcategories CHANGE titel name VARCHAR(255) NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqcategories CHANGE datum description VARCHAR(255) NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqcategories ADD lang VARCHAR(5) NOT NULL AFTER id";
        $query[] = "UPDATE ".SQLPREFIX."faqcategories SET parent_id = 0";
        $query[] = "UPDATE ".SQLPREFIX."faqcategories SET description = ''";
    }
    if ($version <= "140") {
        // rewrite data.php
        if ($fp = @fopen("../inc/data.php","w")) {
    		@fputs($fp,"<?php\n\$DB[\"server\"] = '".$DB["server"]."';\n\$DB[\"user\"] = '".$DB["user"]."';\n\$DB[\"password\"] = '".$DB["password"]."';\n\$DB[\"db\"] = '".$DB["db"]."';\n\$DB[\"prefix\"] = '".SQLPREFIX."';\n\$DB[\"type\"] = 'mysql';\n?>");
    		@fclose($fp);
        } else {
    		print "<p class=\"error\"><strong>Error:</strong> Cannot rewrite to data.php.</p>";
        }
        $query[] = "ALTER TABLE ".SQLPREFIX."faqadminlog CHANGE user usr INT(11) DEFAULT '0' NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqadminsessions CHANGE user usr TINYTEXT NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqchanges CHANGE user usr INT(11) DEFAULT '0' NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqcomments CHANGE user usr VARCHAR(255) NOT NULL";
        $query[] = "ALTER TABLE ".SQLPREFIX."faqvoting CHANGE user usr INT(11) DEFAULT '0' NOT NULL";
    }
    if ($version < 150) {
        // create new table faqcategoryrelations
        $query[] = "CREATE TABLE ".SQLPREFIX."faqcategoryrelations ( category_id INT(11) NOT NULL, category_lang VARCHAR(5) NOT NULL default '', record_id INT(11) NOT NULL, record_lang VARCHAR(5) NOT NULL default '', PRIMARY KEY  (category_id,category_lang,record_id,record_lang) )";
        // fill the new table
        $query[] = "INSERT INTO ".SQLPREFIX."faqcategoryrelations SELECT ".SQLPREFIX."faqcategories.id as category_id, ".SQLPREFIX."faqcategories as category_lang, ".SQLPREFIX."faqdata.id as record_id, ".SQLPREFIX."faqdata as record_lang WHERE ".SQLPREFIX."faqcategories.id = ".SQLPREFIX."faqdata.rubrik ORDER BY category_id";
        //
        // TODO: rebuild table faqdata
        //
    }
    
	$query[] = "OPTIMIZE TABLE ".SQLPREFIX."faqadminlog, ".SQLPREFIX."faqadminsessions, ".SQLPREFIX."faqcategories, ".SQLPREFIX."faqchanges, ".SQLPREFIX."faqcomments, ".SQLPREFIX."faqdata, ".SQLPREFIX."faqfragen, ".SQLPREFIX."faqnews, ".SQLPREFIX."faqsessions, ".SQLPREFIX."faquser, ".SQLPREFIX."faqvisits, ".SQLPREFIX."faqvoting";
	
	print "<p class=\"center\">";
    while ($each_query = each($query)) {
		$result = $db->query($each_query[1]);
		print "|&nbsp;\n";
		if (!$result) {
			print "<p class=\"error\"><strong>Error:</strong> ".$db->error()."</p>\n";
            print "<p>Query:</p>\n";
            print "<pre>".$each_query[1]."</pre>\n";
			die();
			}
        wait(250);
		}
    print "</p>\n";
    print "<p class=\"center\">The database was updated successfully.</p>";
    print "<p class=\"center\"><a href=\"../index.php\">phpMyFAQ</a></p>";
    if (@unlink(basename($_SERVER["PHP_SELF"]))) {
        print "<p class=\"center\">This file was deleted automatically.</p>\n";
        }
    else {
        print "<p class=\"center\">Please delete this file manually.</p>\n";
        }
    }
?>
<p class="center"><?php print COPYRIGHT; ?></p>
</body>
</html>
