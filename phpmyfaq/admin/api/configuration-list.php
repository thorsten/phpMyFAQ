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
                case 'records.attachmentsStorageType':
                    foreach (Translation::get('att_storage_type') as $i => $item) {
                        $selected = (int)$faqConfig->get($key) === $i ? ' selected' : '';
                        printf('<option value="%d"%s>%s</option>', $i, $selected, $item);
                    }
                    break;

                case 'records.orderingPopularFaqs':
                    printf(
                        '<option value="visits"%s>%s</option>',
                        ('visits' === $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('records.orderingPopularFaqs.visits')
                    );
                    printf(
                        '<option value="voting"%s>%s</option>',
                        ('voting' === $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('records.orderingPopularFaqs.voting')
                    );
                    break;

                case 'search.relevance':
                    printf(
                        '<option value="thema,content,keywords"%s>%s</option>',
                        ('thema,content,keywords' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.thema-content-keywords')
                    );
                    printf(
                        '<option value="thema,keywords,content"%s>%s</option>',
                        (
                            'thema,keywords,content' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.thema-keywords-content')
                    );
                    printf(
                        '<option value="content,thema,keywords"%s>%s</option>',
                        ('content,thema,keywords' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.content-thema-keywords')
                    );
                    printf(
                        '<option value="content,keywords,thema"%s>%s</option>',
                        ('content,keywords,thema' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.content-keywords-thema')
                    );
                    printf(
                        '<option value="keywords,content,thema"%s>%s</option>',
                        ('keywords,content,thema' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.keywords-content-thema')
                    );
                    printf(
                        '<option value="keywords,thema,content"%s>%s</option>',
                        ('keywords,thema,content' == $faqConfig->get($key)) ? ' selected' : '',
                        Translation::get('search.relevance.keywords-thema-content')
                    );
                    break;

                case 'seo.metaTagsHome':
                case 'seo.metaTagsFaqs':
                case 'seo.metaTagsCategories':
                case 'seo.metaTagsPages':
                case 'seo.metaTagsAdmin':
                    $adminHelper = new AdministrationHelper();
                    echo $adminHelper->renderMetaRobotsDropdown($faqConfig->get($key));
                    break;

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
