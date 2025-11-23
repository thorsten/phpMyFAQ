<?php

declare(strict_types=1);

/**
 * The Admin Form Controller
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Forms;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FormController extends AbstractController
{
    #[Route(path: 'admin/api/forms/activate')]
    public function activateInput(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_INT);
        $formId = Filter::filterVar($data->formid, FILTER_VALIDATE_INT);
        $inputId = Filter::filterVar($data->inputid, FILTER_VALIDATE_INT);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('activate-input', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->saveActivateInputStatus($formId, $inputId, $checked);
            return $this->json(['success' => Translation::get(key: 'msgEditFormsSuccessful')], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'admin/api/forms/required')]
    public function setInputAsRequired(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_INT);
        $formId = Filter::filterVar($data->formid, FILTER_VALIDATE_INT);
        $inputId = Filter::filterVar($data->inputid, FILTER_VALIDATE_INT);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('require-input', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->saveRequiredInputStatus($formId, $inputId, $checked);
            return $this->json(['success' => Translation::get(key: 'msgEditFormsSuccessful')], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'admin/api/forms/translation-edit')]
    public function editTranslation(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $label = Filter::filterVar($data->label, FILTER_SANITIZE_SPECIAL_CHARS);
        $formId = Filter::filterVar($data->formId, FILTER_SANITIZE_NUMBER_INT);
        $inputId = Filter::filterVar($data->inputId, FILTER_SANITIZE_NUMBER_INT);
        $lang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('edit-translation', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->editTranslation($label, $formId, $inputId, $lang);
            return $this->json(['success' => Translation::get(
                key: 'msgFormsEditTranslationSuccessful',
            )], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/forms/translation-delete')]
    public function deleteTranslation(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $formId = Filter::filterVar($data->formId, FILTER_SANITIZE_NUMBER_INT);
        $inputId = Filter::filterVar($data->inputId, FILTER_SANITIZE_NUMBER_INT);
        $lang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('delete-translation', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->deleteTranslation($formId, $inputId, $lang);
            return $this->json(['success' => Translation::get(
                'msgFormsDeleteTranslationSuccessful',
            )], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'admin/api/forms/translation-add')]
    public function addTranslation(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);

        $data = json_decode($request->getContent());

        $formId = Filter::filterVar($data->formId, FILTER_SANITIZE_NUMBER_INT);
        $inputId = Filter::filterVar($data->inputId, FILTER_SANITIZE_NUMBER_INT);
        $lang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $translation = Filter::filterVar($data->translation, FILTER_SANITIZE_SPECIAL_CHARS);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('add-translation', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->addTranslation($formId, $inputId, $lang, $translation);
            return $this->json(['success' => Translation::get(
                key: 'msgFormsAddTranslationSuccessful',
            )], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
