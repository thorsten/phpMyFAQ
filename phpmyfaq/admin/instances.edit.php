<?php
/**
 * Frontend to edit an instance
 *
 * PHP 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-04-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
    <header>
        <h2><?php print $PMF_LANG['ad_menu_instances']; ?></h2>
    </header>
<?php
if ($permission['editinstances']) {

    $instanceId = PMF_Filter::filterInput(INPUT_GET, 'instance_id', FILTER_VALIDATE_INT);

    $instance = new PMF_Instance($faqConfig);
    $instanceData = $instance->getInstanceById($instanceId);

?>
    <form class="form-horizontal" action="?action=updateinstance" method="post">
        <input type="hidden" name="instance_id" value="<?php print $instanceData->id ?>" />
        <div class="control-group">
            <label class="control-label"><?php print $PMF_LANG['ad_stat_report_url'] ?>:</label>
            <div class="controls">
                <input type="url" name="url" id="url" required="required" value="<?php print $instanceData->url ?>">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">Instance:</label>
            <div class="controls">
                <input type="text" name="instance" id="instance" required="required"
                       value="<?php print $instanceData->instance ?>">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">Site name:</label>
            <div class="controls">
                <input type="text" name="comment" id="comment" required="required"
                       value="<?php print $instanceData->comment ?>">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">Configuration:</label>
            <div class="controls">
            <?php
            foreach ($instance->getInstanceConfig($instanceData->id) as $key => $config) {
                print $key . ': ' . $config . '<br/>';
            }
            ?>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <?php print $PMF_LANG['ad_gen_save']; ?>
            </button>
            <a class="btn btn-info" href="?action=instances">
                <?php print $PMF_LANG['ad_entry_back'] ?>
            </a>
        </div>
    </form>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}