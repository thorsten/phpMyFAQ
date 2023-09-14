<?php

/**
 * The reporting page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-01-12
 */

use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'reports')) {
    ?>

    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="fa fa-tasks"></i> <?= Translation::get('ad_menu_reports') ?>
        </h1>
    </div>

    <div class="container">
        <form action="?action=reportview" method="post" accept-charset="utf-8">
            <h4><?= Translation::get('ad_stat_report_fields') ?></h4>

            <div class="row mb-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_category" name="report_category"
                           value="1" checked>
                    <label class="form-check-label" for="report_category">
                        <?= Translation::get('ad_stat_report_category') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_sub_category" name="report_sub_category"
                           value="1" checked>
                    <label class="form-check-label" for="report_sub_category">
                        <?= Translation::get('ad_stat_report_sub_category') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_translations"
                           name="report_translations" value="1" checked>
                    <label class="form-check-label" for="report_translations">
                        <?= Translation::get('ad_stat_report_translations') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_language" name="report_language"
                           value="1" checked>
                    <label class="form-check-label" for="report_language">
                        <?= Translation::get('ad_stat_report_language') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_id" name="report_id" value="1" checked>
                    <label class="form-check-label" for="report_id">
                        <?= Translation::get('ad_stat_report_id') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_sticky" name="report_sticky" value="1"
                           checked>
                    <label class="form-check-label" for="report_sticky">
                        <?= Translation::get('ad_stat_report_sticky') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_title" name="report_title" value="1"
                           checked>
                    <label class="form-check-label" for="report_title">
                        <?= Translation::get('ad_stat_report_title') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_creation_date"
                           name="report_creation_date" value="1" checked>
                    <label class="form-check-label" for="report_creation_date">
                        <?= Translation::get('ad_stat_report_creation_date') ?>
                    </label>
                </div>

                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_owner" name="report_owner" value="1"
                           checked>
                    <label class="form-check-label" for="report_owner">
                        <?= Translation::get('ad_stat_report_owner') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_last_modified_person"
                           name="report_last_modified_person" value="1" checked>
                    <label class="form-check-label" for="report_last_modified_person">
                        <?= Translation::get('ad_stat_report_last_modified_person') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_url" name="report_url" value="1" checked>
                    <label class="form-check-label" for="report_url">
                        <?= Translation::get('ad_stat_report_url') ?>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="report_visits" name="report_visits" value="1"
                           checked>
                    <label class="form-check-label" for="report_visits">
                        <?= Translation::get('ad_stat_report_visits') ?>
                    </label>
                </div>
            </div>

            <div class="row mb-2">
                <button class="btn btn-primary" type="submit">
                    <?= Translation::get('ad_stat_report_make_report') ?>
                </button>
            </div>

        </form>

    </div>

    <?php
} else {
    echo Translation::get('err_NotAuth');
}
