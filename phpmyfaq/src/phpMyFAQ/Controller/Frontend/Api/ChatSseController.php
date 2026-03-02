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

use Closure;
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
     * @param ?Closure(): Chat $chatFactory
     * @param ?Closure(int): void $sleep
     * @param ?Closure(): int $timeProvider
     * @param ?Closure(): bool $connectionAborted
     * @param ?Closure(string): void $headerEmitter
     * @param ?Closure(): void $bufferCleaner
     * @param ?Closure(): void $flusher
     * @param ?Closure(string): void $outputEmitter
     */
    public function __construct(
        private readonly ?Closure $chatFactory = null,
        private readonly ?Closure $sleep = null,
        private readonly ?Closure $timeProvider = null,
        private readonly ?Closure $connectionAborted = null,
        private readonly ?Closure $headerEmitter = null,
        private readonly ?Closure $bufferCleaner = null,
        private readonly ?Closure $flusher = null,
        private readonly ?Closure $outputEmitter = null,
        private readonly int $heartbeatInterval = 15,
        private readonly int $maxRuntime = 30,
    ) {
        parent::__construct();
    }

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
        $lastIdValue = Filter::filterVar($request->query->get('lastId', 0), FILTER_VALIDATE_INT);
        $lastId = $lastIdValue === false ? 0 : (int) $lastIdValue;
        $chat = ($this->chatFactory ?? fn(): Chat => new Chat($this->configuration))();

        $this->session->save();

        return new StreamedResponse(
            function () use ($userId, $lastId, $chat) {
                if (ob_get_level() > 0) {
                    ($this->bufferCleaner ?? static function (): void {
                        ob_end_clean();
                    })();
                }

                ($this->headerEmitter ?? static function (string $header): void {
                    header($header);
                })('Content-Type: text/event-stream');
                ($this->headerEmitter ?? static function (string $header): void {
                    header($header);
                })('Cache-Control: no-cache');
                ($this->headerEmitter ?? static function (string $header): void {
                    header($header);
                })('Connection: keep-alive');
                ($this->headerEmitter ?? static function (string $header): void {
                    header($header);
                })('X-Accel-Buffering: no');

                $currentLastId = $lastId;
                $lastHeartbeat = ($this->timeProvider ?? static fn(): int => time())();
                $startTime = ($this->timeProvider ?? static fn(): int => time())();

                while (true) {
                    $messages = $chat->getNewMessages($userId, $currentLastId);

                    if ($messages !== []) {
                        $messageData = $chat->messagesToArray($messages);
                        ($this->outputEmitter ?? static function (string $chunk): void {
                            echo $chunk;
                        })('data: ' . json_encode($messageData) . "\n\n");

                        $lastMessage = end($messages);
                        $currentLastId = $lastMessage->getId();
                    }

                    if (
                        (($this->timeProvider ?? static fn(): int => time())() - $lastHeartbeat)
                        >= $this->heartbeatInterval
                    ) {
                        ($this->outputEmitter ?? static function (string $chunk): void {
                            echo $chunk;
                        })(": heartbeat\n\n");
                        $lastHeartbeat = ($this->timeProvider ?? static fn(): int => time())();
                    }

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    ($this->flusher ?? static function (): void {
                        flush();
                    })();

                    if (($this->connectionAborted ?? static fn(): bool => connection_aborted())()) {
                        break;
                    }

                    if ((($this->timeProvider ?? static fn(): int => time())() - $startTime) >= $this->maxRuntime) {
                        ($this->outputEmitter ?? static function (string $chunk): void {
                            echo $chunk;
                        })("event: reconnect\n");
                        ($this->outputEmitter ?? static function (string $chunk): void {
                            echo $chunk;
                        })("data: {\"lastId\": {$currentLastId}}\n\n");
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        ($this->flusher ?? static function (): void {
                            flush();
                        })();
                        break;
                    }

                    ($this->sleep ?? static function (int $seconds): void {
                        sleep($seconds);
                    })(2);
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
