<?php
/**
 * $Id: glossary.edit.php,v 1.11 2007-04-12 19:09:33 thorstenr Exp $
 *
 * Displays a form to edit an extisting glossary item
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2005-09-15
 * @copyright   (c) 2005-2007 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print sprintf('<h2>%s</h2>', $PMF_LANG['ad_menu_glossary']);

if ($permission['editglossary']) {

    $id = (int)$_GET['id'];

    require_once(PMF_ROOT_DIR.'/inc/Glossary.php');
    $glossary = new PMF_Glossary($db, $LANGCODE);
    $glossaryItem = $glossary->getGlossaryItem($id);
?>
<form action="<?php print $_SERVER['PHP_SELF']; ?>" method="post">
<fieldset>
    <legend><?php print $PMF_LANG['ad_glossary_edit']; ?></legend>

    <input type="hidden" name="action" value="updateglossary" />
    <input type="hidden" name="id" value="<?php print $glossaryItem['id']; ?>" />

    <label class="left" for="item"><?php print $PMF_LANG['ad_glossary_item']; ?>:</label>
    <input type="text" name="item" id="item" size="50" value="<?php print $glossaryItem['item']; ?>" /><br />

    <label class="left" for="definition"><?php print $PMF_LANG['ad_glossary_definition']; ?>:</label>
    <textarea name="definition" id="definition" cols="50" rows="3"><?php print $glossaryItem['definition']; ?></textarea><br />

    <input class="submit" style="margin-left: 190px;"type="submit" value="<?php print $PMF_LANG['ad_glossary_save']; ?>" />

</fieldset>
</form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
