<?php

/**
 * The Tag Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class TagController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/tags', name: 'admin.tags', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userIsAuthenticated();

        $tagData = $this->container->get(id: 'phpmyfaq.tags')->getAllTags();

        return $this->render('@admin/content/tags.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderTags' => Translation::get(key: 'msgTags'),
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenInput('tags'),
            'tags' => $tagData,
            'noTags' => Translation::get(key: 'ad_news_nodata'),
            'buttonEdit' => Translation::get(key: 'ad_user_edit'),
            'msgConfirm' => Translation::get(key: 'ad_user_del_3'),
            'buttonDelete' => Translation::get(key: 'msgDelete'),
        ]);
    }
}
