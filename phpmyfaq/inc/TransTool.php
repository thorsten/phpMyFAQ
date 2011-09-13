<?php
/**
 * Interface translation tool functionality
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
 * @package   PMF_TransTool
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_TransTool
 *
 * @category  phpMyFAQ
 * @package   PMF_TransTool
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-12
 */
class PMF_TransTool
{
    /**
     * Parse language file and get vars it does contain
     *
     * @param string $filepath Filepath
     * 
     * @return array
     */
    public function getVars($filepath)
    {
        $retval = array();
        
        if (file_exists($filepath) && is_readable($filepath)) {
        
            $orig = file($filepath);
            
            while (list(,$line) = each($orig)) {
                $line = rtrim($line);
                /**
                 * Bypass all but variable definitions
                 */
                if (strlen($line) && '$' == $line[0]) {
                    /**
                     * $PMF_LANG["key"] = "val";
                     * or
                     * $PMF_LANG["key"] = array(0 => "something", 1 => ...);
                     * turns to something like  array('$PMF_LANG["key"]', '"val";')
                     */
                    $m   = explode("=", $line, 2);
                    $key = str_replace(array('["', '"]', '[\'', '\']'), array('[', ']', '[', ']'), PMF_String::substr(trim($m[0]), 1));
                    $tmp = trim(@$m[1]);
                    
                    if (0 === PMF_String::strpos($tmp, 'array')) {
                        $retval[$key] = PMF_String::substr($tmp, 0, -1);
                    } else {
                        $retval[$key] = PMF_String::substr($tmp, 1, -2);
                    }
                }
            }
        }
    
        return $retval;
    }
    
    
    /**
     * Get the translation ratio of the language files
     * 
     * @param string $filepathExemplary Exemplary file path
     * @param string $filepathToCheck   Filepath to check
     * 
     * @return integer
     */
    public function getTranslatedPercentage($filepathExemplary, $filepathToCheck)
    {
        $exemplary = $this->getVars($filepathExemplary);
        $toCheck   = $this->getVars($filepathToCheck);

        // Number of plural forms in both languages
        $exemplaryNPlurals  = intval($exemplary['PMF_LANG[nplurals]']);
        $toCheckNPlurals    = intval($toCheck['PMF_LANG[nplurals]']);
        // One English plural form is equal to (xx/en) of xx plural forms (1/2, 2/2, 3/2,..,6/2..)
        $pluralsRatio       = ($toCheckNPlurals != -1) ? ($toCheckNPlurals/$exemplaryNPlurals) : 1;

        $retval = $countAll = $countTranslated = 0;
        
        if ($exemplary) {
            while (list($key, $val) = each($exemplary)) {
                if (!$this->isKeyIgnorable($key) && !$this->isValIgnorable($val)) {
                    if ($this->isKeyAFirstPluralForm($key)) {
                        if ($toCheckNPlurals != -1 && isset($toCheck[$key]) && $toCheck[$key] != $val) {
                            $countTranslated++;
                        }
                        $countAll += $pluralsRatio;
                    } elseif ($this->isKeyASecondPluralForm($key)) {
                        // Don't count plural translations if plural forms are not supported
                        for ($i = 1; $i < $toCheckNPlurals; $i++) {
                            $keyI = str_replace('[1]', "[$i]", $key);
                            if (isset($toCheck[$keyI]) && $toCheck[$keyI] != $val) {
                                $countTranslated++;
                            }
                        }
                        $countAll += $pluralsRatio;
                    } else {
                        if (isset($toCheck[$key]) && $toCheck[$key] != $val) {
                            $countTranslated++;
                        }
                        $countAll++;
                    }
                }
            }
            
            $retval = floor(100 * $countTranslated / $countAll);
        }
        
        unset($exemplary, $toCheck);
        
        return $retval;
    }
    
    
    /**
     * Check if the key can be ignored while comparing
     * 
     * @param string $key Key
     * 
     * @return boolean
     */
    public function isKeyIgnorable($key)
    {
        $keyIgnore = array('PMF_LANG[metaCharset]',
                           'PMF_LANG[metaLanguage]',
                           'PMF_LANG[language]',
                           'PMF_LANG[dir]',
                           'PMF_LANG[nplurals]');

        return in_array($key, $keyIgnore);
    }

    /**
     * Check if the key is a first plural form
     *
     * @param string $key Key
     *
     * @return boolean
     */
    public function isKeyAFirstPluralForm($key)
    {
        return (PMF_String::strpos($key, '[0]') !== false);
    }

    /**
     * Check if the key is a second plural form
     *
     * @param string $key Key
     *
     * @return boolean
     */
    public function isKeyASecondPluralForm($key)
    {
        return (PMF_String::strpos($key, '[1]') !== false);
    }

    /**
     * Check if we can ignore a value while comparing. Actually
     * catching empty and non alphanumeric strings
     * 
     * @param string $val Value
     * 
     * @return boolean
     */
    public function isValIgnorable($val)
    {
        return PMF_String::preg_match('/^[^a-z0-9]*$/i', $val);
    }
}
