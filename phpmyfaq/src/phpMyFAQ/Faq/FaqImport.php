<?php

/**
 * Class for importing records from a csv file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-05
 */

namespace phpMyFAQ\Faq;

use phpMyFAQ\Language;
use phpMyFAQ\Faq;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Faq\FaqMetaData;
use phpMyFAQ\Filter;

/**
 * Class FaqImport
 *
 * @package phpMyFAQ\Faq
 */
class FaqImport {
    
    private Configuration $config;

    public function __construct() {
        $this->config = Configuration::getConfigurationInstance();
    }

    public function import(array $record): bool|string {
        $language = new Language($this->config);
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        $user = CurrentUser::getCurrentUser($this->config);
        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->config);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $category = new Category($this->config, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        if (isset($record->faq_id)) {
            $faqId = Filter::filterVar($record->faq_id, FILTER_VALIDATE_INT);
        } else {
            $faqId = null;
        }
        $categoryId = Filter::filterVar($record[0], FILTER_VALIDATE_INT);
        $question = Filter::filterVar($record[1], FILTER_SANITIZE_SPECIAL_CHARS);
        $answer = Filter::filterVar($record[2], FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($record[3], FILTER_SANITIZE_SPECIAL_CHARS);
        $languageCode = Filter::filterVar($record[4], FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($record[5], FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($record[6], FILTER_SANITIZE_EMAIL);
        $isActive = Filter::filterVar($record[7], FILTER_VALIDATE_BOOLEAN);
        $isSticky = Filter::filterVar($record[8], FILTER_VALIDATE_BOOLEAN);

        if ($faq->hasTitleAHash($question)) {
            return 'It is not allowed, that the question title ' . $question . ' contains a hash.';
        }

        $categories = [$categoryId];
        $isActive = !is_null($isActive);
        $isSticky = !is_null($isSticky);

        $faqData = new FaqEntity();
        $faqData
                ->setLanguage($languageCode)
                ->setQuestion($question)
                ->setAnswer($answer)
                ->setKeywords($keywords)
                ->setAuthor($author)
                ->setEmail($email)
                ->setActive($isActive)
                ->setSticky($isSticky)
                ->setComment(false)
                ->setNotes('');

        if (is_null($faqId)) {
            $faqId = $faq->create($faqData);
        } else {
            $faqData->setId($faqId);
            $faqData->setRevisionId(0);
            $faq->update($faqData);
        }

        $faqMetaData = new FaqMetaData($this->config);
        $faqMetaData->setFaqId($faqId)->setFaqLanguage($languageCode)->setCategories($categories)->save();
        
        return true;
    }

    public function parseCSV($handle): array {
        while (($record = fgetcsv($handle)) !== false) {
            $csvData[] = $record;
        }
        return $csvData;
    }

    public function isCSVFile($file): bool {
        $allowedExtensions = array("csv");
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

        return in_array(strtolower($fileExtension), $allowedExtensions);
    }

}
