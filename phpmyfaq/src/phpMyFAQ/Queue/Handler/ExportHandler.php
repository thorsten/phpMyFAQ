<?php

/**
 * Handler for queued exports.
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

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Export;
use phpMyFAQ\Faq;
use phpMyFAQ\Mail;
use phpMyFAQ\Queue\Message\ExportMessage;
use phpMyFAQ\User;
use RuntimeException;

final readonly class ExportHandler
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function __invoke(ExportMessage $message): void
    {
        $user = new User($this->configuration);
        if (!$user->getUserById($message->userId)) {
            throw new RuntimeException(sprintf('Export requested by unknown user ID %d', $message->userId));
        }

        if (!$user->perm->hasPermission($message->userId, PermissionType::EXPORT->value)) {
            throw new RuntimeException(sprintf('User ID %d does not have export permission', $message->userId));
        }

        $faq = new Faq($this->configuration);
        $category = new Category($this->configuration);

        $exporter = Export::create($faq, $category, $this->configuration, $message->format);
        $content = $exporter->generate(
            categoryId: (int) ($message->options['categoryId'] ?? 0),
            downwards: (bool) ($message->options['downwards'] ?? true),
            language: (string) ($message->options['language'] ?? ''),
        );

        if ($content === '') {
            throw new RuntimeException('Export generated empty content');
        }

        $exportDir = PMF_ROOT_DIR . '/content/user/exports';
        if (!is_dir($exportDir) && !mkdir($exportDir, 0o775, true)) {
            throw new RuntimeException('Unable to create export directory: ' . $exportDir);
        }

        $extension = $message->format === 'json' ? 'json' : 'pdf';
        $filename = sprintf('export-%d-%s.%s', $message->userId, date('Y-m-d-H-i-s'), $extension);
        $filePath = $exportDir . '/' . $filename;

        if (file_put_contents($filePath, $content) === false) {
            throw new RuntimeException('Unable to write export file: ' . $filePath);
        }

        $email = $user->getUserData('email');
        if (is_string($email) && $email !== '') {
            $mail = new Mail($this->configuration);
            $mail->addTo($email);
            $mail->subject = 'Your phpMyFAQ export is ready';
            $mail->message = sprintf(
                'Your %s export has been generated and is available for download: %s',
                strtoupper($message->format),
                $filename,
            );
            $mail->send();
        }
    }
}
