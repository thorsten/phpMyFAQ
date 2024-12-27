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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\Forms\FormIds;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

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
                'msgEditForms' => Translation::get('msgEditForms'),
                'formDataAskQuestion' => $forms->getFormData(FormIds::ASK_QUESTION->value),
                'formDataAddContent' => $forms->getFormData(FormIds::ADD_NEW_FAQ->value),
                'msgQuestion' => Translation::get('msgQuestion'),
                'msgAddContent' => Translation::get('msgAddContent'),
                'csrfActivate' => Token::getInstance($session)->getTokenString('activate-input'),
                'csrfRequired' => Token::getInstance($session)->getTokenString('require-input'),
                'ad_entry_id' => Translation::get('ad_entry_id'),
                'msgInputLabel' => Translation::get('msgInputLabel'),
                'msgInputType' => Translation::get('msgInputType'),
                'ad_entry_active' => Translation::get('ad_entry_active'),
                'msgRequiredInputField' => Translation::get('msgRequiredInputField'),
                'msgFormsEditTranslations' => Translation::get('msgFormsEditTranslations'),
                'ad_categ_translate' => Translation::get('ad_categ_translate'),
                'msgHintDeactivateForms' => Translation::get('msgHintDeactivateForms')
            ]
        );
    }
}
