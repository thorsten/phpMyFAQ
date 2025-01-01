<?php

/**
 * The Admin Glossary Controller
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
 * @since     2024-01-27
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GlossaryController extends AbstractController
{
    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/glossary')]
    public function fetch(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_EDIT);

        $glossaryId = Filter::filterVar($request->get('glossaryId'), FILTER_VALIDATE_INT);
        $glossaryLanguage = Filter::filterVar($request->get('glossaryLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);

        $glossary = $this->container->get('phpmyfaq.glossary');
        $glossary->setLanguage($glossaryLanguage);

        return $this->json($glossary->fetch($glossaryId), Response::HTTP_OK);
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/glossary/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_DELETE);

        $data = json_decode($request->getContent());

        $glossaryId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $glossaryLanguage = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-glossary', $data->csrf)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $glossary = $this->container->get('phpmyfaq.glossary');
        $glossary->setLanguage($glossaryLanguage);

        if ($glossary->delete($glossaryId)) {
            return $this->json(['success' => Translation::get('ad_glossary_delete_success')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_glossary_delete_error')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/glossary/create')]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_ADD);

        $data = json_decode($request->getContent());

        $glossaryLanguage = Filter::filterVar($data->language, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryItem = Filter::filterVar($data->item, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryDefinition = Filter::filterVar($data->definition, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('add-glossary', $data->csrf)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $glossary = $this->container->get('phpmyfaq.glossary');
        $glossary->setLanguage($glossaryLanguage);

        if ($glossary->create($glossaryItem, $glossaryDefinition)) {
            return $this->json(['success' => Translation::get('ad_glossary_save_success')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_glossary_save_error')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/glossary/update')]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_EDIT);

        $data = json_decode($request->getContent());

        $glossaryId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $glossaryLanguage = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryItem = Filter::filterVar($data->item, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryDefinition = Filter::filterVar($data->definition, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('update-glossary', $data->csrf)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $glossary = $this->container->get('phpmyfaq.glossary');
        $glossary->setLanguage($glossaryLanguage);

        if ($glossary->update($glossaryId, $glossaryItem, $glossaryDefinition)) {
            return $this->json(['success' => Translation::get('ad_glossary_update_success')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_glossary_update_error')], Response::HTTP_BAD_REQUEST);
        }
    }
}
