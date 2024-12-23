<?php

/**
 * The Administration FAQs Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class FaqController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/faqs', name: 'admin.faqs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);
        $this->userHasPermission(PermissionType::FAQ_APPROVE);
        $this->userHasPermission(PermissionType::FAQ_EDIT);
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryRelation = new Relation($this->configuration, $category);
        $categoryRelation->setGroups($currentAdminGroups);

        $comments = $this->container->get('phpmyfaq.comments');
        $sessions = $this->container->get('session');

        return $this->render(
            '@admin/content/faq.overview.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'csrfTokenSearch' => Token::getInstance($sessions)->getTokenInput('edit-faq'),
                'csrfTokenOverview' => Token::getInstance($sessions)->getTokenString('faq-overview'),
                'categories' => $category->getCategoryTree(),
                'numberOfRecords' => $categoryRelation->getNumberOfFaqsPerCategory(),
                'numberOfComments' => $comments->getNumberOfCommentsByCategory(),
                'msgComments' => Translation::get('ad_start_comments'),
                'msgQuestion' => Translation::get('ad_entry_theme'),
                'msgDate' => Translation::get('ad_entry_date'),
                'msgSticky' => Translation::get('ad_entry_sticky'),
                'msgActive' => Translation::get('ad_record_active'),
            ]
        );
    }
}
