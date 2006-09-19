<?php
/**
* $Id: glossary.add.php,v 1.9 2006-09-19 21:28:33 matteo Exp $
*
* Displays a form to add a glossary item
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
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print sprintf('<h2>%s</h2>', $PMF_LANG['ad_menu_glossary']);

if ($permission['addglossary']) {
?>
<form action="<?php print $_SERVER['PHP_SELF'].$linkext; ?>" method="post">
<fieldset>
    <legend><?php print $PMF_LANG['ad_glossary_add']; ?></legend>

    <input type="hidden" name="action" value="saveglossary" />

    <label class="left" for="item"><?php print $PMF_LANG['ad_glossary_item']; ?>:</label>
    <input type="text" name="item" id="item" size="50" /><br />

    <label class="left" for="definition"><?php print $PMF_LANG['ad_glossary_definition']; ?>:</label>
    <textarea name="definition" id="definition" cols="50" rows="3"></textarea><br />

    <input class="submit" style="margin-left: 190px;" type="submit" value="<?php print $PMF_LANG['ad_glossary_save']; ?>" />

</fieldset>
</form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
