<?php

/**
 * The Open Question Controller
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
 * @since     2024-12-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\CategoryNameTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;
use Twig\Extra\Intl\IntlExtension;

final class OpenQuestionsController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     */
    #[Route('/question')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::QUESTION_DELETE);

        $session = $this->container->get(id: 'session');
        $question = $this->container->get(id: 'phpmyfaq.question');

        $this->addExtension(new IntlExtension());
        $this->addExtension(new AttributeExtension(CategoryNameTwigExtension::class));
        return $this->render('@admin/content/open-questions.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'msgOpenQuestions' => Translation::get(languageKey: 'msgOpenQuestions'),
            'csrfTokenDeleteQuestion' => Token::getInstance($session)->getTokenString('delete-questions'),
            'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
            'msgQuestion' => Translation::get(languageKey: 'msgQuestion'),
            'msgVisibility' => Translation::get(languageKey: 'ad_entry_visibility'),
            'questions' => $question->getAll(),
            'yes' => Translation::get(languageKey: 'ad_gen_yes'),
            'no' => Translation::get(languageKey: 'ad_gen_no'),
            'enableCloseQuestion' => $this->configuration->get(item: 'records.enableCloseQuestion'),
            'msg2answerFAQ' => Translation::get(languageKey: 'msg2answerFAQ'),
            'msgTakeQuestion' => Translation::get(languageKey: 'ad_ques_take'),
            'csrfTokenToggleVisibility' => Token::getInstance($session)->getTokenString('toggle-question-visibility'),
            'msgDeleteAllOpenQuestions' => Translation::get(languageKey: 'msgDelete'),
        ]);
    }
}
