<?php

/**
 * Attachment handler class for files stored in filesystem.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use LengthException;
use phpMyFAQ\Attachment\Filesystem\AbstractFile as FilesystemFile;
use phpMyFAQ\Attachment\Filesystem\File\EncryptedFile;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;
use Throwable;

/**
 * Class File
 *
 * @package phpMyFAQ\Attachment
 */
class File extends AbstractAttachment implements AttachmentInterface
{
    /**
     * Build a file path under which the attachment file is accessible in filesystem
     *
     * @throws AttachmentException
     */
    protected function buildFilePath(): string
    {
        $attachmentPath = PMF_ATTACHMENTS_DIR;
        $fsHash = $this->mkVirtualHash();
        $subDirCount = 3;
        $subDirNameLength = 5;

        for ($i = 0; $i < $subDirCount; ++$i) {
            $attachmentPath .=
                DIRECTORY_SEPARATOR . substr((string) $fsHash, $i * $subDirNameLength, $subDirNameLength);
        }

        return $attachmentPath . (DIRECTORY_SEPARATOR . substr((string) $fsHash, $i * $subDirNameLength));
    }

    /**
     * Create subdirectories to save a file to.
     *
     * @param string $filepath filepath to create subdirectories for
     * @return bool success
     */
    public function createSubDirs(string $filepath): bool
    {
        clearstatcache();
        $attDir = dirname($filepath);

        return file_exists($attDir) && is_dir($attDir) || mkdir($attDir, 0o777, true);
    }

    /**
     * Check whether the file storage is ok.
     *
     * @throws AttachmentException
     */
    public function isStorageOk(): bool
    {
        clearstatcache();
        $attachmentDir = dirname($this->buildFilePath());

        return (
            false !== PMF_ATTACHMENTS_DIR
            && file_exists(PMF_ATTACHMENTS_DIR)
            && is_dir(PMF_ATTACHMENTS_DIR)
            && file_exists($attachmentDir)
            && is_dir($attachmentDir)
        );
    }

    /**
     * Save the current attachment to the appropriate storage.
     * The filepath given will be processed and moved to the appropriate location.
     *
     * @param string $filePath full path to the attachment file
     * @throws FileException|AttachmentException
     */
    public function save(string $filePath, ?string $filename = null): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // Validate the key before the metadata row exists, so a misconfigured
        // key cannot leave an orphaned encrypted=1 record behind.
        if ($this->encrypted && (null === $this->key || !AttachmentFactory::isSupportedKey($this->key))) {
            throw new AttachmentException(
                'Attachment encryption is enabled, but the configured encryption key is not 16, 24 or 32 bytes long',
            );
        }

        $this->realHash = md5_file($filePath);
        $this->filesize = filesize($filePath);
        $this->filename = $filename ?? basename($filePath);

        $this->saveMeta();

        try {
            $success = $this->storeFile($filePath);
        } catch (Throwable $throwable) {
            // Roll back the metadata written above; the file was not stored.
            $this->deleteMeta();

            throw $throwable;
        }

        if (!$success) {
            // File wasn't saved
            $this->delete();
        }

        return $success;
    }

    /**
     * Moves the uploaded file to its final location and updates the metadata.
     *
     * @throws FileException|AttachmentException
     */
    protected function storeFile(string $filePath): bool
    {
        $targetFile = $this->buildFilePath();

        if (!$this->createSubDirs($targetFile)) {
            return false;
        }

        // Doing this check, we're sure not to unnecessarily
        // overwrite existing unencrypted file duplicates.
        if ($this->linkedRecords()) {
            $this->postUpdateMeta();

            return true;
        }

        $vanillaFile = new VanillaFile($filePath);
        $target = $this->getFile(FilesystemFile::MODE_WRITE);

        if (!$vanillaFile->moveTo($target)) {
            return false;
        }

        $this->postUpdateMeta();

        return true;
    }

    /**
     * Delete attachment.
     *
     * @throws FileException|AttachmentException
     */
    public function delete(): bool
    {
        $success = true;

        // Won't delete the file if there are still some records hanging on it
        if (!$this->linkedRecords()) {
            $success &= $this->getFile()->delete();
        }

        $this->deleteMeta();

        return $success;
    }

    /**
     * Retrieve file contents into a variable.
     */
    public function get(): string {}

    /**
     * Output current file to stdout.
     *
     * @throws AttachmentException
     */
    public function rawOut(): void
    {
        $file = $this->getFile();
        while (!$file->eof()) {
            echo $file->getChunk();
        }
    }

    /**
     * Factory method to initialize the corresponding file object.
     *
     * @param string $mode File mode for file open
     * @throws AttachmentException
     */
    private function getFile(string $mode = FilesystemFile::MODE_READ): EncryptedFile|VanillaFile
    {
        if ($this->encrypted) {
            // buildFilePath() already guarantees a non-null key for encrypted files.
            try {
                return new EncryptedFile($this->buildFilePath(), $mode, (string) $this->key);
            } catch (LengthException $lengthException) {
                throw new AttachmentException(
                    'The configured attachment encryption key is not 16, 24 or 32 bytes long',
                    previous: $lengthException,
                );
            }
        }

        return new VanillaFile($this->buildFilePath(), $mode);
    }
}
