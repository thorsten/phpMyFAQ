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
use phpMyFAQ\Configuration;
use phpMyFAQ\Storage\StorageException;
use phpMyFAQ\Storage\StorageFactory;
use phpMyFAQ\Storage\StorageInterface;

/**
 * Class File
 *
 * @package phpMyFAQ\Attachment
 */
class File extends AbstractAttachment implements AttachmentInterface
{
    private ?StorageInterface $storage = null;

    /**
     * Build a file path under which the attachment file is accessible in filesystem
     *
     * @throws AttachmentException
     */
    protected function buildFilePath(): string
    {
        $storagePath = $this->buildStoragePath();
        $attachmentPath = PMF_ATTACHMENTS_DIR;

        return $attachmentPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storagePath);
    }

    /**
     * Build the storage key/path for the current attachment.
     *
     * @throws AttachmentException
     */
    protected function buildStoragePath(): string
    {
        $fsHash = $this->mkVirtualHash();
        $subDirCount = 3;
        $subDirNameLength = 5;
        $segments = [];

        for ($i = 0; $i < $subDirCount; ++$i) {
            $segments[] = substr((string) $fsHash, $i * $subDirNameLength, $subDirNameLength);
        }

        $segments[] = substr((string) $fsHash, $i * $subDirNameLength);

        return implode('/', $segments);
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
        if ($this->usesCloudStorage()) {
            return true;
        }

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

            if ($this->linkedRecords()) {
                $success = true;
            } else {
                try {
                    if ($this->encrypted) {
                        $targetFile = $this->buildFilePath();
                        if ($this->createSubDirs($targetFile)) {
                            $vanillaFile = new VanillaFile($filePath);
                            $target = $this->getFile(FilesystemFile::MODE_WRITE);
                            $success = $vanillaFile->moveTo($target);
                        }
                    } else {
                        $contents = file_get_contents($filePath);
                        if ($contents !== false) {
                            $success = $this->getStorage()->put($this->buildStoragePath(), $contents);
                        }
                    }
                } catch (StorageException $storageException) {
                    throw new AttachmentException($storageException->getMessage(), 0, $storageException);
                }
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
            if ($this->encrypted) {
                $success &= $this->getFile()->delete();
            } else {
                try {
                    $this->getStorage()->delete($this->buildStoragePath());
                } catch (StorageException $storageException) {
                    throw new AttachmentException($storageException->getMessage(), 0, $storageException);
                }
            }
        }

        $this->deleteMeta();

        return $success;
    }

    /**
     * Retrieve file contents into a variable.
     *
     * @throws AttachmentException
     */
    public function get(): string
    {
        if (!$this->encrypted) {
            try {
                return $this->getStorage()->get($this->buildStoragePath());
            } catch (StorageException $storageException) {
                throw new AttachmentException($storageException->getMessage(), 0, $storageException);
            }
        }

        $file = $this->getFile();
        $contents = '';
        while (!$file->eof()) {
            $contents .= $file->getChunk();
        }

        return $contents;
    }

    /**
     * Output current file to stdout.
     *
     * @throws AttachmentException
     */
    public function rawOut(): void
    {
        if (!$this->encrypted) {
            echo $this->get();
            return;
        }

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

    /**
     * @throws AttachmentException
     */
    private function getStorage(): StorageInterface
    {
        if ($this->storage instanceof StorageInterface) {
            return $this->storage;
        }

        $configuration = Configuration::getConfigurationInstance();
        if ($configuration === null) {
            throw new AttachmentException('Storage cannot be initialized without configuration.');
        }

        $this->storage = new StorageFactory($configuration)->create();

        return $this->storage;
    }

    private function usesCloudStorage(): bool
    {
        $configuration = Configuration::getConfigurationInstance();
        if ($configuration === null) {
            return false;
        }

        return strtolower((string) $configuration->get('storage.type')) === 's3';
    }
}
