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
 * @copyright 2016-2023 phpMyFAQ Team
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
class CategoryImage
{
    /** @var string */
    private const UPLOAD_DIR = PMF_ROOT_DIR . '/images/';

    private bool $isUpload = false;

    private array $uploadedFile = [];

    private string $fileName = '';

    /**
     * Constructor.
     *
     * @param Configuration $config Configuration object
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Sets the uploaded file array from $_FILES.
     *
     *
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
    public function setFileName(string $fileName): CategoryImage
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
            'image/png' => 'png'
        ];

        return $mapping[$mimeType] ?? '';
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
            && $this->uploadedFile['size'] < $this->config->get('records.maxAttachmentSize')
        ) {
            if (false === getimagesize($this->uploadedFile['tmp_name'])) {
                throw new Exception('Cannot detect image size');
            }
            if (move_uploaded_file($this->uploadedFile['tmp_name'], self::UPLOAD_DIR . $this->fileName)) {
                return true;
            } else {
                throw new Exception('Cannot move uploaded image');
            }
        } else {
            throw new Exception('Uploaded image is too big');
        }
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
