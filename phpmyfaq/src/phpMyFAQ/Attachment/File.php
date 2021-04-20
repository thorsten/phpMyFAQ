<?php

/**
 * Attachment handler class for files stored in filesystem.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */

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
class File extends AttachmentAbstract implements AttachmentInterface
{
    /**
     * Build file path under which the attachment, file is accessible in filesystem
     *
     * @return string
     * @throws AttachmentException
     */
    protected function buildFilePath(): string
    {
        $attachmentPath = PMF_ATTACHMENTS_DIR;
        $fsHash = $this->mkVirtualHash();
        $subDirCount = 3;
        $subDirNameLength = 5;

        for ($i = 0; $i < $subDirCount; ++$i) {
            $attachmentPath .= DIRECTORY_SEPARATOR . substr($fsHash, $i * $subDirNameLength, $subDirNameLength);
        }

        $attachmentPath .= DIRECTORY_SEPARATOR . substr($fsHash, $i * $subDirNameLength);

        return $attachmentPath;
    }

    /**
     * Create subdirectories to save file to.
     *
     * @param string $filepath filepath to create subdirectories for
     * @return bool success
     */
    public function createSubDirs(string $filepath): bool
    {
        clearstatcache();
        $attDir = dirname($filepath);

        return file_exists($attDir) && is_dir($attDir) || mkdir($attDir, 0777, true);
    }

    /**
     * Check whether the file storage is ok.
     *
     * @return bool
     * @throws AttachmentException
     */
    public function isStorageOk(): bool
    {
        clearstatcache();
        $attachmentDir = dirname($this->buildFilePath());

        return false !== PMF_ATTACHMENTS_DIR &&
               file_exists(PMF_ATTACHMENTS_DIR) &&
               is_dir(PMF_ATTACHMENTS_DIR) &&
               file_exists($attachmentDir) &&
               is_dir($attachmentDir);
    }

    /**
     * Save current attachment to the appropriate storage. The
     * filepath given will be processed and moved to appropriate
     * location.
     *
     * @param string $filePath full path to the attachment file
     * @param string $filename filename to force
     * @return bool
     * @throws FileException|AttachmentException
     * @todo rollback if something went wrong
     */
    public function save($filePath, $filename = null): bool
    {
        $success = false;

        if (file_exists($filePath)) {
            $this->realHash = md5_file($filePath);
            $this->filesize = filesize($filePath);
            $this->filename = null == $filename ? basename($filePath) : $filename;

            $this->saveMeta();

            $targetFile = $this->buildFilePath();

            if (null !== $this->id && $this->createSubDirs($targetFile)) {
                // Doing this check we're sure not to unnecessary
                // overwrite existing unencrypted file duplicates.
                if (!$this->linkedRecords()) {
                    $source = new VanillaFile($filePath);
                    $target = $this->getFile(FilesystemFile::MODE_WRITE);

                    $success = $source->moveTo($target);
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->postUpdateMeta();
                } else {
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
     * @return bool
     * @throws FileException
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
     *
     * @return string
     */
    public function get(): string
    {
    }

    /**
     * Output current file to stdout.
     *
     * @param bool   $headers if headers must be sent
     * @param string $disposition disposition type (ignored if $headers false)
     * @return void
     * @throws AttachmentException
     */
    public function rawOut($headers = true, $disposition = 'attachment'): void
    {
        $file = $this->getFile();

        if ($headers) {
            $disposition = 'attachment' == $disposition ? 'attachment' : 'inline';
            header('Content-Type: ' . $this->mimeType, true);
            header('Content-Length: ' . $this->filesize, true);
            header("Content-Disposition: $disposition; filename=\"" . rawurlencode($this->filename) . "\"", true);
            header("Content-MD5: {$this->realHash}", true);
        }

        while (!$file->eof()) {
            echo $file->getChunk();
        }
    }

    /**
     * Factory method to initialise the corresponding file object.
     *
     * @param string $mode File mode for file open
     * @return VanillaFile|EncryptedFile
     * @throws AttachmentException
     */
    private function getFile($mode = FilesystemFile::MODE_READ)
    {
        if ($this->encrypted) {
            $file = new EncryptedFile(
                $this->buildFilePath(),
                $mode,
                $this->key
            );
        } else {
            $file = new VanillaFile($this->buildFilePath(), $mode);
        }

        return $file;
    }
}
