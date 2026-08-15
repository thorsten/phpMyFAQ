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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Question;
use phpMyFAQ\Question\QuestionHistoryRepository;
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
    public function __construct(
        private readonly Question $question,
        private readonly QuestionHistoryRepository $questionHistory,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws LoaderError
     */
    #[Route(path: '/questions', name: 'admin.questions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::QUESTION_DELETE);

        $question = $this->question;
        $currentLang = $this->configuration->getLanguage()->getLanguage();
        $questions = $question->getAll();
        $allQuestions = $question->getAll(true, '');
        $otherLangsData = [];
        $otherCount = 0;

        foreach ($allQuestions as $q) {
            $langCode = $q->getLanguage();

            if ($langCode !== $currentLang) {
                $otherCount++;

                $longName = $langCode;
                if (class_exists('\Locale')) {
                    $longName = \Locale::getDisplayLanguage($langCode, $currentLang);
                }

                if (!array_key_exists($langCode, $otherLangsData)) {
                    $otherLangsData[$langCode] = [
                        'label' => $longName,
                        'count' => 0,
                    ];
                }
                $otherLangsData[$langCode]['count']++;
            }
        }

        $this->addExtension(new IntlExtension());
        $this->addExtension(new AttributeExtension(CategoryNameTwigExtension::class));
        return $this->render('@admin/content/open-questions.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'msgOpenQuestions' => Translation::get(key: 'msgOpenQuestions'),
            'csrfTokenDeleteQuestion' => Token::getInstance($this->session)->getTokenString('delete-questions'),
            'currentLocale' => $currentLang,
            'msgQuestion' => Translation::get(key: 'msgQuestion'),
            'msgVisibility' => Translation::get(key: 'ad_entry_visibility'),
            'questions' => $questions,
            'otherCount' => $otherCount,
            'otherLangsData' => $otherLangsData,
            'yes' => Translation::get(key: 'ad_gen_yes'),
            'no' => Translation::get(key: 'ad_gen_no'),
            'enableCloseQuestion' => $this->configuration->get(item: 'records.enableCloseQuestion'),
            'msg2answerFAQ' => Translation::get(key: 'msg2answerFAQ'),
            'msgTakeQuestion' => Translation::get(key: 'ad_ques_take'),
            'csrfTokenToggleVisibility' => Token::getInstance($this->session)->getTokenString(
                'toggle-question-visibility',
            ),
            'msgDeleteAllOpenQuestions' => Translation::get(key: 'msgDelete'),
            'msgAttention' => Translation::get(key: 'msgAttention'),
            'msgOtherQuestionsDesc' => Translation::get(key: 'msgOtherQuestionsDesc'),
            'msgOtherQuestionDesc' => Translation::get(key: 'msgOtherQuestionDesc'),
            'msgChangeLanguageHint' => Translation::get(key: 'msgChangeLanguageHint'),
            'msgOpenQuestion' => Translation::get(key: 'msgOpenQuestion'),
            'csrfTokenReopenQuestion' => Token::getInstance($this->session)->getTokenString('reopen-question'),
            'msgReopenQuestion' => Translation::get(key: 'msgReopenQuestion'),
            'msgQuestionHistory' => Translation::get(key: 'msgQuestionHistory'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     */
    #[Route(path: '/questions/history/{questionId}', name: 'admin.questions.history', methods: ['GET'])]
    public function history(Request $request, int $questionId): Response
    {
        $this->userHasPermission(PermissionType::QUESTION_DELETE);

        $currentLang = $this->configuration->getLanguage()->getLanguage();

        $entries = [];
        foreach ($this->questionHistory->getByQuestion($questionId, $currentLang) as $row) {
            $entries[] = [
                'eventType' => (string) $row['event_type'],
                'userId' => (int) $row['user_id'],
                'username' => (string) $row['username'],
                'faqId' => (int) $row['faq_id'],
                'created' => Date::createIsoDate((string) $row['created']),
            ];
        }

        $this->addExtension(new IntlExtension());
        return $this->render('@admin/content/open-question-history.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'msgQuestionHistory' => Translation::get(key: 'msgQuestionHistory'),
            'msgQuestionHistoryDate' => Translation::get(key: 'msgQuestionHistoryDate'),
            'msgQuestionHistoryEvent' => Translation::get(key: 'msgQuestionHistoryEvent'),
            'msgAuthor' => Translation::get(key: 'msgAuthor'),
            'msgNoQuestionHistory' => Translation::get(key: 'msgNoQuestionHistory'),
            'msgOpenQuestions' => Translation::get(key: 'msgOpenQuestions'),
            'question' => $this->question->get($questionId),
            'entries' => $entries,
            'eventLabels' => [
                'submitted' => Translation::get(key: 'questionHistoryEventSubmitted'),
                'answered' => Translation::get(key: 'questionHistoryEventAnswered'),
                'reopened' => Translation::get(key: 'questionHistoryEventReopened'),
            ],
        ]);
    }
}
