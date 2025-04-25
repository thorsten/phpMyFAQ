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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\QuestionEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->isAddingQuestionsAllowed()) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_FORBIDDEN);
        }

        $stopWords = new StopWords($this->configuration);
        $category = new Category($this->configuration);

        $questionHelper = new QuestionHelper();
        $questionHelper
            ->setConfiguration($this->configuration)
            ->setCategory($category);

        $categories = $category->getAllCategories();

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $selectedCategory = isset($data->category) ? Filter::filterVar($data->category, FILTER_VALIDATE_INT) : false;
        $language = trim((string) Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS));
        $userQuestion = trim(strip_tags((string) $data->question));
        $save = Filter::filterVar($data->save ?? 0, FILTER_VALIDATE_INT);
        $storeNow = Filter::filterVar($data->store ?? 'not', FILTER_SANITIZE_SPECIAL_CHARS);

        // If smart answering is disabled, save the question immediately
        if (false === $this->configuration->get('main.enableSmartAnswering')) {
            $save = true;
        }

        // Validate captcha if we can store the question after displaying the smart answer
        if ($storeNow !== 'now' && !$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        // Check if all necessary fields are provided and not empty
        if (
            $author !== '' && $email !== '' && $userQuestion !== '' &&
            $stopWords->checkBannedWord($userQuestion)
        ) {
            if ($selectedCategory === false) {
                $selectedCategory = $category->getAllCategoryIds()[0];
            }

            $visibility = $this->configuration->get('records.enableVisibilityQuestions') ? 'Y' : 'N';

            $questionEntity = new QuestionEntity();
            $questionEntity
                ->setUsername($author)
                ->setEmail($email)
                ->setCategoryId($selectedCategory)
                ->setLanguage($language)
                ->setQuestion(Strings::htmlentities($userQuestion))
                ->setIsVisible($visibility === 'Y');

            // Save the question immediately if smart answering is disabled
            if (false === (bool)$save) {
                $cleanQuestion = $stopWords->clean($userQuestion);

                $faqSearch = new Search($this->configuration);
                $faqSearch->setCategory(new Category($this->configuration));
                $faqSearch->setCategoryId((int) $selectedCategory);

                $faqPermission = new Permission($this->configuration);
                $faqSearchResult = new SearchResultSet($this->currentUser, $faqPermission, $this->configuration);

                $searchResult = array_merge(...array_map(
                    fn($word) => $faqSearch->search($word, false),
                    array_filter($cleanQuestion)
                ));

                $faqSearchResult->reviewResultSet($searchResult);

                if ($faqSearchResult->getNumberOfResults() > 0) {
                    $smartAnswer = $questionHelper->generateSmartAnswer($faqSearchResult);
                    return $this->json(['result' => $smartAnswer], Response::HTTP_OK);
                }
            }

            $question = new Question($this->configuration);
            $question->add($questionEntity);
            $notification = new Notification($this->configuration);
            $notification->sendQuestionSuccessMail($questionEntity, $categories);

            return $this->json(['success' => Translation::get('msgAskThx4Mail')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('errSaveEntries')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    private function isAddingQuestionsAllowed(): bool
    {
        return $this->configuration->get('records.allowQuestionsForGuests') ||
            $this->configuration->get('main.enableAskQuestions') ||
            $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::QUESTION_ADD->value
            );
    }
}
