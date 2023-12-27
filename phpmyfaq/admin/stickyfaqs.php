<?php
/**
 * Shows the function for ordering sticky faqs customly.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

use Twig\Extension\DebugExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Faq;
use phpMyFAQ\Configuration;
use phpMyFAQ\Translation;
use phpMyFAQ\Database;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$faqTableInfo = $faqConfig->getDb()->getTableStatus(Database::getTablePrefix());
$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$template = $twig->loadTemplate('./admin/content/stickyfaqs.twig');

$faq = new Faq($faqConfig);
$data = $faq->getStickyRecordsData();

$stickyFaqsList = '';

foreach ($data as $sticky) {
    $stickyFaqsList .= sprintf('<li class="list-group-item" data-pmf-faqid="%d"><a href="%s">%s</a></li>',
        $sticky['id'],
        $sticky['url'],
        $sticky['question']);
}

$templateVars = [
    'stickyFAQsHeader' => Translation::get('stickyRecordsHeader'),
    'generatedListStickyFaqs' => $stickyFaqsList,
    'sortableDisabled' => ($faqConfig->get('records.orderStickyFaqsCustom') === false) ? 'sortable-disabled' : ''
];

if ($faqConfig->get('records.orderStickyFaqsCustom') === false) {
    $templateVars = [
        ...$templateVars,
        'alert' => sprintf(
            '<div class="alert alert-warning alert-dismissible fade show">%s<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Translation::get('msgOrderStickyFaqsCustomDeactivated')
        )
    ];
}

echo $template->render($templateVars);
