<?php

/**
 * Private phpMyFAQ Admin API: lists the complete configuration items as text/html.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-26
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\AdministrationHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$configMode = Filter::filterVar($request->query->get('conf'), FILTER_SANITIZE_SPECIAL_CHARS, 'main');

/**
 * @param mixed  $key
 * @param string $type
 */
function renderInputForm(mixed $key, string $type): void
{
    $faqConfig = Configuration::getConfigurationInstance();

    switch ($type) {
        case 'select':
            printf('<select name="edit[%s]" class="form-select">', $key);

            switch ($key) {
                case 'upgrade.releaseEnvironment':
                    printf(
                        '<option value="%s" %s>Development</option>',
                        ReleaseType::DEVELOPMENT->value,
                        (ReleaseType::DEVELOPMENT->value === $faqConfig->get($key)) ? 'selected' : ''
                    );
                    printf(
                        '<option value="%s" %s>Stable</option>',
                        ReleaseType::STABLE->value,
                        (ReleaseType::STABLE->value === $faqConfig->get($key)) ? 'selected' : ''
                    );
                    printf(
                        '<option value="%s" %s>Nightly</option>',
                        ReleaseType::NIGHTLY->value,
                        (ReleaseType::NIGHTLY->value === $faqConfig->get($key)) ? 'selected' : ''
                    );
                    break;
            }

            echo "</select>\n</div>\n";
            break;
    }
}

header('Content-type: text/html; charset=utf-8');

foreach (Translation::getConfigurationItems() as $key => $value) {
    if (str_starts_with($key, $configMode)) {
        printf(
            '<div class="row my-2"><label class="col-lg-3 col-form-label %s">',
            $value['element'] === 'checkbox' || $value['element'] === 'radio' ? 'pt-0' : ''
        );

        switch ($key) {
            case 'records.maxAttachmentSize':
                printf($value['label'], ini_get('upload_max_filesize'));
                break;
            case 'main.dateFormat':
                printf(
                    '<a target="_blank" href="https://www.php.net/manual/%s/function.date.php">%s</a>',
                    $faqLangCode,
                    $value['label']
                );
                break;
            default:
                echo $value['label'];
                break;
        }
        ?>
      </label>
      <div class="col-lg-6">
          <?php renderInputForm($key, $value['element']); ?>
      </div>
        <?php
    }
}
