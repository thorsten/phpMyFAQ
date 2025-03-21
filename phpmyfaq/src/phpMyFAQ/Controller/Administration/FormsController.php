<?php

/**
 * The Administration Forms Controller
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
 * @since     2024-12-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\Forms\FormIds;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\TwigFilter;

class FormsController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/forms', name: 'admin.forms', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);

        $forms = $this->container->get('phpmyfaq.forms');
        $session = $this->container->get('session');

        return $this->render(
            '@admin/configuration/forms.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'formDataAskQuestion' => $forms->getFormData(FormIds::ASK_QUESTION->value),
                'formDataAddContent' => $forms->getFormData(FormIds::ADD_NEW_FAQ->value),
                'csrfActivate' => Token::getInstance($session)->getTokenString('activate-input'),
                'csrfRequired' => Token::getInstance($session)->getTokenString('require-input'),
                'ad_entry_id' => Translation::get('ad_entry_id'),
                'ad_entry_active' => Translation::get('ad_entry_active'),
                'ad_categ_translate' => Translation::get('ad_categ_translate'),
                'msgHintDeactivateForms' => Translation::get('msgHintDeactivateForms')
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/forms/translate/:formId/:inputId', name: 'admin.forms.translate', methods: ['GET'])]
    public function translate(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FORMS_EDIT);

        $formId = Filter::filterVar($request->get('formId'), FILTER_VALIDATE_INT);
        $inputId = Filter::filterVar($request->get('inputId'), FILTER_VALIDATE_INT);

        $forms = $this->container->get('phpmyfaq.forms');
        $session = $this->container->get('session');

        // Get supported languages for adding new translations
        $languages = [];
        foreach (LanguageCodes::getAllSupported() as $code => $language) {
            if (!in_array($code, $forms->getTranslatedLanguages($formId, $inputId))) {
                $languages[] = $language;
            }
        }

        // Twig filter for language codes
        // Not seperated as TwigExtension because of special function and handling of 'default'
        // value in this context
        $filter = new TwigFilter('languageCode', function ($string) {
            if ($string === 'default') {
                return $string;
            } else {
                return LanguageCodes::get($string);
            }
        });

        $this->addFilter($filter);
        return $this->render(
            '@admin/configuration/forms.translations.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'translations' => $forms->getTranslations($formId, $inputId),
                'ad_sess_pageviews' => Translation::get('ad_sess_pageviews'),
                'csrfTokenEditTranslation' => Token::getInstance($session)->getTokenString('edit-translation'),
                'csrfTokenDeleteTranslation' => Token::getInstance($session)->getTokenString('delete-translation'),
                'csrfTokenAddTranslation' => Token::getInstance($session)->getTokenString('add-translation'),
                'languages' => $languages,
                'formId' => $formId,
                'inputId' => $inputId,
            ]
        );
    }
}
