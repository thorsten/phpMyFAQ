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

if (!$user->perm->checkRight($user->getUserId(), 'edit_section') &&
    !$user->perm->checkRight($user->getUserId(), 'del_section') &&
    !$user->perm->checkRight($user->getUserId(), 'add_section')) {
    exit();
}

// set some parameters
$sectionSelectSize = 10;
$memberSelectSize = 7;
$descriptionRows = 3;
$descriptionCols = 15;
$defaultSectionAction = 'list';
$sectionActionList = [
    'list'
];

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

// validate sectionAction
if (!in_array($sectionAction, $sectionActionList)){
    // @Todo: implement Error message
}

// update group members
if ($sectionAction == 'update_members' && $user->perm->checkRight($user->getUserId(), 'edit_section')) {
  $message = '';
  $sectionAction = $defaultSectionAction;
  $sectionId = Filter::filterInput(INPUT_POST, 'section_id', FILTER_VALIDATE_INT, 0);
  $sectionMembers = isset($_POST['section_members']) ? $_POST['section_members'] : [];

  if ($sectionId == 0) {
      $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
  } else {
      $user = new User($faqConfig);
      $perm = $user->perm;
      if (!$perm->removeAllGroupsFromSection($sectionId)) {
          $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
      }
      foreach ($sectionMembers as $memberId) {
          $perm->addToGroup((int) $memberId, $sectionId);
      }
      $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
          $PMF_LANG['ad_msg_savedsuc_1'],
          $perm->getSectionName($sectionId),
          $PMF_LANG['ad_msg_savedsuc_2']);
  }
}

if (!isset($message)) {
  $message = '';
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
              <a class="btn btn-sm btn-outline-success" href="?action=section&amp;section_action=add">
                  <?= $PMF_LANG['ad_section_add_link'] ?>
              </a>
            </div>
          </div>
        </div>

        <script src="assets/js/user.js"></script>
        <script src="assets/js/groups.js"></script>
        <script src="assets/js/sections.js"></script>

  <div id="user_message"><?= $message ?></div>

  <div class="row">

    <div class="col-lg-6" id="section_list">
      <div class="card">
        <form id="section_select" name="section_select" action="?action=section&amp;section_action=delete_confirm"
              method="post">
          <div class="card-header">
              <?= $PMF_LANG['ad_sections'] ?>
          </div>
          <div class="card-body">
            <select name="section_list_select" id="section_list_select" class="form-control"
                    size="<?= $sectionSelectSize ?>" tabindex="1">
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
            <?= $PMF_LANG['ad_section_details'] ?>
        </div>
        <form action="?action=section&section_action=update_data" method="post">
          <input id="update_section_id" type="hidden" name="section_id" value="0">
          <div class="card-body">
            <div class="form-group row">
              <label class="col-lg-3 form-control-label" for="update_section_name">
                  <?= $PMF_LANG['ad_section_name'] ?>
              </label>
              <div class="col-lg-9">
                <input id="update_section_name" type="text" name="name" class="form-control"
                       tabindex="1" value="<?= (isset($section_name) ? $section_name : '') ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-lg-3 form-control-label" for="update_section_description">
                  <?= $PMF_LANG['ad_section_description'] ?>
              </label>
              <div class="col-lg-9">
                                    <textarea id="update_section_description" name="description" class="form-control"
                                              rows="<?= $descriptionRows ?>"
                                              tabindex="2"><?php
                                        echo(isset($section_description) ? $section_description : '') ?></textarea>
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

    <div class="col-lg-6" id="sectionMemberships">
      <form id="section_membership" name="section_membership" method="post"
            action="?action=section&amp;section_action=update_members">
        <input id="update_member_section_id" type="hidden" name="section_id" value="0">
        <div class="card">
          <div class="card-header">
              <?= $PMF_LANG['ad_section_membership'] ?>
          </div>
          <div class="card-body">
            <div class="form-group row">
              <div class="text-right">
                                <span class="select_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectSelectAll('group_list_select')">
                                        <i aria-hidden="true" class="material-icons">people</i>
                                    </a>
                                </span>
                <span class="unselect_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectUnselectAll('group_list_select')">
                                        <i aria-hidden="true" class="material-icons">people_outline</i>
                                    </a>
                                </span>
              </div>
            </div>

            <div class="form-group row">
              <select id="group_list_select" class="form-control" size="<?= $memberSelectSize ?>"
                      multiple>
                <option value="0">...group list...</option>
              </select>
            </div>

            <div class="form-group row">
              <div class="text-center">
                <input class="btn btn-success pmf-add-section-member" type="button"
                       value="<?= $PMF_LANG['ad_section_addMember'] ?>">
                <input class="btn btn-danger pmf-remove-section-member" type="button"
                       value="<?= $PMF_LANG['ad_section_removeMember'] ?>">
              </div>
            </div>

            <div class="form-group row">
                <?= $PMF_LANG['ad_section_members'] ?>
              <div class="float-right">
                <span class="select_all">
                    <a class="btn btn-primary btn-sm"
                       href="javascript:selectSelectAll('section_member_list')">
                        <i aria-hidden="true" class="material-icons">people</i>
                    </a>
                </span>
                <span class="unselect_all">
                  <a class="btn btn-primary btn-sm"
                     href="javascript:selectUnselectAll('section_member_list')">
                      <i aria-hidden="true" class="material-icons">people_outline</i>
                  </a>
                </span>
              </div>
            </div>

            <div class="form-group row">
              <select id="section_member_list" name="section_members[]" class="form-control" multiple
                      size="<?= $memberSelectSize ?>">
                <option value="0">...member list...</option>
              </select>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" onclick="javascript:selectSelectAll('section_member_list')" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
<?php

}
