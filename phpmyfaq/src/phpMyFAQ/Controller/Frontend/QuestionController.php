<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class QuestionController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function create(Request $request): JsonResponse
    {
        $user = CurrentUser::getCurrentUser($this->configuration);

        $faq = new Faq($this->configuration);
        $faqHelper = new FaqHelper($this->configuration);
        $category = new Category($this->configuration);
        $question = new Question($this->configuration);
        $stopWords = new StopWords($this->configuration);
        $categories = $category->getAllCategories();

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!$this->isAddingQuestionsAllowed($user)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_FORBIDDEN);
        }

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $selectedCategory = Filter::filterVar($data->category, FILTER_VALIDATE_INT);
        $userQuestion = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $userQuestion = trim(strip_tags((string) $userQuestion));
        $save = Filter::filterVar($data->save ?? 0, FILTER_VALIDATE_INT);

        // If e-mail address is set to optional
        if (!$this->configuration->get('main.optionalMailAddress') && empty($email)) {
            $email = $this->configuration->getAdminEmail();
        }

        // If smart answering is disabled, save the question immediately
        if (false === $this->configuration->get('main.enableSmartAnswering')) {
            $save = true;
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        if (
            $author !== '' && $author !== '0' && ($email !== '' && $email !== '0') &&
            ($userQuestion !== '' && $userQuestion !== '0') && $stopWords->checkBannedWord($userQuestion)
        ) {
            $visibility = $this->configuration->get('records.enableVisibilityQuestions') ? 'Y' : 'N';

            $questionData = [
                'username' => $author,
                'email' => $email,
                'category_id' => $selectedCategory,
                'question' => Strings::htmlentities($userQuestion),
                'is_visible' => $visibility
            ];

            if (false === (bool)$save) {
                $cleanQuestion = $stopWords->clean($userQuestion);

                $faqSearch = new Search($this->configuration);
                $faqSearch->setCategory(new Category($this->configuration));
                $faqSearch->setCategoryId((int) $selectedCategory);

                $faqPermission = new FaqPermission($this->configuration);
                $faqSearchResult = new SearchResultSet($user, $faqPermission, $this->configuration);

                $plr = new Plurals();
                $searchResult = [];
                $mergedResult = [];

                foreach ($cleanQuestion as $word) {
                    if (!empty($word)) {
                        try {
                            $searchResult[] = $faqSearch->search($word, false);
                        } catch (Exception) {
                            // @todo handle exception
                        }
                    }
                }

                foreach ($searchResult as $resultSet) {
                    foreach ($resultSet as $result) {
                        $mergedResult[] = $result;
                    }
                }

                $faqSearchResult->reviewResultSet($mergedResult);

                if (0 < $faqSearchResult->getNumberOfResults()) {
                    $smartAnswer = sprintf(
                        '<h5>%s</h5>',
                        $plr->getMsg('plmsgSearchAmount', $faqSearchResult->getNumberOfResults())
                    );

                    $smartAnswer .= '<ul>';

                    foreach ($faqSearchResult->getResultSet() as $result) {
                        $url = sprintf(
                            '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                            $this->configuration->getDefaultUrl(),
                            $result->category_id,
                            $result->id,
                            $result->lang
                        );
                        $link = new Link($url, $this->configuration);
                        $link->text = Utils::chopString($result->question, 15);
                        $link->itemTitle = $result->question;

                        try {
                            $smartAnswer .= sprintf(
                                '<li>%s<br><small class="pmf-search-preview">%s...</small></li>',
                                $link->toHtmlAnchor(),
                                $faqHelper->renderAnswerPreview($result->answer, 10)
                            );
                        } catch (Exception) {
                            // handle exception
                        }
                    }

                    $smartAnswer .= '</ul>';

                    return $this->json(['result' => $smartAnswer], Response::HTTP_OK);
                } else {
                    $question->addQuestion($questionData);
                    $questionHelper = new QuestionHelper($this->configuration, $category);
                    try {
                        $questionHelper->sendSuccessMail($questionData, $categories);
                    } catch (Exception | TransportExceptionInterface $exception) {
                        return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                    }
                    return $this->json(['success' => Translation::get('msgAskThx4Mail')], Response::HTTP_OK);
                }
            } else {
                $question->addQuestion($questionData);
                $questionHelper = new QuestionHelper($this->configuration, $category);
                try {
                    $questionHelper->sendSuccessMail($questionData, $categories);
                } catch (Exception | TransportExceptionInterface $exception) {
                    return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                return $this->json(['success' => Translation::get('msgAskThx4Mail')], Response::HTTP_OK);
            }
        } else {
            return $this->json(['error' => Translation::get('errSaveEntries')], Response::HTTP_BAD_REQUEST);
        }
    }

    private function isAddingQuestionsAllowed(CurrentUser $user): bool
    {
        if (
            !$this->configuration->get('records.allowQuestionsForGuests') &&
            !$this->configuration->get('main.enableAskQuestions') &&
            !$user->perm->hasPermission($user->getUserId(), PermissionType::QUESTION_ADD->value)
        ) {
            return false;
        }

        return true;
    }
}
