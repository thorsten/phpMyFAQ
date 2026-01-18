<?php

/**
 * The Admin Translation Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-17
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Translation\ContentTranslationService;
use phpMyFAQ\Translation\DTO\TranslationRequest;
use phpMyFAQ\Translation\Exception\TranslationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TranslationController extends AbstractAdministrationApiController
{
    /**
     * Translates content using a configured AI translation provider
     *
     * @throws \Exception
     */
    #[Route(path: 'admin/api/content/translate', name: 'admin.api.content.translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_TRANSLATE);

        $data = json_decode($request->getContent(), true);

        if (!Token::getInstance($this->session)->verifyToken('translate', $data['pmf-csrf-token'] ?? '')) {
            return $this->json([
                'success' => false,
                'error' => 'CSRF - ' . Translation::get(key: 'msgNoPermission'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validate required fields
        $contentType = $data['contentType'] ?? '';
        $sourceLang = $data['sourceLang'] ?? '';
        $targetLang = $data['targetLang'] ?? '';
        $fields = $data['fields'] ?? [];

        if (empty($contentType) || empty($sourceLang) || empty($targetLang) || empty($fields)) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required parameters',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate content type
        $validContentTypes = ['faq', 'customPage', 'category', 'news'];
        if (!in_array($contentType, $validContentTypes, true)) {
            return $this->json(['success' => false, 'error' => 'Invalid content type'], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var ContentTranslationService $translationService */
            $translationService = $this->container->get(id: 'phpmyfaq.translation.content-translation-service');

            $translationRequest = new TranslationRequest($contentType, $sourceLang, $targetLang, $fields);

            $result = match ($contentType) {
                'faq' => $translationService->translateFaq($translationRequest),
                'customPage' => $translationService->translateCustomPage($translationRequest),
                'category' => $translationService->translateCategory($translationRequest),
                'news' => $translationService->translateNews($translationRequest),
                default => throw new TranslationException('Unsupported content type'),
            };

            if ($result->isSuccess()) {
                $logType = match ($contentType) {
                    'faq' => AdminLogType::FAQ_TRANSLATE,
                    'customPage' => AdminLogType::PAGE_TRANSLATE,
                    'category' => AdminLogType::CATEGORY_TRANSLATE,
                    'news' => AdminLogType::NEWS_TRANSLATE,
                    default => throw new TranslationException('Unsupported content type'),
                };

                $this->adminLog->log($this->currentUser, $logType->value . ':' . $sourceLang . '->' . $targetLang);

                return $this->json([
                    'success' => true,
                    'translatedFields' => $result->getTranslatedFields(),
                ], Response::HTTP_OK);
            }

            return $this->json([
                'success' => false,
                'error' => $result->getError(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (TranslationException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
