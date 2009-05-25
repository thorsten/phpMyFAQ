<?php
/**
 * Interface translation tool functionality
 *
 * @package    phpMyFAQ
 * @subpackage PMF_TransTool
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-05-12
 * @version    SVN: $Id$
 * @copyright  2006-2009 phpMyFAQ Team
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
 */

/**
 * PMF_TransTool
 *
 * @package    phpMyFAQ
 * @subpackage PMF_TransTool
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-05-12
 * @version    SVN: $Id$
 * @copyright  2006-2009 phpMyFAQ Team
 */
class PMF_TransTool
{
    /**
     * Parse language file and get vars it does contain
     *
     * @param string $filepath
     * 
     * @return array
     */
    public function getVars($filepath)
    {
        $retval = array();
        
        if(file_exists($filepath) && is_readable($filepath)) {
        
            $orig = file($filepath);
            
            while(list(,$line) = each($orig)) {
                $line = rtrim($line);
                /**
                 * Bypass all but variable definitions
                 */
                if(strlen($line) && '$' == $line[0]) {
                    /**
                     * $PMF_LANG["key"] = "val";
                     * or
                     * $PMF_LANG["key"] = array(0 => "something", 1 => ...);
                     * turns to something like  array('$PMF_LANG["key"]', '"val";')
                     */
                    $m = explode("=", $line, 2);
                    
                    $key = str_replace(array('["', '"]', '[\'', '\']'), array('[', ']', '[', ']'), PMF_String::substr(trim($m[0]), 1));
                    
                    $tmp = trim(@$m[1]);
                    if(0 === PMF_String::strpos($tmp, 'array')) {
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
     * @param string $filepathExemplary
     * @param string $filepathToCheck
     * 
     * @return integer
     */
    public function getTranslatedPercentage($filepathExemplary, $filepathToCheck)
    {
        $exemplary = $this->getVars($filepathExemplary);
        $toCheck = $this->getVars($filepathToCheck);
        
        $retval = $countAll = $countTranslated = 0;
        
        if($exemplary) {
            while(list($key, $val) = each($exemplary)) {
                if(isset($toCheck[$key]) && $toCheck[$key] != $val) {
                    $countTranslated++;
                }
                
                $countAll++;
            }
            
            $retval = floor(100*$countTranslated/$countAll);
        }
        
        unset($exemplary, $toCheck);
        
        return $retval;
    }
}
?>