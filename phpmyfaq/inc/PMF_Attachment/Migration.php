<?php
/**
 * Attachment migration handler
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-13
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Attachment_Migration
 * 
 * @category  phpMyFAQ
 * @package   PMF_Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-13
 */
class PMF_Attachment_Migration
{
    /**
     * Migrate 2.0.x, 2.5.x to 2.6+ files without encryption
     */
    const MIGRATION_TYPE1 = 1;
    
    /**
     * Migrate 2.0.x, 2.5.x to 2.6+ files encrypting with default key
     */
    const MIGRATION_TYPE2 = 2;
        
    /**
     * Migrate encrypted to unencrypted.
     * NOTE this will migrate only files encrypted
     * with default key
     */
    const MIGRATION_TYPE3 = 3;

    /**
     * Migrate files encrypted with default key
     * to unencrypted files
     */
    const MIGRATION_TYPE4 = 4;
    
    /**
     * Errors
     * 
     * @var array
     */
    protected $error = array();
    
    /**
     * Warnings
     * 
     * @var array
     */
    protected $warning = array();
    
    /**
     * Returns the old files
     * 
     * @param string $dir Directory
     * 
     * @return array
     */
    protected function getOldFileList($dir)
    {
        $list = array();
        
        $faq  = new PMF_Faq;
        $faq->getAllRecords();
        $records = $faq->faqRecords;
        
        reset($records);
        while (list(,$record) = each($records)) {
            
            $recordDir = "$dir/$record[id]";
            if (file_exists($recordDir) && is_dir($recordDir)) {
                
                $list[$record['id']]['files'] = array();
                foreach (new DirectoryIterator($recordDir) as $entry) {
                    if (!$entry->isDot() && $entry->isFile()) {
                        $list[$record['id']]['files'][] = "$recordDir/{$entry->getFilename()}";
                    }
                }
                
                $list[$record['id']]['lang'] = $record['lang'];
            }
        }
        
        return $list;
    }
    
    /**
     * Quite simple migration from versions <2.6 
     * 
     * @return void
     */
    protected function migrateFromOldFormatToFs()
    {
        $list = $this->getOldFileList(PMF_ATTACHMENTS_DIR);
    
        foreach ($list as $recordId => $item) {
            $recordLang = $item['lang'];
            foreach ($item['files'] as $file) {
                $att = PMF_Attachment_Factory::create();
                $att->setRecordId($recordId);
                $att->setRecordLang($recordLang);
                
                if (!$att->save($file)) {
                    $this->error[] = "File $file couldn't be migrated";
                }
            }
            
            $recordDir = PMF_ATTACHMENTS_DIR . "/$recordId";
            if (!@rmdir(PMF_ATTACHMENTS_DIR . "/$file")) {
                $this->warning[] = "Couldn't remove dir $recordDir after migration";
            }
        }
    }
    
    /**
     * Migrate
     * 
     * @param integer $migrationType how to migrate
     * @param array   $options       migration options
     * 
     * @return boolean
     */
    public function doMigrate($migrationType, $options)
    {
        switch ($migrationType) {
            case PMF_Attachment_Migration::MIGRATION_TYPE1:
                
                PMF_Attachment_Factory::init(PMF_Attachment::STORAGE_TYPE_FILESYSTEM,
                                             '',
                                             false);
                $this->migrateFromOldFormatToFs();
                break;
              
            case PMF_Attachment_Migration::MIGRATION_TYPE2:
                /**
                 * Awaiting new default key here
                 */
                if(isset($options['defaultKey']) && !empty($options['defaultKey'])) {
                    PMF_Attachment_Factory::init(PMF_Attachment::STORAGE_TYPE_FILESYSTEM,
                                                 $options['defaultKey'],
                                                 true);
                    $this->migrateFromOldFormatToFs();
                } else {
                    $this->error[] = 'Default key required to be set for this option';
                }
                break;
                
            case PMF_Attachment_Migration::MIGRATION_TYPE3:
                $this->error[] = 'not implemented';
                break;
                
            case PMF_Attachment_Migration::MIGRATION_TYPE4:
                $this->error[] = 'not implemented';
                break;
                
            default:
                $this->error[] = 'Nothing to do';
                break;
                
        }
        
        return empty($this->error);
    }
    
    /**
     * Get migration errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->error;
    }
    
    /**
     * Get migration warnings
     * 
     * @return array
     */
    public function getWarnings()
    {
        return $this->warning;
    }
}