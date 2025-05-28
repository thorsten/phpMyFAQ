<?php

/**
 * The FAQ Controller
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
 * @since     2024-03-03
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq\MetaData;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class FaqController extends AbstractController
{
    /**
     * @throws Exception|\JsonException|\Exception
     */
    public function create(Request $request): JsonResponse
    {
        $faq = $this->container->get('phpmyfaq.faq');
        $faqHelper = $this->container->get('phpmyfaq.helper.faq');
        $question = $this->container->get('phpmyfaq.question');
        $stopWords = $this->container->get('phpmyfaq.stop-words');
        $session = $this->container->get('phpmyfaq.user.session');
        $session->setCurrentUser($this->currentUser);

        $language = $this->container->get('phpmyfaq.language');
        $languageCode = $language->setLanguage(
            $this->configuration->get('main.languageDetection'),
            $this->configuration->get('main.language')
        );

        if (!$this->isAddingFaqsAllowed($this->currentUser)) {
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

        $category = new Category($this->configuration);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        if (isset($data->rubrik)) {
            if (is_string($data->rubrik)) {
                $data->rubrik = [ $data->rubrik ];
            }

            $categories = Filter::filterArray(
                $data->rubrik
            );
        } else {
            $categories = [$category->getAllCategoryIds()[0]];
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        if (
            $author !== '' &&
            $author !== '0' &&
            ($email !== '' && $email !== '0') &&
            ($questionText !== '' && $questionText !== '0') &&
            $stopWords->checkBannedWord(strip_tags($questionText))
        ) {
            if ($answer !== '' && $answer !== '0') {
                $stopWords->checkBannedWord(strip_tags($answer));
            } else {
                $answer = '';
            }

            $session->userTracking('save_new_entry', 0);

            $autoActivate = $this->configuration->get('records.defaultActivation');

            $faqEntity = new FaqEntity();
            $faqEntity
                ->setLanguage($languageCode)
                ->setQuestion($questionText)
                ->setActive($autoActivate)
                ->setSticky(false)
                ->setAnswer($answer)
                ->setKeywords($keywords)
                ->setAuthor($author)
                ->setEmail($email)
                ->setComment(true)
                ->setNotes('');

            $faq->create($faqEntity);
            $recordId = $faqEntity->getId();

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
            $categoryHelper = $this->container->get('phpmyfaq.helper.category');
            $categoryHelper
                ->setCategory($category)
                ->setConfiguration($this->configuration);

            $moderators = $categoryHelper->getModerators($categories);

            try {
                $notification = $this->container->get('phpmyfaq.notification');
                $notification->sendNewFaqAdded($moderators, $faqEntity);
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
        }

        return $this->json(['error' => Translation::get('errSaveEntries')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \Exception
     */
    private function isAddingFaqsAllowed(CurrentUser $currentUser): bool
    {
        return !(!$this->configuration->get('records.allowNewFaqsForGuests') && !$currentUser->perm->hasPermission(
            $currentUser->getUserId(),
            PermissionType::FAQ_ADD->value,
        ));
    }
}
