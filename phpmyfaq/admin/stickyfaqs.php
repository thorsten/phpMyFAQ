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
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

use phpMyFAQ\Faq;
use phpMyFAQ\Configuration;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\Session\Token;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('@admin/content/stickyfaqs.twig');

$faq = new Faq($faqConfig);
$stickyData = $faq->getStickyFaqsData();

$templateVars = [
    'stickyFAQsHeader' => Translation::get('stickyRecordsHeader'),
    'stickyData' => $stickyData,
    'sortableDisabled' => ($faqConfig->get('records.orderStickyFaqsCustom') === false) ? 'sortable-disabled' : '',
    'orderingStickyFaqsActivated' => $faqConfig->get('records.orderStickyFaqsCustom'),
    'alertMessageStickyFaqsDeactivated' => Translation::get('msgOrderStickyFaqsCustomDeactivated'),
    'alertMessageNoStickyRecords' => Translation::get('msgNoStickyFaqs'),
    'csrfToken' => Token::getInstance($container->get('session'))->getTokenString('order-stickyfaqs')
];

echo $template->render($templateVars);
