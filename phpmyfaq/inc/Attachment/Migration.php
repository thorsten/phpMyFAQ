<?php
/**
 * Attachment migration handler
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-13
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Atachment_Abstract
 *
 * @category  phpMyFAQ
 * @package   Attachment
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Attachment_Migration
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * @param $dir
     * @return array
     */
    protected function getOldFileList($dir)
    {
        $list = array();
        
        $faq  = new PMF_Faq($this->_config);
        $faq->getAllRecords();
        $records = $faq->faqRecords;
        
        reset($records);
        while(list(,$record) = each($records)) {
            
            $recordDir = "$dir/$record[id]";
            if(file_exists($recordDir) && is_dir($recordDir)) {
                
                $list[$record['id']]['files'] = array();
                foreach(new DirectoryIterator($recordDir) as $entry) {
                    if(!$entry->isDot() && $entry->isFile()) {
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
     * @return null
     */
    protected function migrateFromOldFormatToFs()
    {
        $list = $this->getOldFileList(PMF_ATTACHMENTS_DIR);
    
        foreach($list as $recordId => $item) {
            $recordLang = $item['lang'];
            foreach($item['files'] as $file) {
                $att = PMF_Attachment_Factory::create();
                $att->setRecordId($recordId);
                $att->setRecordLang($recordLang);
                
                if(!$att->save($file)) {
                    $this->error[] = "File $file couldn't be migrated";
                }
            }
            
            $recordDir = PMF_ATTACHMENTS_DIR . "/$recordId";
            if(!@rmdir(PMF_ATTACHMENTS_DIR . "/$file")) {
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
        switch($migrationType) {
            case PMF_Attachment_Migration::MIGRATION_TYPE1:
                
                PMF_Attachment_Factory::init(PMF_Attachment::STORAGE_TYPE_FILESYSTEM,
                                             '',
                                             false);
                $this->migrateFromOldFormatToFs();
                // FIXME should attachment settings update be triggered here?
                
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
                // TODO implement this
                $this->error[] = 'not implemented';
                break;
                
            case PMF_Attachment_Migration::MIGRATION_TYPE4:
                //TODO implenemt this
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