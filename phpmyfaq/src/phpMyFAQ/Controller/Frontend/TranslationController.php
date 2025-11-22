<?php

declare(strict_types=1);

/**
 * The Translations Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-03-17
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TranslationController extends AbstractController
{
    #[Route(path: 'api/translations/{language}', name: 'api.private.translations', methods: ['GET'])]
    public function translations(Request $request): JsonResponse
    {
        $language = Filter::filterVar($request->get('language'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Language::isASupportedLanguage($language)) {
            return $this->json(['error' => 'Language not supported'], Response::HTTP_BAD_REQUEST);
        }

        try {
            Translation::getInstance()->setCurrentLanguage($language);
            return $this->json(Translation::getAll());
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
