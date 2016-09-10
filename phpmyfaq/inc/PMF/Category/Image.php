<?php

/**
 * The category image class.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2016-09-08
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Category images.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2016-09-08
 */
class PMF_Category_Image
{
    const UPLOAD_DIR = PMF_ROOT_DIR.'/images/';

    /** @var PMF_Configuration */
    private $config = null;

    /** @var bool */
    private $isUpload = false;

    /** @var array */
    private $uploadedFile = [];

    /** @var string */
    private $fileName = '';

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config   Configuration object
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Sets the uploaded file array from $_FILES.
     *
     * @param array $uploadedFile
     *
     * @return PMF_Category_Image
     */
    public function setUploadedFile(Array $uploadedFile)
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
     * @param integer $categoryId
     * @param string $categoryName
     *
     * @return string
     */
    public function getFileName($categoryId, $categoryName)
    {
        if ($this->isUpload) {
            $this->setFileName(
                sprintf(
                    'category-%d-%s.%s',
                    (int)$categoryId,
                    (string)$categoryName,
                    $this->getFileExtension($this->uploadedFile['type'])
                )
            );
        }

        return $this->fileName;
    }

    /**
     * Uploads the current file and moves it into the images/ folder.
     *
     * @return bool
     */
    public function upload()
    {
        if ($this->isUpload && is_uploaded_file($this->uploadedFile['tmp_name']) &&
            $this->uploadedFile['size'] < $this->config->get('records.maxAttachmentSize')) {

            if (false === getimagesize($this->uploadedFile['tmp_name'])) {
                return false;
            } else {
                if (move_uploaded_file($this->uploadedFile['tmp_name'], self::UPLOAD_DIR.$this->fileName)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Deletes the current file, returns true, if no file is available.
     *
     * @return bool
     */
    public function delete()
    {
        if (is_file(self::UPLOAD_DIR.$this->fileName)) {
            return unlink(self::UPLOAD_DIR.$this->fileName);
        }

        return true;
    }

    /**
     * Returns the image file extension from a given MIME type.
     *
     * @param string $mimeType
     *
     * @return string
     */
    private function getFileExtension($mimeType)
    {
        $mapping = [
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        return isset($mapping[$mimeType]) ? $mapping[$mimeType] : '';
    }

    /**
     * Returns the filename.
     *
     * @param string $fileName
     *
     * @return PMF_Category_Image
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }
}
