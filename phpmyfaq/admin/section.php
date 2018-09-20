<?php
/**
 * Displays the section management frontend.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2005-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-09-20
 */

use phpMyFAQ\Filter;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$user->perm->checkRight($user->getUserId(), 'editsection') &&
    !$user->perm->checkRight($user->getUserId(), 'delsection') &&
    !$user->perm->checkRight($user->getUserId(), 'addsection')) {
    exit();
}

// set some parameters
//$groupSelectSize = 10;
//$memberSelectSize = 7;
//$descriptionRows = 3;
//$descriptionCols = 15;
$defaultSectionAction = 'list';

// what shall we do?
// actions defined by url: section_action=
$sectionAction = Filter::filterInput(INPUT_GET, 'section_action', FILTER_SANITIZE_STRING, $defaultSectionAction);
// actions defined by submit button
if (isset($_POST['section_action_deleteConfirm'])) {
    $sectionAction = 'delete_confirm';
}
if (isset($_POST['cancel'])) {
    $sectionAction = $defaultGroupAction;
}

// show list of sections
if ('list' === $sectionAction) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i class="material-icons md-36">people</i>
              <?= $PMF_LANG['ad_menu_section_administration'] ?>
          </h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
              <a class="btn btn-sm btn-outline-success" href="?action=group&amp;group_action=add">
                  <?= $PMF_LANG['ad_group_add_link'] ?>
              </a>
            </div>
          </div>
        </div>

        <script src="assets/js/user.js"></script>
        <script src="assets/js/groups.js"></script>

  <div id="user_message"><?= $message ?></div>

  <div class="row">

    <div class="col-lg-4" id="group_list">
      <div class="card">
        <form id="group_select" name="group_select" action="?action=group&amp;group_action=delete_confirm"
              method="post">
          <div class="card-header">
              <?= $PMF_LANG['ad_groups'] ?>
          </div>
          <div class="card-body">
            <select name="group_list_select" id="group_list_select" class="form-control"
                    size="<?= $groupSelectSize ?>" tabindex="1">
            </select>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-danger" type="submit">
                  <?= $PMF_LANG['ad_gen_delete'] ?>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div id="group_data" class="card">
        <div class="card-header">
            <?= $PMF_LANG['ad_group_details'] ?>
        </div>
        <form action="?action=group&group_action=update_data" method="post">
          <input id="update_group_id" type="hidden" name="group_id" value="0">
          <div class="card-body">
            <div class="form-group row">
              <label class="col-lg-3 form-control-label" for="update_group_name">
                  <?= $PMF_LANG['ad_group_name'] ?>
              </label>
              <div class="col-lg-9">
                <input id="update_group_name" type="text" name="name" class="form-control"
                       tabindex="1" value="<?= (isset($group_name) ? $group_name : '') ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-lg-3 form-control-label" for="update_group_description">
                  <?= $PMF_LANG['ad_group_description'] ?>
              </label>
              <div class="col-lg-9">
                                    <textarea id="update_group_description" name="description" class="form-control"
                                              rows="<?= $descriptionRows ?>"
                                              tabindex="2"><?php
                                        echo(isset($group_description) ? $group_description : '') ?></textarea>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-lg-offset-3 col-lg-9">
                <div class="checkbox">
                  <label>
                    <input id="update_group_auto_join" type="checkbox" name="auto_join" value="1"
                           tabindex="3"<?php
                    echo((isset($group_auto_join) && $group_auto_join) ? ' checked' : '') ?>>
                      <?= $PMF_LANG['ad_group_autoJoin'] ?>
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-4" id="groupMemberships">
      <form id="group_membership" name="group_membership" method="post"
            action="?action=group&amp;group_action=update_members">
        <input id="update_member_group_id" type="hidden" name="group_id" value="0">
        <div class="card">
          <div class="card-header">
              <?= $PMF_LANG['ad_group_membership'] ?>
          </div>
          <div class="card-body">
            <div class="form-group row">
              <div class="text-right">
                                <span class="select_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectSelectAll('group_user_list')">
                                        <i aria-hidden="true" class="material-icons">people</i>
                                    </a>
                                </span>
                <span class="unselect_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectUnselectAll('group_user_list')">
                                        <i aria-hidden="true" class="material-icons">people_outline</i>
                                    </a>
                                </span>
              </div>
            </div>

            <div class="form-group row">
              <select id="group_user_list" class="form-control" size="<?= $memberSelectSize ?>"
                      multiple>
                <option value="0">...user list...</option>
              </select>
            </div>

            <div class="form-group row">
              <div class="text-center">
                <input class="btn btn-success pmf-add-member" type="button"
                       value="<?= $PMF_LANG['ad_group_addMember'] ?>">
                <input class="btn btn-danger pmf-remove-member" type="button"
                       value="<?= $PMF_LANG['ad_group_removeMember'] ?>">
              </div>
            </div>

            <div class="form-group row">
                <?= $PMF_LANG['ad_group_members'] ?>
              <div class="float-right">
                <span class="select_all">
                    <a class="btn btn-primary btn-sm"
                       href="javascript:selectSelectAll('group_member_list')">
                        <i aria-hidden="true" class="material-icons">people</i>
                    </a>
                </span>
                <span class="unselect_all">
                  <a class="btn btn-primary btn-sm"
                     href="javascript:selectUnselectAll('group_member_list')">
                      <i aria-hidden="true" class="material-icons">people_outline</i>
                  </a>
                </span>
              </div>
            </div>

            <div class="form-group row">
              <select id="group_member_list" name="group_members[]" class="form-control" multiple
                      size="<?= $memberSelectSize ?>">
                <option value="0">...member list...</option>
              </select>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" onclick="javascript:selectSelectAll('group_member_list')" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-4" id="groupDetails">

      <div id="groupRights" class="card">
        <form id="rightsForm" action="?action=group&amp;group_action=update_rights" method="post">
          <input id="rights_group_id" type="hidden" name="group_id" value="0">
          <div class="card-header" id="user_rights_legend">
            <i aria-hidden="true" class="fa fa-lock"></i> <?= $PMF_LANG['ad_group_rights'] ?>
            <span class="float-right">
              <a class="btn btn-secondary btn-sm" href="#" id="checkAll">
                <?= $PMF_LANG['ad_user_checkall'] ?> / <?= $PMF_LANG['ad_user_uncheckall'] ?>
              </a>
            </span>
          </div>

          <div class="card-body">
            <?php foreach ($user->perm->getAllRightsData() as $right): ?>
              <div class="form-check">
                <input id="user_right_<?= $right['right_id'] ?>" type="checkbox"
                       name="user_rights[]" value="<?= $right['right_id'] ?>"
                       class="form-check-input permission">
                <label class="form-check-label">
                    <?php
                    if (isset($PMF_LANG['rightsLanguage'][$right['name']])) {
                        echo $PMF_LANG['rightsLanguage'][$right['name']];
                    } else {
                        echo $right['description'];
                    }
                    ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
              </button>
            </div>
          </div>
      </div>
      </form>
    </div>
  </div>
  </div>
<?php

}