<?php

/**
 * The Chat SSE Controller for Server-Sent Events real-time messaging.
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

namespace phpMyFAQ\Controller\Frontend\Api;

use Exception;
use phpMyFAQ\Chat;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ChatSseController extends AbstractController
{
    /**
     * SSE endpoint for real-time message delivery.
     *
     * @throws Exception
     */
    #[Route(path: 'chat/stream', name: 'api.private.chat.stream', methods: ['GET'])]
    public function stream(Request $request): StreamedResponse
    {
        $this->userIsAuthenticated();

        $userId = $this->currentUser->getUserId();
        $lastId = Filter::filterVar($request->query->get('lastId', 0), FILTER_VALIDATE_INT) ?: 0;
        $chat = new Chat($this->configuration);

        $this->session->save();

        return new StreamedResponse(
            static function () use ($userId, $lastId, $chat) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');

                $currentLastId = $lastId;
                $heartbeatInterval = 15;
                $lastHeartbeat = time();
                $maxRuntime = 30; // Maximum runtime in seconds (for PHP timeout)
                $startTime = time();

                while (true) {
                    $messages = $chat->getNewMessages($userId, $currentLastId);

                    if (!empty($messages)) {
                        $messageData = $chat->messagesToArray($messages);
                        echo 'data: ' . json_encode($messageData) . "\n\n";

                        $lastMessage = end($messages);
                        $currentLastId = $lastMessage->getId();
                    }

                    if ((time() - $lastHeartbeat) >= $heartbeatInterval) {
                        echo ": heartbeat\n\n";
                        $lastHeartbeat = time();
                    }

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    if (connection_aborted()) {
                        break;
                    }

                    if ((time() - $startTime) >= $maxRuntime) {
                        echo "event: reconnect\n";
                        echo "data: {\"lastId\": {$currentLastId}}\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    sleep(2);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ],
        );
    }
}
