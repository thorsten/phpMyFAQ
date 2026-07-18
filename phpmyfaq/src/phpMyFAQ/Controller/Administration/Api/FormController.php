<?php

/**
 * The Admin Form Controller
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

declare(strict_types=1);

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
    #[Route(path: 'forms/activate', name: 'admin.api.forms.activate', methods: ['PUT'])]
    public function activateInput(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = $this->getJsonObject($request);
        // The frontend sends "checked" as a JSON boolean. FILTER_VALIDATE_INT turns false
        // into null (validation failure), which breaks deactivating an input, so validate
        // it as a boolean and cast to the 0/1 integer the Forms layer expects.
        $checked = (int) Filter::filterVar($data->checked ?? false, FILTER_VALIDATE_BOOLEAN, false);
        $formId = (int) Filter::filterVar($data->formid ?? null, FILTER_VALIDATE_INT);
        $inputId = (int) Filter::filterVar($data->inputid ?? null, FILTER_VALIDATE_INT);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->session)->verifyToken('activate-input', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->saveActivateInputStatus($formId, $inputId, $checked);
            return $this->json(['success' => Translation::get(key: 'msgEditFormsSuccessful')], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'forms/required', name: 'admin.api.forms.required', methods: ['PUT'])]
    public function setInputAsRequired(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = $this->getJsonObject($request);
        // The frontend sends "checked" as a JSON boolean. FILTER_VALIDATE_INT turns false
        // into null (validation failure), which breaks marking an input as not required, so
        // validate it as a boolean and cast to the 0/1 integer the Forms layer expects.
        $checked = (int) Filter::filterVar($data->checked ?? false, FILTER_VALIDATE_BOOLEAN, false);
        $formId = (int) Filter::filterVar($data->formid ?? null, FILTER_VALIDATE_INT);
        $inputId = (int) Filter::filterVar($data->inputid ?? null, FILTER_VALIDATE_INT);

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->session)->verifyToken('require-input', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->saveRequiredInputStatus($formId, $inputId, $checked);
            return $this->json(['success' => Translation::get(key: 'msgEditFormsSuccessful')], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'forms/translation-edit', name: 'admin.api.forms.translation-edit', methods: ['PUT'])]
    public function editTranslation(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = $this->getJsonObject($request);
        $label = Filter::filterVar($data->label ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $formId = (int) Filter::filterVar($data->formId ?? null, FILTER_VALIDATE_INT);
        $inputId = (int) Filter::filterVar($data->inputId ?? null, FILTER_VALIDATE_INT);
        $lang = Filter::filterVar($data->lang ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->session)->verifyToken('edit-translation', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->editTranslation($label, $formId, $inputId, $lang);
            return $this->json([
                'success' => Translation::get(key: 'msgFormsEditTranslationSuccessful'),
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(
        path: 'admin/api/forms/translation-delete',
        name: 'admin.api.forms.translation-delete',
        methods: ['DELETE'],
    )]
    public function deleteTranslation(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = $this->getJsonObject($request);
        $formId = (int) Filter::filterVar($data->formId ?? null, FILTER_VALIDATE_INT);
        $inputId = (int) Filter::filterVar($data->inputId ?? null, FILTER_VALIDATE_INT);
        $lang = Filter::filterVar($data->lang ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->session)->verifyToken('delete-translation', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->deleteTranslation($formId, $inputId, $lang);
            return $this->json([
                'success' => Translation::get('msgFormsDeleteTranslationSuccessful'),
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'forms/translation-add', name: 'admin.api.forms.translation-add', methods: ['POST'])]
    public function addTranslation(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);

        $data = $this->getJsonObject($request);

        $formId = (int) Filter::filterVar($data->formId ?? null, FILTER_VALIDATE_INT);
        $inputId = (int) Filter::filterVar($data->inputId ?? null, FILTER_VALIDATE_INT);
        $lang = Filter::filterVar($data->lang ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $translation = Filter::filterVar($data->translation ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        $forms = new Forms($this->configuration);
        if (!Token::getInstance($this->session)->verifyToken('add-translation', (string) ($data->csrf ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->addTranslation($formId, $inputId, $lang, $translation);
            return $this->json([
                'success' => Translation::get(key: 'msgFormsAddTranslationSuccessful'),
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
