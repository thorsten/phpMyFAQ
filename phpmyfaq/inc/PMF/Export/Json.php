<?php

/**
 * JSON Export class for phpMyFAQ.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-29
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Export_Xml.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-29
 */
class PMF_Export_Json extends PMF_Export
{
    /**
     * Constructor.
     *
     * @param PMF_Faq           $faq      Faq object
     * @param PMF_Category      $category Category object
     * @param PMF_Configuration $config   Configuration
     *
     * return PMF_Export_Json
     */
    public function __construct(PMF_Faq $faq, PMF_Category $category, PMF_Configuration $config)
    {
        $this->faq = $faq;
        $this->category = $category;
        $this->_config = $config;
    }

    /**
     * Generates the export.
     *
     * @param int    $categoryId Category Id
     * @param bool   $downwards  If true, downwards, otherwise upward ordering
     * @param string $language   Language
     *
     * @return string
     */
    public function generate($categoryId = 0, $downwards = true, $language = '')
    {
        $generated = [];

        // Initialize categories
        $this->category->transform($categoryId);

        $faqdata = $this->faq->get(FAQ_QUERY_TYPE_EXPORT_XML, $categoryId, $downwards, $language);

        if (count($faqdata)) {
            foreach ($faqdata as $data) {

                $generated[] = [
                    'faq' => [
                        'id' => $data['id'],
                        'language' => $data['lang'],
                        'category' => $this->category->getPath($data['category_id'], ' >> '),
                        'keywords' => $data['keywords'],
                        'question' => strip_tags($data['topic']),
                        'answer' => PMF_String::htmlspecialchars($data['content']),
                        'author' => $data['author_name'],
                        'last_modified' => PMF_Date::createIsoDate($data['lastmodified'])
                    ]
                ];
            }
        }

        header('Content-type: application/json');

        return json_encode($generated);
    }
}
