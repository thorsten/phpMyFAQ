<?php

/**
 * Frontend for importing records from a csv file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */
use phpMyFAQ\Configuration;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Faq\FaqImport;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Tags;
use DateTime;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Search\Elasticsearch;
use phpMyFAQ\Filter;
use phpMyFAQ\Visits;
use phpMyFAQ\Category\CategoryRelation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), 'add_faq')) {
    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/content/csv.import.twig');
    $faqImport = new FaqImport();
    $templateVars = [];

    if (isset($_FILES['userfile']) && 0 === $_FILES['userfile']['error'] && $faqImport->isCSVFile($_FILES['userfile'])) {
        $handle = fopen($_FILES['userfile']['tmp_name'], 'r');
        $csvData = $faqImport->parseCSV($handle);
        foreach ($csvData as $record) {
            $faq = new Faq($faqConfig);
            $category = new Category($faqConfig, [], false);
            $category->setUser($currentAdminUser);
            $category->setGroups($currentAdminGroups);

            $categoryPermission = new CategoryPermission($faqConfig);
            $tagging = new Tags($faqConfig);

            $faqData = new FaqEntity();
            $faqData
                    ->setLanguage($record[5])
                    ->setActive('yes')
                    ->setSticky(false)
                    ->setQuestion(
                            Filter::removeAttributes(html_entity_decode((string) $record[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                    )
                    ->setAnswer(
                            Filter::removeAttributes(html_entity_decode((string) $record[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                    )
                    ->setKeywords($record[3])
                    ->setAuthor($record[6])
                    ->setEmail($record[7])
                    ->setCreatedDate(new DateTime())
                    ->setNotes('')
                    ->setComment(false);

            // Add new record and get that ID
            $recordId = $faq->create($faqData);

            if ($recordId) {
                // Create ChangeLog entry
                $changelog = new Changelog($faqConfig);
                $changelog->add($recordId, $user->getUserId(), 'csvImport', $faqData->getLanguage());

                // Create the visit entry
                $visits = new Visits($faqConfig);
                $visits->logViews($recordId);

                $categoryRelation = new CategoryRelation($faqConfig, $category);
                $categoryRelation->add(array($record[0]), $recordId, $faqData->getLanguage());

                // Insert the tags
                if ($tags !== '') {
                    $tagging->saveTags($recordId, explode(',', trim((string) $record[4])));
                }

                // If Elasticsearch is enabled, index new FAQ document
                if ($faqConfig->get('search.enableElasticsearch')) {
                    $esInstance = new Elasticsearch($faqConfig);
                    $esInstance->index(
                            [
                                'id' => $recordId,
                                'lang' => $record[5],
                                'solution_id' => $solutionId,
                                'question' => $faqData->getQuestion(),
                                'answer' => $faqData->getAnswer(),
                                'keywords' => $record[3],
                                'category_id' => $record[0]
                            ]
                    );
                }
            }
        }

        $templateVars = [
            ...$templateVars,
            'successAlert' => Translation::get('msgImportSuccessful')
        ];
    }
    $templateVars = [
        ...$templateVars,
        'adminHeaderImport' => Translation::get('msgImportRecords'),
        'adminHeaderCSVImport' => Translation::get('msgImportCSVFile'),
        'adminBodyCSVImport' => Translation::get('msgImportCSVFileBody'),
        'adminImportLabel' => Translation::get('ad_csv_file'),
        'adminCSVImport' => Translation::get('msgImport'),
        'adminHeaderCSVImportColumns' => Translation::get('msgColumnStructure'),
        'categoryId' => Translation::get('ad_categ_categ'),
        'question' => Translation::get('ad_entry_topic'),
        'answer' => Translation::get('ad_entry_content'),
        'keywords' => Translation::get('ad_entry_keywords'),
        'author' => Translation::get('ad_entry_author'),
        'email' => Translation::get('ad_entry_email'),
        'languageCode' => Translation::get('msgLanguageCode'),
        'seperateWithCommas' => Translation::get('msgSeperateWithCommas'),
        'tags' => Translation::get('ad_entry_tags'),
        'msgImportRecordsColumnStructure' => Translation::get('msgImportRecordsColumnStructure'),
        'csrfToken' => Token::getInstance()->getTokenString('importcsv')
    ];
    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
} 