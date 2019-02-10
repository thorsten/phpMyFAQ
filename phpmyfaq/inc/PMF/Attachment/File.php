<?php

/**
 * Attachment handler class for files stored in filesystem.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Abstract.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-08-21
 */
class PMF_Attachment_File extends PMF_Attachment_Abstract implements PMF_Attachment_Interface
{
    /**
     * Build file path under which the attachment.
     *
     * file is accessible in filesystem
     *
     * @return string
     */
    protected function buildFilePath()
    {
        $retval = PMF_ATTACHMENTS_DIR;
        $fsHash = $this->mkVirtualHash();
        $subDirCount = 3;
        $subDirNameLength = 5;

        for ($i = 0; $i < $subDirCount; ++$i) {
            $retval .= DIRECTORY_SEPARATOR.substr($fsHash, $i * $subDirNameLength, $subDirNameLength);
        }

        $retval .= DIRECTORY_SEPARATOR.substr($fsHash, $i * $subDirNameLength);

        return $retval;
    }

    /**
     * Create subdirs to save file to.
     *
     * @param string $filepath filpath to create subdirs for
     *
     * @return bool success
     */
    public function createSubDirs($filepath)
    {
        clearstatcache();

        $attDir = dirname($filepath);

        return file_exists($attDir) && is_dir($attDir) ||
               mkdir($attDir, 0777, true);
    }

    /**
     * Check wether the file storage is ok.
     *
     * @return bool
     */
    public function isStorageOk()
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
     * @param string $filepath full path to the attachment file
     * @param string $filename filename to force
     *
     * @return bool
     *
     * TODO rollback if something went wrong
     */
    public function save($filepath, $filename = null)
    {
        $retval = false;

        if (file_exists($filepath)) {
            $this->realHash = md5_file($filepath);
            $this->filesize = filesize($filepath);
            $this->filename = null == $filename ? basename($filepath) : $filename;

            $this->saveMeta();

            $targetFile = $this->buildFilePath();

            if (null !== $this->id && $this->createSubDirs($targetFile)) {
                /*
                 * Doing this check we're sure not to unnecessary 
                 * overwrite existing unencrypted file duplicates.
                 */
                if (!$this->linkedRecords()) {
                    $source = new PMF_Attachment_Filesystem_File_Vanilla($filepath);
                    $target = $this->getFile(PMF_Attachment_Filesystem_File::MODE_WRITE);

                    $retval = $source->moveTo($target);
                } else {
                    $retval = true;
                }

                if ($retval) {
                    $this->postUpdateMeta();
                } else {
                    /*
                     * File wasn't saved
                     */
                    $this->delete();
                    $retval = false;
                }
            }
        }

        return $retval;
    }

    /**
     * Delete attachment.
     * 
     * @return bool
     */
    public function delete()
    {
        $retval = true;

        // Won't delete the file if there are still some records hanging on it
        if (!$this->linkedRecords()) {
            try {
                $retval &= $this->getFile()->delete();
            } catch (Exception $e) {
                $retval &= !file_exists($this->buildFilePath());
            }
        }

        $this->deleteMeta();

        return $retval;
    }

    /**
     * Retrieve file contents into a variable.
     *
     * @return string
     */
    public function get()
    {
    }

    /**
     * Output current file to stdout.
     *
     * @param bool   $headers     if headers must be sent
     * @param string $disposition diposition type (ignored if $headers false)
     * 
     * @return string
     */
    public function rawOut($headers = true, $disposition = 'attachment')
    {
        $file = $this->getFile();

        if ($headers) {
            $disposition = 'attachment' == $disposition ? 'attachment' : 'inline';
            header('Content-Type: '.$this->mimeType, true);
            header('Content-Length: '.$this->filesize, true);
            header("Content-Disposition: $disposition; filename=\"".rawurlencode($this->filename)."\"", true);
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
     * 
     * @return PMF_Attachment_Filesystem_File_Vanilla|PMF_Attachment_Filesystem_File_Encrypted
     */
    private function getFile($mode = PMF_Attachment_Filesystem_File::MODE_READ)
    {
        if ($this->encrypted) {
            $file = new PMF_Attachment_Filesystem_File_Encrypted(
                $this->buildFilePath(),
                $mode,
                $this->key
            );
        } else {
            $file = new PMF_Attachment_Filesystem_File_Vanilla($this->buildFilePath(), $mode);
        }

        return $file;
    }
}
