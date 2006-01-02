<?php
/**
* $Id: glossary.edit.php,v 1.4 2006-01-02 16:51:26 thorstenr Exp $
*
* Displays a form to edit an extisting glossary item
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-09-15
* @copyright    (c) 2006 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print sprintf('<h2>%s</h2>', $PMF_LANG['ad_menu_glossary']);

if ($permission['editglossary']) {
    
    $id = (int)$_GET['id'];
    
    require_once('../inc/glossary.php');
    $glossary = new PMF_Glossary($db, $LANGCODE);
    $glossaryItem = $glossary->getGlossaryItem($id);
?>
<form action="<?php print $_SERVER['PHP_SELF'].$linkext; ?>" method="post">
<fieldset>
    <legend><?php print $PMF_LANG['ad_glossary_edit']; ?></legend>
    
    <input type="hidden" name="aktion" value="updateglossary" />
    <input type="hidden" name="id" value="<?php print $glossaryItem['id']; ?>" />
    
    <label class="left" for="item"><?php print $PMF_LANG['ad_glossary_item']; ?>:</label>
    <input class="admin" type="text" name="item" id="item" size="50" value="<?php print $glossaryItem['item']; ?>" /><br />
    
    <label class="left" for="definition"><?php print $PMF_LANG['ad_glossary_definition']; ?>:</label>
    <textarea class="admin" name="definition" id="definition" cols="50" rows="3"><?php print $glossaryItem['definition']; ?></textarea><br />
    
    <input class="submit" type="submit" value="<?php print $PMF_LANG['ad_glossary_save']; ?>" />

</fieldset>
</form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}