<?php

/**
 * The Administration Stop Words Controller
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
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class StopWordsController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/stopwords', name: 'admin.stopwords', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $hasPermission = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::CONFIGURATION_EDIT,
        );

        return $this->render('@admin/configuration/stopwords.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderStopWords' => Translation::get('ad_menu_stopwordsconfig'),
            'hasPermission' => $hasPermission,
            'msgDescription' => Translation::get('ad_stopwords_desc'),
            'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenInput('stopwords'),
            'msgStopWordsLabel' => Translation::get('ad_stopwords_desc'),
            'sortedLanguageCodes' => LanguageCodes::getAllSorted(),
            'buttonAdd' => Translation::get('ad_config_stopword_input'),
        ]);
    }
}
