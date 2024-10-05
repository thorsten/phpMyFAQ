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
 * @copyright 2016-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-09-08
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;

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

    private array $uploadedFile = [];

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
     * Sets the uploaded file array from $_FILES.
     */
    public function setUploadedFile(array $uploadedFile): Image
    {
        if (isset($uploadedFile['error']) && UPLOAD_ERR_OK === $uploadedFile['error']) {
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
                    $this->getFileExtension($this->uploadedFile['type'])
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
    private function isValidMimeType(string $file): bool
    {
        $types = ['image/jpeg','image/gif','image/png', 'image/webp'];
        $type = mime_content_type($file);

        return in_array($type, $types);
    }

    /**
     * Uploads the current file and moves it into the images/ folder.
     *
     * @throws Exception
     */
    public function upload(): bool
    {
        if (
            $this->isUpload && is_uploaded_file($this->uploadedFile['tmp_name'])
            && $this->uploadedFile['size'] < $this->configuration->get('records.maxAttachmentSize')
        ) {
            if (false === getimagesize($this->uploadedFile['tmp_name'])) {
                throw new Exception('Cannot detect image size');
            }

            if (!$this->isValidMimeType($this->uploadedFile['tmp_name'])) {
                throw new Exception('Image MIME type validation failed.');
            }

            if (!move_uploaded_file($this->uploadedFile['tmp_name'], self::UPLOAD_DIR . $this->fileName)) {
                throw new Exception('Cannot move uploaded image');
            }

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
