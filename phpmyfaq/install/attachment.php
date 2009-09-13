<?php 
/**
 * Attachment migration script
 *
 * @package    phpMyFAQ 
 * @subpackage Installation
 * @author    Anatoliy Belsky <ab@php.net>
 * @since      2009-09-13
 * @version    SVN: $Id: attachment.php 4946 2009-09-11 14:06:09Z anatoliy $
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

set_time_limit(0);

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
    header("Location: ".str_replace('admin/index.php', '', $_SERVER['PHP_SELF']).'install/setup.php');
    exit();
}

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqconfig->get('main.phpMyFAQToken')));
session_start();


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>phpMyFAQ Attachment Migration</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <link rel="shortcut icon" href="../template/default/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/default/favicon.ico" type="image/x-icon" />
    <style type="text/css"><!--
    body {
        margin: 0px;
        padding: 0px;
        font-size: 12px;
        font-family: "Bitstream Vera Sans", "Trebuchet MS", Geneva, Verdana, Arial, Helvetica, sans-serif;
        background: #ffffff;
        color: #000000;
    }
    #header {
        margin: auto;
        padding: 15px;
        background: #353535;
        color: #ffffff;
        font-size: 36px;
        font-weight: bold;
        text-align: center;
        border-bottom: 2px solid silver;
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
        width: 500px;
        margin-bottom: 10px;
        padding-top: 15px;
        clear: both;
    }
    legend.installation {
        border: 1px solid black;
        background-color: #C79810;
        padding: 4px 8px 4px 8px;
        font-size: 14px;
        font-weight: bold;
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
        background-color: #6BBA70;
        border: 3px solid #000000;
        color: #ffffff;
        font-weight: bold;
        font-size: 24px;
        padding: 10px 30px 10px 30px;
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
<h1 id="header">phpMyFAQ Atatchment Migration</h1>
<?php 
    $migrationType = PMF_Filter::filterInput(INPUT_POST,
                                             'migrationType',
                                             FILTER_SANITIZE_STRING);
    
    $migration = new PMF_Attachment_Migration;
    
    $options = array();
    
    switch($migrationType) {
        case PMF_Attachment_Migration::MIGRATION_TYPE1:
        case PMF_Attachment_Migration::MIGRATION_TYPE3:
            //TODO implenemt this
            break;
            
        case PMF_Attachment_Migration::MIGRATION_TYPE2:
        case PMF_Attachment_Migration::MIGRATION_TYPE4:
            $options['defaultKey'] = PMF_Filter::filterInput(INPUT_POST,
                                                             'defaultKey',
                                                             FILTER_SANITIZE_STRING);                                 
            break;
            
        default:
            echo '<center><h2><font color="red">BACKUP YOUR FILES BEFORE ',
                 'PROCEED!!!</font></h2></center>';
            showForm();
            break;
            
    }
    
    if(!empty($migrationType))
    {
        if($migration->doMigrate($migrationType, $options)) {
            echo '<br><center><h2><font color="green">Success</font></h2></center>';
        } else {
            echo '<center>',
                 '<font color="red">',
                 'Following errors happened:',
                 '</font><br>',
                 implode('<br>', $migration->getErrors()),
                 '</center>';
            showForm();
        }
    }
    
function showForm()
{
?>
<script>
/**
 * Show option fields corresponding to attachment type
 *
 * @param integer migrationType to show options for
 *
 * @return void
 */
function showOptions(migrationType)
{
	var html = ''
	 
    switch(migrationType*1) {
        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE1 ?>:
        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE3 ?>:
            // nothing to do yet
            break;

        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE2 ?>:
        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE4 ?>:
            html = 'Default Key: <input name="defaultKey" maxlength="256">'
            break;
    }

    document.getElementById('optionFields').innerHTML = html
}
</script>
<center>
<form method="post">
    <table>
        <tr>
            <td>
                <select id="migrationType" name="migrationType"
                        onchange="showOptions(this.options[this.selectedIndex].value)">
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE1 ?>">
                        2.0.x, 2.5.x to 2.6+ without encryption
                    </option>
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE2 ?>">
                        2.0.x, 2.5.x to 2.6+ with encryption
                    </option>
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE3 ?>">
                        default encrypted to unencrypted
                    </option>
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE4 ?>">
                        unencrypted to default encrypted
                    </option>
                </select>
            </td>            
        </tr>
        <tr>
            <td id="optionFields">
            
            </td>
        </tr>
        <tr><td><input type="submit"></td></tr>
    </table>
</form>
</center>
<?php     
}   
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
?>
</body>
</html>