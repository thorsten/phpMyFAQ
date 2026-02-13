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

use phpMyFAQ\Configuration;
use phpMyFAQ\Mail;
use phpMyFAQ\Queue\Message\SendMailMessage;

final readonly class SendMailHandler
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function __invoke(SendMailMessage $message): void
    {
        $mail = new Mail($this->configuration);

        $envelope = $message->metadata['envelope'] ?? null;
        if (
            is_array($envelope)
            && isset($envelope['recipients'], $envelope['headers'], $envelope['body'])
            && is_string($envelope['recipients'])
            && is_array($envelope['headers'])
            && is_string($envelope['body'])
        ) {
            $mail->sendPreparedEnvelope($envelope['recipients'], $envelope['headers'], $envelope['body']);

            return;
        }

        $mail->addTo($message->recipient);
        $mail->subject = $message->subject;
        $mail->message = $message->body;
        $mail->send(forceSynchronousDelivery: true);
    }
}
