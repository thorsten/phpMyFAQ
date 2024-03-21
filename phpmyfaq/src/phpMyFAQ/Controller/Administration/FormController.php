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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Forms;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractController
{
    #[Route('admin/api/forms/activate')]
    public function activateInput(Request $request)
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_INT);
        $formId = Filter::filterVar($data->formid, FILTER_VALIDATE_INT);
        $inputId = Filter::filterVar($data->inputid, FILTER_VALIDATE_INT);
        $forms = new Forms(Configuration::getConfigurationInstance());
        if (!Token::getInstance()->verifyToken('activate-input', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->saveActivateInputStatus($formId, $inputId, $checked);
            return $this->json(['success' => Translation::get('msgEditFormsSuccessful')], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('admin/api/forms/required')]
    public function setInputAsRequired(Request $request)
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_INT);
        $formId = Filter::filterVar($data->formid, FILTER_VALIDATE_INT);
        $inputId = Filter::filterVar($data->inputid, FILTER_VALIDATE_INT);
        $forms = new Forms(Configuration::getConfigurationInstance());
        if (!Token::getInstance()->verifyToken('require-input', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->saveRequiredInputStatus($formId, $inputId, $checked);
            return $this->json(['success' => Translation::get('msgEditFormsSuccessful')], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('admin/api/forms/translation-edit')]
    public function editTranslation(Request $request)
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $label = Filter::filterVar($data->label, FILTER_SANITIZE_SPECIAL_CHARS);
        $formId = Filter::filterVar($data->formId, FILTER_SANITIZE_NUMBER_INT);
        $inputId = Filter::filterVar($data->inputId, FILTER_SANITIZE_NUMBER_INT);
        $lang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $forms = new Forms(Configuration::getConfigurationInstance());
        if (!Token::getInstance()->verifyToken('edit-translation', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->editTranslation($label, $formId, $inputId, $lang);
            return $this->json(['success' => Translation::get('msgFormsEditTranslationSuccessful')], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('admin/api/forms/translation-delete')]
    public function deleteTranslation(Request $request)
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $formId = Filter::filterVar($data->formId, FILTER_SANITIZE_NUMBER_INT);
        $inputId = Filter::filterVar($data->inputId, FILTER_SANITIZE_NUMBER_INT);
        $lang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $forms = new Forms(Configuration::getConfigurationInstance());
        if (!Token::getInstance()->verifyToken('delete-translation', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->deleteTranslation($formId, $inputId, $lang);
            return $this->json(
                ['success' => Translation::get('msgFormsDeleteTranslationSuccessful')],
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('admin/api/forms/translation-add')]
    public function addTranslation(Request $request)
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);
        $data = json_decode($request->getContent());
        $formId = Filter::filterVar($data->formId, FILTER_SANITIZE_NUMBER_INT);
        $inputId = Filter::filterVar($data->inputId, FILTER_SANITIZE_NUMBER_INT);
        $lang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $translation = Filter::filterVar($data->translation, FILTER_SANITIZE_SPECIAL_CHARS);
        $forms = new Forms(Configuration::getConfigurationInstance());
        if (!Token::getInstance()->verifyToken('add-translation', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $forms->addTranslation($formId, $inputId, $lang, $translation);
            return $this->json(['success' => Translation::get('msgFormsAddTranslationSuccessful')], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
