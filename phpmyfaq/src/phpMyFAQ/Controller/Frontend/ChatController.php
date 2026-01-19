<?php

/**
 * The Chat Controller displays the chat page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Chat;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractFrontController
{
    /**
     * Displays the user's chat/messages page.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/user/chat', name: 'public.user.chat', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if (!$this->currentUser->isLoggedIn()) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('chat', 0);

        $chat = new Chat($this->configuration);
        $session = $this->container->get('session');

        $conversations = $chat->getConversationList($this->currentUser->getUserId());
        $unreadCount = $chat->getUnreadCount($this->currentUser->getUserId());

        return $this->render('chat.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgChat'), $this->configuration->getTitle()),
            'conversations' => $conversations,
            'unreadCount' => $unreadCount,
            'currentUserId' => $this->currentUser->getUserId(),
            'csrfTokenSendMessage' => Token::getInstance($session)->getTokenString('send-chat-message'),
            'csrfTokenMarkRead' => Token::getInstance($session)->getTokenString('mark-chat-read'),
        ]);
    }
}
