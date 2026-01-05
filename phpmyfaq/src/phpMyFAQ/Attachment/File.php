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

use phpMyFAQ\Attachment\Filesystem\AbstractFile as FilesystemFile;
use phpMyFAQ\Attachment\Filesystem\File\EncryptedFile;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;

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

        return file_exists($attDir) && is_dir($attDir) || mkdir($attDir, permissions: 0o777, recursive: true);
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
     * @todo rollback if something went wrong
     */
    public function save(string $filePath, ?string $filename = null): bool
    {
        $success = false;

        if (file_exists($filePath)) {
            $this->realHash = md5_file($filePath);
            $this->filesize = filesize($filePath);
            $this->filename = $filename ?? basename($filePath);

            $this->saveMeta();

            $targetFile = $this->buildFilePath();

            if ($this->createSubDirs($targetFile)) {
                // Doing this check, we're sure not to unnecessarily
                // overwrite existing unencrypted file duplicates.
                if (!$this->linkedRecords()) {
                    $vanillaFile = new VanillaFile($filePath);
                    $target = $this->getFile(FilesystemFile::MODE_WRITE);

                    $success = $vanillaFile->moveTo($target);
                }

                if ($this->linkedRecords()) {
                    $success = true;
                }

                if ($success) {
                    $this->postUpdateMeta();
                }

                if (!$success) {
                    // File wasn't saved
                    $this->delete();
                    $success = false;
                }
            }
        }

        return $success;
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
    public function get(): string
    {
    }

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
            return new EncryptedFile($this->buildFilePath(), $mode, $this->key);
        }

        return new VanillaFile($this->buildFilePath(), $mode);
    }
}
