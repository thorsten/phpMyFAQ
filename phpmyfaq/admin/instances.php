<?php
/**
 * The main multi-site instances frontend
 *
 * PHP 5.2
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
 * @since     2012-03-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
    <header>
        <h2><?php print $PMF_LANG['ad_menu_instances']; ?></h2>
        <?php if ($permission['addinstances']): ?>
        <div>
            <a class="btn btn-primary" data-toggle="modal" href="#pmf-modal-add-instance">add new phpMyFAQ site</a>
        </div>
        <?php endif; ?>
    </header>
<?php
if ($permission['editinstances']) {
    $instance = new PMF_Instance($faqConfig);
?>

    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>URL</th>
            <th>Instance</th>
            <th colspan="4">site name</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($instance->getAllInstances() as $site): ?>
        <tr>
            <td><?php print $site->id ?></td>
            <td><a href="http://<?php print $site->url.$site->instance ?>"><?php print $site->url ?></a></td>
            <td><?php print $site->instance ?></td>
            <td><?php print $site->comment ?></td>
            <td><a href="#" class="btn btn-info">edit</a></td>
            <td><a href="#" class="btn btn-danger">delete</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="modal fade" id="pmf-modal-add-instance">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>
            <h3>Add new phpMyFAQ site</h3>
        </div>
        <div class="modal-body">
            <form class="form-horizontal" action="#" method="post">
                <div class="control-group">
                    <label class="control-label"><?php print $PMF_LANG['ad_stat_report_url'] ?>:</label>
                    <div class="controls">
                        <input type="text" name="url" id="url">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">Instance:</label>
                    <div class="controls">
                        <input type="text" name="instance" id="instance">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">Site name:</label>
                    <div class="controls">
                        <input type="text" name="comment" id="comment">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <a href="#" class="btn btn-primary">Save changes</a>
        </div>
    </div>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}