<?php
/**
 * Displays a form to edit an extisting glossary item
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print sprintf('<header><h2>%s</h2></header>', $PMF_LANG['ad_glossary_edit']);

if ($permission['editglossary']) {
    $id           = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $glossary     = new PMF_Glossary();
    $glossaryItem = $glossary->getGlossaryItem($id);
?>
        <form class="form-horizontal" action="?action=updateglossary" method="post">
            <input type="hidden" name="id" value="<?php print $glossaryItem['id']; ?>" />
            <div class="control-group">
                <label class="control-label" for="item"><?php print $PMF_LANG['ad_glossary_item']; ?>:</label>
                <div class="controls">
                    <input type="text" name="item" id="item" value="<?php print $glossaryItem['item']; ?>" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="definition"><?php print $PMF_LANG['ad_glossary_definition']; ?>:</label>
                <div class="controls">
                    <textarea name="definition" id="definition" cols="50" rows="3"><?php print $glossaryItem['definition']; ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG['ad_glossary_save']; ?>" />
            </div>
        </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
