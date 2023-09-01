<?php

/**
 * The category image class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2016-09-08
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;

/**
 * Class CategoryImage
 *
 * @package phpMyFAQ\Category
 */
class CategoryImage
{
    /** @var string */
    private const UPLOAD_DIR = PMF_ROOT_DIR . '/images/';

    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * @var bool
     */
    private bool $isUpload = false;

    /**
     * @var array
     */
    private array $uploadedFile = [];

    /**
     * @var string
     */
    private string $fileName = '';

    /**
     * Constructor.
     *
     * @param Configuration $config Configuration object
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Sets the uploaded file array from $_FILES.
     *
     * @param array $uploadedFile
     *
     * @return CategoryImage
     */
    public function setUploadedFile(array $uploadedFile): CategoryImage
    {
        if (isset($uploadedFile['error']) && UPLOAD_ERR_OK === $uploadedFile['error']) {
            $this->isUpload = true;
        }
        $this->uploadedFile = $uploadedFile;

        return $this;
    }

    /**
     * Returns the filename for the given category ID and language.
     *
     * @param int    $categoryId
     * @param string $categoryName
     * @return string
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
     *
     * @param string $fileName
     * @return CategoryImage
     */
    public function setFileName(string $fileName): CategoryImage
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Returns the image file extension from a given MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    private function getFileExtension(string $mimeType): string
    {
        $mapping = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        return $mapping[$mimeType] ?? '';
    }

    /**
     * Checks for valid image MIME types, returns true if valid
     * @param string $file
     * @return bool
     */
    private function isValidMimeType(string $file): bool
    {
        $types = ['image/jpeg','image/gif','image/png'];
        $type = mime_content_type($file);

        return in_array($type, $types);
    }

    /**
     * Uploads the current file and moves it into the images/ folder.
     *
     * @return bool
     */
    public function upload(): bool
    {
        if (
            $this->isUpload && is_uploaded_file($this->uploadedFile['tmp_name'])
            && $this->uploadedFile['size'] < $this->config->get('records.maxAttachmentSize')
        ) {
            if (!getimagesize($this->uploadedFile['tmp_name'])) {
                return false;
            }

            if (!$this->isValidMimeType($this->uploadedFile['tmp_name'])) {
                return false;
            }

            if (!move_uploaded_file($this->uploadedFile['tmp_name'], self::UPLOAD_DIR . $this->fileName)) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes the current file, returns true, if no file is available.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (is_file(self::UPLOAD_DIR . $this->fileName)) {
            return unlink(self::UPLOAD_DIR . $this->fileName);
        }

        return true;
    }
}
