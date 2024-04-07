<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData;
use phpMyFAQ\Faq\QueryHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\Session;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class FaqController extends AbstractController
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
        $session = new Session($this->configuration);
        $session->setCurrentUser($user);

        $language = new Language($this->configuration);
        $languageCode = $language->setLanguage(
            $this->configuration->get('main.languageDetection'),
            $this->configuration->get('main.language')
        );

        if (!$this->isAddingFaqsAllowed($user)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $questionText = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $questionText = trim(strip_tags((string) $questionText));
        if ($this->configuration->get('main.enableWysiwygEditorFrontend')) {
            $answer = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = trim(html_entity_decode((string) $answer));
        } else {
            $answer = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = strip_tags((string) $answer);
            $answer = trim(nl2br($answer));
        }

        $contentLink = Filter::filterVar($data->contentlink, FILTER_VALIDATE_URL);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        if (isset($data->{'rubrik[]'})) {
            if (is_string($data->{'rubrik[]'})) {
                $data->{'rubrik[]'} = [ $data->{'rubrik[]'} ];
            }

            $categories = Filter::filterArray(
                $data->{'rubrik[]'}
            );
        } else {
            $categories = [];
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }
        if (
            !empty($author) && !empty($email) && ($questionText !== '' && $questionText !== '0') &&
            $stopWords->checkBannedWord(strip_tags($questionText)) &&
            ($answer !== '' && $answer !== '0') && $stopWords->checkBannedWord(strip_tags($answer))
        ) {
            $session->userTracking('save_new_entry', 0);

            if (!empty($contentLink) && Strings::substr($contentLink, 7) !== '') {
                $answer = sprintf(
                    '%s<br><div id="newFAQContentLink">%s<a href="https://%s" target="_blank">%s</a></div>',
                    $answer,
                    Translation::get('msgInfo'),
                    Strings::substr($contentLink, 7),
                    $contentLink
                );
            }

            $autoActivate = $this->configuration->get('records.defaultActivation');

            $faqEntity = new FaqEntity();
            $faqEntity
                ->setLanguage($languageCode)
                ->setQuestion($questionText)
                ->setActive((bool)($autoActivate ? QueryHelper::FAQ_SQL_ACTIVE_YES : QueryHelper::FAQ_SQL_ACTIVE_NO))
                ->setSticky(false)
                ->setAnswer($answer)
                ->setKeywords($keywords)
                ->setAuthor($author)
                ->setEmail($email)
                ->setComment(true)
                ->setNotes('');

            $recordId = $faq->create($faqEntity);

            $openQuestionId = Filter::filterVar($data->openQuestionID, FILTER_VALIDATE_INT);
            if ($openQuestionId) {
                if ($this->configuration->get('records.enableDeleteQuestion')) {
                    $question->delete($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $question->updateQuestionAnswer($openQuestionId, $recordId, $categories[0]);
                }
            }

            $faqMetaData = new MetaData($this->configuration);
            $faqMetaData
                ->setFaqId($recordId)
                ->setFaqLanguage($faqEntity->getLanguage())
                ->setCategories($categories)
                ->save();

            // Let the admin and the category owners to be informed by email of this new entry
            $categoryHelper = new CategoryHelper();
            $categoryHelper
                ->setCategory($category)
                ->setConfiguration($this->configuration);

            $moderators = $categoryHelper->getModerators($categories);

            try {
                $notification = new Notification($this->configuration);
                $notification->sendNewFaqAdded($moderators, $recordId, $languageCode);
            } catch (Exception | TransportExceptionInterface $e) {
                $this->configuration->getLogger()->info('Notification could not be sent: ', [ $e->getMessage() ]);
            }

            if ($this->configuration->get('records.defaultActivation')) {
                $link = [
                    'link' => $faqHelper->createFaqUrl($faqEntity, $categories[0]),
                    'info' => Translation::get('msgRedirect')
                ];
            } else {
                $link = [];
            }

            return $this->json(
                [
                    'success' => Translation::get('msgNewContentThanks'),
                    ... $link
                ],
                Response::HTTP_OK
            );
        } else {
            return $this->json(['error' => Translation::get('errSaveEntries')], Response::HTTP_BAD_REQUEST);
        }
    }

    private function isAddingFaqsAllowed(CurrentUser $user): bool
    {
        if (
            !$this->configuration->get('records.allowNewFaqsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_ADD->value)
        ) {
            return false;
        }
        return true;
    }
}
