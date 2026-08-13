<?php

/**
 * The Question & Smart Answer Controller
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
 * @since     2024-03-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\QuestionEntity;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class QuestionController extends AbstractController
{
    /**
     * Session flag proving that a captcha-validated first request has already
     * displayed a smart answer. Only then may the "store now" confirmation step
     * skip the (already consumed) captcha.
     */
    private const string SMART_ANSWER_SESSION_KEY = 'phpmyfaq.question.smartAnswerShown';

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->isAddingQuestionsAllowed()) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_FORBIDDEN);
        }

        $stopWords = $this->container->get(id: 'phpmyfaq.stop-words');
        $category = new Category($this->configuration);

        $questionHelper = $this->container->get(id: 'phpmyfaq.helper.question');
        $questionHelper->setConfiguration($this->configuration)->setCategory($category);

        $categories = $category->getAllCategories();

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $session = $this->container->get(id: 'session');

        if (!Token::getInstance($session)->verifyToken(
            page: 'add-question',
            requestToken: $data->{'pmf-csrf-token'} ?? null,
        )) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $email = Filter::filterVar($email, FILTER_SANITIZE_SPECIAL_CHARS);
        $selectedCategory = isset($data->category) ? Filter::filterVar($data->category, FILTER_VALIDATE_INT) : false;
        $language = trim((string) Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS));
        $userQuestion = trim(strip_tags((string) $data->question));
        $save = Filter::filterVar($data->save ?? 0, FILTER_VALIDATE_INT);
        $storeNow = Filter::filterVar($data->store ?? 'not', FILTER_SANITIZE_SPECIAL_CHARS);

        // If smart answering is disabled, save the question immediately
        if (false === $this->configuration->get(item: 'main.enableSmartAnswering')) {
            $save = true;
        }

        // The captcha may only be skipped for the "store now" confirmation step when a
        // captcha-validated first request has already displayed a smart answer and set the
        // server-side flag below. Otherwise the captcha (single-use) must be validated here.
        if ($storeNow === 'now') {
            if (!$this->isSmartAnswerConfirmationAllowed($session)) {
                return $this->json(['error' => Translation::get(key: 'msgCaptcha')], Response::HTTP_BAD_REQUEST);
            }

            $session->remove(self::SMART_ANSWER_SESSION_KEY);
        } elseif (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get(key: 'msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        // Check if all necessary fields are provided and not empty
        if ($author !== '' && $email !== '' && $userQuestion !== '' && $stopWords->checkBannedWord($userQuestion)) {
            if ($selectedCategory === false) {
                $selectedCategory = $category->getAllCategoryIds()[0];
            }

            $visibility = $this->configuration->get(item: 'records.enableVisibilityQuestions') ? 'Y' : 'N';

            $questionEntity = new QuestionEntity();
            $questionEntity
                ->setUsername($author)
                ->setEmail($email)
                ->setCategoryId($selectedCategory)
                ->setLanguage($language)
                ->setQuestion($userQuestion)
                ->setIsVisible($visibility === 'Y');

            // Save the question immediately if smart answering is disabled
            if (false === (bool) $save) {
                $cleanQuestion = $stopWords->clean($userQuestion);

                $faqSearch = $this->container->get(id: 'phpmyfaq.search');
                $faqSearch->setCategory(new Category($this->configuration));
                $faqSearch->setCategoryId((int) $selectedCategory);

                $faqPermission = new Permission($this->configuration);
                $searchResultSet = new SearchResultSet($this->currentUser, $faqPermission, $this->configuration);

                $searchResult = array_merge(...array_map(static fn($word) => $faqSearch->search(
                    $word,
                    allLanguages: false,
                ), array_filter($cleanQuestion)));

                $searchResultSet->reviewResultSet($searchResult);

                if ($searchResultSet->getNumberOfResults() > 0) {
                    // Remember that this captcha-validated request produced a smart answer, so the
                    // subsequent "store now" confirmation is allowed to skip the consumed captcha.
                    $session->set(self::SMART_ANSWER_SESSION_KEY, true);

                    $smartAnswer = $questionHelper->generateSmartAnswer($searchResultSet);
                    return $this->json(['result' => $smartAnswer], Response::HTTP_OK);
                }
            }

            $question = $this->container->get(id: 'phpmyfaq.question');
            $question->add($questionEntity);

            try {
                $notification = $this->container->get(id: 'phpmyfaq.notification');
                $notification->sendQuestionSuccessMail($questionEntity, $categories);
            } catch (\Throwable $e) {
                $this->configuration->getLogger()->info('Notification could not be sent: ', [$e->getMessage()]);
            }

            return $this->json(['success' => Translation::get(key: 'msgAskThx4Mail')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'errSaveEntries')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Whether the "store now" confirmation step may proceed without a captcha. This is only
     * true after a previous captcha-validated request displayed a smart answer and set the
     * server-side flag; a forged request that jumps straight to "store now" has no such flag.
     */
    private function isSmartAnswerConfirmationAllowed(SessionInterface $session): bool
    {
        return (bool) $session->get(self::SMART_ANSWER_SESSION_KEY, false);
    }

    /**
     * @throws \Exception
     */
    private function isAddingQuestionsAllowed(): bool
    {
        if (!$this->configuration->get(item: 'main.enableAskQuestions')) {
            return false;
        }

        $isGuest = -1 === $this->currentUser->getUserId();

        return !$isGuest || (bool) $this->configuration->get(item: 'records.allowQuestionsForGuests');
    }
}
