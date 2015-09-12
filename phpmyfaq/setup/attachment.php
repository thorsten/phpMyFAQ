<?php 
/**
 * Attachment migration script
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-13
 */

set_time_limit(0);

define('PMF_ROOT_DIR', dirname(__DIR__));
define('IS_VALID_PHPMYFAQ', null);

//
// Check if config/database.php exist -> if not, redirect to installer
//
if (!file_exists(PMF_ROOT_DIR . '/config/database.php')) {
    header("Location: ".str_replace('admin/index.php', '', $_SERVER['SCRIPT_NAME']).'setup/index.php');
    exit();
}

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/inc/Bootstrap.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>phpMyFAQ Attachment Migration</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="shortcut icon" href="../assets/template/default/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../assets/template/default/favicon.ico" type="image/x-icon" />
    <style media="screen" type="text/css">@import url(css/setup.css);</style>
</head>
<body>
<h1 id="header">phpMyFAQ Attachment Migration</h1>
<?php 
    $migrationType = PMF_Filter::filterInput(
        INPUT_POST,
        'migrationType',
        FILTER_SANITIZE_STRING
    );
    
    $migration = new PMF_Attachment_Migration($faqConfig);
    $options   = [];
    
    switch ($migrationType) {
        case PMF_Attachment_Migration::MIGRATION_TYPE1:
            //TODO implenemt this
            break;
            
        case PMF_Attachment_Migration::MIGRATION_TYPE2:
/*        case PMF_Attachment_Migration::MIGRATION_TYPE3:
        case PMF_Attachment_Migration::MIGRATION_TYPE4:*/
            $options['defaultKey'] = PMF_Filter::filterInput(INPUT_POST,
                                                             'defaultKey',
                                                             FILTER_SANITIZE_STRING);                                 
            break;
            
        default:
            echo '<h2 style="color: red;">BACKUP YOUR FILES BEFORE PROCEED!!!</h2>';
            showForm();
            break;
            
    }
    
    if (!empty($migrationType)) {
        if ($migration->doMigrate($migrationType, $options)) {
            print '<br><h2 style="color: green;">Success</h2>';
        } else {
            print '<span style="color: red">Errors:</span><br>' . implode('<br>', $migration->getErrors());
            showForm();
        }
        
        $warnings = $migration->getWarnings();
        if(!empty($warnings)) {
            echo '<span style="color: yellow">Warnings:</span><br>' . implode('<br>', $migration->getWarnings());
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
            // nothing to do yet
            break;

        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE2 ?>:
/*        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE3 ?>:
        case <?php echo PMF_Attachment_Migration::MIGRATION_TYPE4 ?>:*/
            html = 'Default Key: <input name="defaultKey" maxlength="256">'
            break;
    }

    document.getElementById('optionFields').innerHTML = html
}
</script>
<form method="post">
    <table>
        <tr>
            <td>
                <select id="migrationType" name="migrationType"
                        onchange="showOptions(this.options[this.selectedIndex].value)">
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE1 ?>">
                        2.5.x ==> 2.6+ files without encryption
                    </option>
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE2 ?>">
                        2.5.x ==> 2.6+ files with encryption
                    </option>
<!--                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE3 ?>">
                        2.6+ default encrypted files ==> unencrypted files
                    </option>
                    <option value="<?php echo PMF_Attachment_Migration::MIGRATION_TYPE4 ?>">
                        2.6+ unencrypted files ==> default encrypted files
                    </option>-->
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
<?php     
}   
    
?>
</body>
</html>