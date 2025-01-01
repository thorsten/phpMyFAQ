<?php

/**
 * The category image class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-09-08
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CategoryImage
 *
 * @package phpMyFAQ\Category
 */
class Image
{
    /** @var string */
    private const UPLOAD_DIR = PMF_CONTENT_DIR . '/user/images/';

    private bool $isUpload = false;

    private UploadedFile $uploadedFile;

    private string $fileName = '';

    /**
     * Constructor.
     *
     * @param Configuration $configuration Configuration object
     */
    public function __construct(private readonly Configuration $configuration)
    {
    }

    /**
     * Sets the uploaded file
     */
    public function setUploadedFile(UploadedFile $uploadedFile): Image
    {
        if ($uploadedFile->isValid()) {
            $this->isUpload = true;
        }

        $this->uploadedFile = $uploadedFile;

        return $this;
    }

    /**
     * Returns the filename for the given category ID and language.
     */
    public function getFileName(int $categoryId, string $categoryName): string
    {
        if ($this->isUpload) {
            $this->setFileName(
                sprintf(
                    'category-%d-%s.%s',
                    $categoryId,
                    $categoryName,
                    $this->getFileExtension($this->uploadedFile->getMimeType())
                )
            );
        }

        return $this->fileName;
    }

    /**
     * Returns the filename.
     */
    public function setFileName(string $fileName): Image
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Returns the image file extension from a given MIME type.
     */
    private function getFileExtension(string $mimeType): string
    {
        $mapping = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        return $mapping[$mimeType] ?? 'png';
    }

    /**
     * Checks for valid image MIME types, returns true if valid
     */
    private function isValidMimeType(string $contentType): bool
    {
        $types = ['image/jpeg','image/gif','image/png', 'image/webp'];
        return in_array($contentType, $types);
    }

    /**
     * Uploads the current file and moves it into the images/ folder.
     *
     * @throws Exception
     */
    public function upload(): bool
    {
        if (
            $this->isUpload && $this->uploadedFile->isValid()
            && $this->uploadedFile->getSize() < $this->configuration->get('records.maxAttachmentSize')
        ) {
            if (false === $this->uploadedFile->getSize()) {
                throw new Exception('Cannot detect image size');
            }

            if (!$this->isValidMimeType($this->uploadedFile->getClientMimeType())) {
                throw new Exception('Image MIME type validation failed.');
            }

            $this->uploadedFile->move(self::UPLOAD_DIR, $this->fileName);

            return true;
        }
        throw new Exception('Uploaded image is too big');
    }

    /**
     * Deletes the current file, returns true, if no file is available.
     */
    public function delete(): bool
    {
        if (is_file(self::UPLOAD_DIR . $this->fileName)) {
            return unlink(self::UPLOAD_DIR . $this->fileName);
        }

        return true;
    }
}
