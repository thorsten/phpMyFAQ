<?php
/**
 * Class for checking system requirements
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
 * @package   PMF_System
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-13
 */

/**
 * PMF_System
 *
 * @category  phpMyFAQ 
 * @package   PMF_System
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-13
 */
class PMF_System
{
    /**
     * Array of missing PHP extensions
     *
     * @var array
     */
    private $missingExtensions = array();
    
    /**
     * Lookup for installed database extensions
     * If the first supported extension is enabled, return true.
     *
     * @param  array $supportedDatabase Array of supported databases
     * 
     * @return boolean
     */
    public function checkDatabase($supportedDatabase)
    {
        foreach ($supportedDatabase as $extension => $database) {
            if (extension_loaded ($extension)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Lookup for installed PHP extensions
     *
     * @param  array $enabledExtensions enabled Extensions
     * 
     * @return boolean
     */
    public function checkExtension($enabledExtensions)
    {
        foreach ($enabledExtensions as $extension) {
            if (!extension_loaded ( $extension)) {
                $this->missingExtensions[] = $extension;
            }
        }
        
        if (count($this->missingExtensions) > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks for an installed phpMyFAQ version
     *
     * @return boolean
     */
    public function checkphpMyFAQInstallation()
    {
        if (is_file(PMF_ROOT_DIR . '/inc/data.php') || is_file(PMF_ROOT_DIR . '/config/database.php')) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Returns all missing extensions
     *
     * @return array
     */
    public function getMissingExtensions()
    {
        return $this->missingExtensions;
    }

    /**
     * Creates a JSON object with all .php files of phpMyFAQ with their sha1 hashes
     * @return string
     */
    public function createHashes()
    {
        $created = new DateTime();

        $path  = dirname(dirname(__FILE__));
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $hashes = array(
            'created' => $created->format('Y-m-d H:i:sP')
        );
        $blacklist = array(
            '/config/constants.php' => false,
            '/config/constants_ldap.php' => false,
            '/config/database.php' => false,
            '/config/ldap.php' => false
        );

        foreach ($files as $file) {
            if ('php' === $file->getExtension() && ! preg_match('#/tests/#', $file->getPath())) {
                $current = str_replace($path, '', $file->getPathname());

                if (isset($blacklist[$current])) {
                    continue;
                }
                $hashes[$current] = sha1(file_get_contents($file->getPathname()));
            }
        }

        return json_encode($hashes);
    }

}