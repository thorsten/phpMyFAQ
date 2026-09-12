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
use phpseclib4\Exception\LengthException;
use Throwable;

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
        $attachmentPath = defined('PMF_ATTACHMENTS_DIR')
            ? (string) constant('PMF_ATTACHMENTS_DIR')
            : sys_get_temp_dir();

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

        return file_exists($attDir) && is_dir($attDir) || mkdir($attDir, permissions: 0o750, recursive: true);
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

        if (!defined('PMF_ATTACHMENTS_DIR')) {
            return false;
        }

        clearstatcache();
        $attachmentDir = dirname($this->buildFilePath());

        $attachmentsRoot = (string) PMF_ATTACHMENTS_DIR;

        return (
            file_exists($attachmentsRoot)
            && is_dir($attachmentsRoot)
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
        if ($this->encrypted && ($this->key === null || !AttachmentFactory::isSupportedKey($this->key))) {
            throw new AttachmentException(
                'Attachment encryption is enabled, but the configured encryption key is not 16, 24 or 32 bytes long',
            );
        }

        $this->realHash = (string) md5_file($filePath);
        $this->filesize = (int) filesize($filePath);
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
     * Stores the uploaded file in its final location and updates the metadata.
     *
     * @throws FileException|AttachmentException
     */
    protected function storeFile(string $filePath): bool
    {
        // Doing this check, we're sure not to unnecessarily
        // overwrite existing unencrypted file duplicates.
        if ($this->linkedRecords()) {
            $this->postUpdateMeta();

            return true;
        }

        try {
            $success = $this->encrypted ? $this->storeEncryptedFile($filePath) : $this->storePlainFile($filePath);
        } catch (StorageException $storageException) {
            throw new AttachmentException($storageException->getMessage(), 0, $storageException);
        }

        if (!$success) {
            return false;
        }

        $this->postUpdateMeta();

        return true;
    }

    /**
     * @throws FileException|AttachmentException
     */
    private function storeEncryptedFile(string $filePath): bool
    {
        if (!$this->createSubDirs($this->buildFilePath())) {
            return false;
        }

        $vanillaFile = new VanillaFile($filePath);
        $target = $this->getFile(FilesystemFile::MODE_WRITE);

        return $vanillaFile->moveTo($target);
    }

    /**
     * @throws AttachmentException|StorageException
     */
    private function storePlainFile(string $filePath): bool
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return false;
        }

        return $this->getStorage()->put($this->buildStoragePath(), $contents);
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
        $hasLinkedRecords = $this->linkedRecords();
        if (!$hasLinkedRecords && $this->encrypted) {
            $success = $success && $this->getFile()->delete();
        }

        if (!$hasLinkedRecords && !$this->encrypted) {
            try {
                $this->getStorage()->delete($this->buildStoragePath());
            } catch (StorageException $storageException) {
                throw new AttachmentException($storageException->getMessage(), 0, $storageException);
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
            $encryptionKey = $this->key;
            if ($encryptionKey === null) {
                throw new AttachmentException('Cannot open an encrypted attachment without a key.');
            }

            try {
                return new EncryptedFile($this->buildFilePath(), $mode, $encryptionKey);
            } catch (LengthException $lengthException) {
                throw new AttachmentException(
                    'The configured attachment encryption key is not 16, 24 or 32 bytes long',
                    previous: $lengthException,
                );
            }
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

        $this->storage = new StorageFactory($configuration)->create();

        return $this->storage;
    }

    private function usesCloudStorage(): bool
    {
        try {
            $configuration = Configuration::getConfigurationInstance();
        } catch (\LogicException) {
            // No configuration bootstrapped (CLI scripts, isolated tests): default to local storage.
            return false;
        }

        return strtolower((string) $configuration->get('storage.type')) === 's3';
    }
}
