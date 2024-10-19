<?php

/**
 * Shows all comments in the categories and provides a link to delete comments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-04
 */

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\FaqTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use Twig\Extra\Intl\IntlExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);
[$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($user);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new IntlExtension());
$twig->addExtension(new FaqTwigExtension());
$template = $twig->loadTemplate('./admin/content/comments.twig');

if ($user->perm->hasPermission($user->getUserId(), PermissionType::COMMENT_DELETE->value)) {
    $comment = new Comments($faqConfig);
    $faq = new Faq($faqConfig);
    $date = new Date($faqConfig);

    $faqComments = $comment->getAllComments();
    $newsComments = $comment->getAllComments(CommentType::NEWS);

    $templateVars = [
        'currentLocale' => $faqConfig->getLanguage()->getLanguage(),
        'faqComments' => $faqComments,
        'newsComments' => $newsComments,
        'csrfToken' => Token::getInstance()->getTokenString('delete-comment'),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
