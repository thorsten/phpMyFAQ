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
    #[Route('admin/api/glossary/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_DELETE);

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        $glossaryId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);

        if (!Token::getInstance()->verifyToken('delete-glossary', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);

            return $response;
        }

        $glossary = new Glossary($configuration);

        if ($glossary->deleteGlossaryItem($glossaryId)) {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_glossary_delete_success')]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('ad_glossary_delete_error')]);
        }

        return $response;
    }

    #[Route('admin/api/glossary/add')]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GLOSSARY_ADD);

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        $glossaryItem = Filter::filterVar($data->item, FILTER_SANITIZE_SPECIAL_CHARS);
        $glossaryDefinition = Filter::filterVar($data->definition, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('add-glossary', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);

            return $response;
        }

        $glossary = new Glossary($configuration);

        if ($glossary->addGlossaryItem($glossaryItem, $glossaryDefinition)) {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_glossary_save_success')]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('ad_glossary_save_error')]);
        }

        return $response;
    }
}
