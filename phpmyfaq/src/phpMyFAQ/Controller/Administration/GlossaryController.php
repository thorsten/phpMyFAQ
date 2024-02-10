<?php

/**
 * The Admin Glossary Controller
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GlossaryController extends AbstractController
{
    #[Route('admin/api/glossary')]
    public function fetch(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_EDIT);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $glossaryId = Filter::filterVar($request->get('glossaryId'), FILTER_VALIDATE_INT);

        $glossary = new Glossary($configuration);

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData($glossary->fetch($glossaryId));

        return $jsonResponse;
    }

    #[Route('admin/api/glossary/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_DELETE);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        $glossaryId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);

        if (!Token::getInstance()->verifyToken('delete-glossary', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        $glossary = new Glossary($configuration);

        if ($glossary->delete($glossaryId)) {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_glossary_delete_success')]);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => Translation::get('ad_glossary_delete_error')]);
        }

        return $jsonResponse;
    }

    #[Route('admin/api/glossary/add')]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_ADD);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        $glossaryItem = Filter::filterVar($data->item, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryDefinition = Filter::filterVar($data->definition, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('add-glossary', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        $glossary = new Glossary($configuration);

        if ($glossary->create($glossaryItem, $glossaryDefinition)) {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_glossary_save_success')]);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => Translation::get('ad_glossary_save_error')]);
        }

        return $jsonResponse;
    }

    #[Route('admin/api/glossary/update')]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_EDIT);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        $glossaryId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $glossaryItem = Filter::filterVar($data->item, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryDefinition = Filter::filterVar($data->definition, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('update-glossary', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        $glossary = new Glossary($configuration);

        if ($glossary->update($glossaryId, $glossaryItem, $glossaryDefinition)) {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_glossary_update_success')]);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => Translation::get('ad_glossary_update_error')]);
        }

        return $jsonResponse;
    }
}
