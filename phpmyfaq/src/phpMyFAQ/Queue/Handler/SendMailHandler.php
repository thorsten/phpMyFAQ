<?php

/**
 * Handler for queued mail messages.
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
 * @since     2026-02-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Queue\Handler;

use Closure;
use phpMyFAQ\Configuration;
use phpMyFAQ\Mail;
use phpMyFAQ\Mail\MailContent;
use phpMyFAQ\Queue\Message\SendMailMessage;
use RuntimeException;

final readonly class SendMailHandler
{
    /**
     * @param (Closure(): Mail)|null $mailFactory
     */
    public function __construct(
        private Configuration $configuration,
        private ?Closure $mailFactory = null,
    ) {
    }

    public function __invoke(SendMailMessage $message): void
    {
        $mail = $this->mailFactory instanceof Closure ? ($this->mailFactory)() : new Mail($this->configuration);

        $envelope = $message->metadata['envelope'] ?? null;
        if (
            is_array($envelope)
            && array_key_exists('recipients', $envelope)
            && array_key_exists('headers', $envelope)
            && array_key_exists('body', $envelope)
            && is_string($envelope['recipients'])
            && is_array($envelope['headers'])
            && is_string($envelope['body'])
        ) {
            $headers = [];
            foreach ($envelope['headers'] as $headerName => $headerValue) {
                $headers[(string) $headerName] = is_int($headerValue) || is_string($headerValue) ? $headerValue : null;
            }

            $this->failUnlessSent(
                $mail->sendPreparedEnvelope(
                    $envelope['recipients'],
                    $headers,
                    $envelope['body'],
                    $this->contentOf($envelope),
                ),
                $message,
            );

            return;
        }

        $mail->addTo($message->recipient);
        $mail->subject = $message->subject;
        $mail->message = $message->body;
        $this->failUnlessSent($mail->send(forceSynchronousDelivery: true), $message);
    }

    /**
     * The parts of the body. Without them (a job queued before the parts were stored) the mail
     * layer falls back to the prepared body.
     *
     * @param array<array-key, mixed> $envelope
     */
    private function contentOf(array $envelope): ?MailContent
    {
        if (
            !array_key_exists('text', $envelope)
            || !array_key_exists('html', $envelope)
            || !is_string($envelope['text'])
            || !is_string($envelope['html'])
        ) {
            return null;
        }

        return MailContent::html($envelope['html'], $envelope['text']);
    }

    /**
     * A mail agent reports a failed delivery by returning 0 (e.g. PHP's mail() returned false).
     * Throwing hands the job to the retry logic of the worker instead of acknowledging it.
     */
    private function failUnlessSent(int $sentMails, SendMailMessage $message): void
    {
        if ($sentMails > 0) {
            return;
        }

        throw new RuntimeException(sprintf(
            'The mail agent did not accept the mail "%s" for %s.',
            $message->subject,
            $message->recipient,
        ));
    }
}
