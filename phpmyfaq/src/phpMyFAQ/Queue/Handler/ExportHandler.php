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

use Closure;
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
    /**
     * @param (Closure(): User)|null $userFactory
     * @param (Closure(): Faq)|null $faqFactory
     * @param (Closure(): Category)|null $categoryFactory
     * @param (Closure(): Mail)|null $mailFactory
     */
    public function __construct(
        private Configuration $configuration,
        private ?Closure $userFactory = null,
        private ?Closure $faqFactory = null,
        private ?Closure $categoryFactory = null,
        private ?Closure $exportFactory = null,
        private ?Closure $mailFactory = null,
    ) {
    }

    public static function getExportDirectory(): string
    {
        return (string) PMF_ROOT_DIR . '/content/core/exports';
    }

    public function __invoke(ExportMessage $message): void
    {
        $user = $this->userFactory instanceof Closure ? ($this->userFactory)() : new User($this->configuration);
        if (!$user->getUserById($message->userId)) {
            throw new RuntimeException(sprintf('Export requested by unknown user ID %d', $message->userId));
        }

        if (!$user->perm->hasPermission($message->userId, PermissionType::EXPORT->value)) {
            throw new RuntimeException(sprintf('User ID %d does not have export permission', $message->userId));
        }

        $faq = $this->faqFactory instanceof Closure ? ($this->faqFactory)() : new Faq($this->configuration);
        $category = $this->categoryFactory instanceof Closure
            ? ($this->categoryFactory)()
            : new Category($this->configuration);

        $exporter = null;
        if ($this->exportFactory instanceof Closure) {
            // The factory seam is duck-typed on purpose so tests can inject lightweight fakes
            $createdExporter = ($this->exportFactory)($faq, $category, $message->format);
            if (is_object($createdExporter) && method_exists($createdExporter, 'generate')) {
                $exporter = $createdExporter;
            }
        }

        $exporter ??= Export::create($faq, $category, $this->configuration, $message->format);
        $content = $exporter->generate(
            categoryId: (int) ($message->options['categoryId'] ?? 0),
            downwards: (bool) ($message->options['downwards'] ?? true),
            language: (string) ($message->options['language'] ?? ''),
        );

        if ($content === '') {
            throw new RuntimeException('Export generated empty content');
        }

        // Exports are only ever delivered by e-mail, so they are stored below
        // content/core/, which the web server never serves.
        $exportDir = self::getExportDirectory();
        if (
            !is_dir($exportDir)
            && !mkdir(directory: $exportDir, permissions: 0o775, recursive: true)
            && !is_dir($exportDir)
        ) {
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
            try {
                $mail = $this->mailFactory instanceof Closure ? ($this->mailFactory)() : new Mail($this->configuration);
                $mail->addTo($email);
                $mail->subject = 'Your phpMyFAQ export is ready';
                $mail->message = sprintf(
                    'Your %s export has been generated and is available for download: %s',
                    strtoupper($message->format),
                    $filename,
                );
                $mail->send();
            } catch (\Throwable $e) {
                error_log(sprintf(
                    'ExportHandler: failed to send notification email to %s for %s export file %s: %s',
                    $email,
                    $message->format,
                    $filename,
                    $e->getMessage(),
                ));
            }
        }
    }
}
