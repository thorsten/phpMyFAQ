<?php
/**
 * Displays the section management frontend.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2005-2019 phpMyFAQ Team
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
    !$user->perm->checkRight($user->getUserId(), 'delete_section') &&
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
    'list',
    'update_members',
    'update_data',
    'delete_confirm',
    'delete',
    'addsave',
    'add'
];

// what shall we do?
// actions defined by url: section_action=
$sectionAction = Filter::filterInput(INPUT_GET, 'section_action', FILTER_SANITIZE_STRING, $defaultSectionAction);
// actions defined by submit button
if (isset($_POST['section_action_deleteConfirm'])) {
    $sectionAction = 'delete_confirm';
}
if (isset($_POST['cancel'])) {
    $sectionAction = $defaultSectionAction;
}

// validate sectionAction
if (!in_array($sectionAction, $sectionActionList)){
    // @Todo: implement Error message
}

// update section members
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
          $perm->addGroupToSection((int) $memberId, $sectionId);
      }
      $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
          $PMF_LANG['ad_msg_savedsuc_1'],
          $perm->getSectionName($sectionId),
          $PMF_LANG['ad_msg_savedsuc_2']);
  }
}

// update section data
if ($sectionAction == 'update_data' && $user->perm->checkRight($user->getUserId(), 'edit_section')) {
  $message = '';
  $sectionAction = $defaultSectionAction;
  $sectionId = Filter::filterInput(INPUT_POST, 'section_id', FILTER_VALIDATE_INT, 0);
  if ($sectionId == 0) {
      $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
  } else {
      $sectionData = [];
      $dataFields = ['name', 'description'];
      foreach ($dataFields as $field) {
          $sectionData[$field] = Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_STRING, '');
      }
      $user = new User($faqConfig);
      $perm = $user->perm;
      if (!$perm->changeSection($sectionId, $sectionData)) {
          $message .= sprintf(
          '<p class="alert alert-danger">%s<br>%s</p>',
          $PMF_LANG['ad_msg_mysqlerr'],
          $db->error()
          );
      } else {
          $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
              $PMF_LANG['ad_msg_savedsuc_1'],
              $perm->getSectionName($sectionId),
              $PMF_LANG['ad_msg_savedsuc_2']);
      }
  }
}

// delete section confirmation
if ($sectionAction == 'delete_confirm' && $user->perm->checkRight($user->getUserId(), 'delete_section')) {
  $message = '';
  $user = new CurrentUser($faqConfig);
  $perm = $user->perm;
  $sectionId = Filter::filterInput(INPUT_POST, 'section_list_select', FILTER_VALIDATE_INT, 0);
  if ($sectionId <= 0) {
      $message    .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
      $sectionAction = $defaultSectionAction;
  } else {
      $sectionData = $perm->getSectionData($sectionId);
      ?>
      <header class="row">
          <div class="col-lg-12">
              <h2 class="page-header">
                  <i aria-hidden="true" class="fas fa-users fa-fw"></i>
                  <?= $PMF_LANG['ad_section_deleteSection'] ?> "<?= $sectionData['name'] ?>"
              </h2>
          </div>
      </header>

      <div class="row">
          <div class="col-lg-12">
              <p><?= $PMF_LANG['ad_section_deleteQuestion'] ?></p>
              <form action ="?action=section&amp;section_action=delete" method="post">
                  <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                  <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession()?>">
                  <p>
                      <button class="btn btn-inverse" type="submit" name="cancel">
                          <?= $PMF_LANG['ad_gen_cancel'] ?>
                      </button>
                      <button class="btn btn-primary" type="submit">
                          <?= $PMF_LANG['ad_gen_delete'] ?>
                      </button>
                  </p>
              </form>
          </div>
      </div>
<?php

  }
}

if ($sectionAction == 'delete' && $user->perm->checkRight($user->getUserId(), 'delete_section')) {
  $message = '';
  $user = new User($faqConfig);
  $sectionId = Filter::filterInput(INPUT_POST, 'section_id', FILTER_VALIDATE_INT, 0);
  $csrfOkay = true;
  $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
  if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
      $csrfOkay = false;
  }
  $sectionAction = $defaultSectionAction;
  if ($sectionId <= 0) {
      $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
  } else {
      if (!$user->perm->deleteSection($sectionId) && !$csrfOkay) {
          $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_section_error_delete']);
      } else {
          $message .= sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_section_deleted']);
      }
      $userError = $user->error();
      if ($userError != '') {
          $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
      }
  }
}

if ($sectionAction == 'addsave' && $user->perm->checkRight($user->getUserId(), 'add_section')) {
  $user = new User($faqConfig);
  $message = '';
  $messages = [];
  $sectionName = Filter::filterInput(INPUT_POST, 'section_name', FILTER_SANITIZE_STRING, '');
  $sectionDescription = Filter::filterInput(INPUT_POST, 'section_description', FILTER_SANITIZE_STRING, '');
  $csrfOkay = true;
  $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

  if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
      $csrfOkay = false;
  }
  // check section name
  if ($sectionName == '') {
      $messages[] = $PMF_LANG['ad_section_error_noName'];
  }
  // ok, let's go
  if (count($messages) == 0 && $csrfOkay) {
      // create section
      $sectionData = array(
          'name' => $sectionName,
          'description' => $sectionDescription
      );

      if ($user->perm->addSection($sectionData) <= 0) {
          $messages[] = $PMF_LANG['ad_adus_dberr'];
      }
  }
  // no errors, show list
  if (count($messages) == 0) {
      $sectionAction = $defaultSectionAction;
      $message = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_section_suc']);
  // display error messages and show form again
  } else {
      $sectionAction = 'add';
      $message = '<p class="alert alert-danger">';
      foreach ($messages as $err) {
          $message .= $err.'<br>';
      }
      $message .= '</p>';
  }
}

if (!isset($message)) {
  $message = '';
}

// show new section form
if ($sectionAction == 'add' && $user->perm->checkRight($user->getUserId(), 'add_section')) {
  $user = new CurrentUser($faqConfig);
  ?>

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
          <i aria-hidden="true" class="fas fa-layer-group"></i>
            <?= $PMF_LANG['ad_section_add'] ?>
        </h1>
      </div>

      <div class="row">
          <div class="col-lg-12">
              <div id="user_message"><?= $message ?></div>
              <form  name="section_create" action="?action=section&amp;section_action=addsave" method="post">
                  <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">

                  <div class="form-group row">
                      <label class="col-lg-2 col-form-label" for="section_name"><?= $PMF_LANG['ad_section_name'] ?></label>
                      <div class="col-lg-3">
                          <input type="text" name="section_name" id="section_name" autofocus class="form-control"
                                 value="<?=(isset($sectionName) ? $sectionName : '') ?>" tabindex="1">
                      </div>
                  </div>

                  <div class="form-group row">
                      <label class="col-lg-2 col-form-label" for="section_description"><?= $PMF_LANG['ad_section_description'] ?></label>
                      <div class="col-lg-3">
                          <textarea name="section_description" id="section_description" cols="<?= $descriptionCols ?>"
                                    rows="<?= $descriptionRows ?>" tabindex="2"  class="form-control"
                              ><?=(isset($sectionDescription) ? $sectionDescription : '') ?></textarea>
                      </div>
                  </div>

                  <div class="form-group row">
                      <div class="offset-lg-2 col-lg-3">
                          <button class="btn btn-primary" type="submit">
                              <?= $PMF_LANG['ad_gen_save'] ?>
                          </button>
                          <button class="btn btn-info" type="reset" name="cancel">
                              <?= $PMF_LANG['ad_gen_cancel'] ?>
                          </button>
                      </div>
                  </div>
              </form>
          </div>
      </div>
<?php

} // end if ($sectionAction == 'add')

// show list of sections
if ('list' === $sectionAction) {
    ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-layer-group"></i>
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

      <div id="section_data" class="card">
        <div class="card-header">
            <?= $PMF_LANG['ad_section_details'] ?>
        </div>
        <form action="?action=section&section_action=update_data" method="post">
          <input id="update_section_id" type="hidden" name="section_id" value="0">
          <div class="card-body">
            <div class="form-group row">
              <label class="col-lg-3 col-form-label" for="update_section_name">
                  <?= $PMF_LANG['ad_section_name'] ?>
              </label>
              <div class="col-lg-9">
                <input id="update_section_name" type="text" name="name" class="form-control"
                       tabindex="1" value="<?= (isset($sectionName) ? $sectionName : '') ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-lg-3 col-form-label" for="update_section_description">
                  <?= $PMF_LANG['ad_section_description'] ?>
              </label>
              <div class="col-lg-9">
                <textarea name="description" id="update_section_description" cols="<?= $descriptionCols ?>"
                          rows="<?= $descriptionRows ?>" tabindex="2"  class="form-control"
                ><?= isset($sectionDescription) ? $sectionDescription : '' ?></textarea>
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
                                        <i aria-hidden="true" class="fas fa-layer-group"></i>
                                    </a>
                                </span>
                <span class="unselect_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectUnselectAll('group_list_select')">
                                        <i aria-hidden="true" class="fas fa-user-minus"></i>
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
          </div>

          <ul class="list-group list-group-flush">
            <li class="list-group-item bg-light"><?= $PMF_LANG['ad_section_members']; ?></li>
          </ul>

          <div class="card-body">
            <div class="form-group row">
              <div class="float-right">
                <span class="select_all">
                    <a class="btn btn-primary btn-sm"
                       href="javascript:selectSelectAll('section_member_list')">
                        <i aria-hidden="true" class="fas fa-layer-group"></i>
                    </a>
                </span>
                <span class="unselect_all">
                  <a class="btn btn-primary btn-sm"
                     href="javascript:selectUnselectAll('section_member_list')">
                       <i aria-hidden="true" class="fas fa-user-minus"></i>
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
