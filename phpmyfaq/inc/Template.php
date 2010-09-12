<?php
/**
 * The PMF_Template class provides methods and functions for the
 * template parser
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
 * @package   PMF_Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-22
 */

/**
 * PMF_Template
 *
 * @category  phpMyFAQ
 * @package   PMF_Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-22
 */
class PMF_Template
{
    /**
     * The template array
     *
     * @var array
     */
    public $templates = array();

    /**
     * The output array
     *
     * @var array
     */
    private $outputs = array();

    /**
     * The blocks array
     *
     * @var array
     */
    private $blocks = array();

    /**
     * array containing the touched blocks
     *
     * @var array
     */
    private $blocksTouched = array();

    /**
     * Name of active template set
     * 
     * @var string
     */
    private static $tplSetName;

    /**
     * Constructor
     *
     * Combine all template files into the main templates array
     *
     * @param array  $myTemplate Templates
     * @param string $tplSetName active template set name
     * 
     * @return PMF_Template
     */
    public function __construct(Array $myTemplate, $tplSetName = 'default')
    {
        self::$tplSetName = $tplSetName;
        
        foreach ($myTemplate as $templateName => $filename) {
            $this->templates[$templateName] = $this->readTemplate("template/$tplSetName/$filename", $templateName);
        }
    }

    /**
     * This function merges two templates
     *
     * @param string $name   Name of the template to include
     * @param string $toname Name of the new template
     * 
     * @return void
     */
    public function includeTemplate($name, $toname)
    {
        $this->outputs[$toname] = str_replace('{'.$name.'}', 
                                              $this->outputs[$name], 
                                              $this->outputs[$toname]);
        $this->outputs[$name]   = '';
    }

    /**
     * Parses the template
     *
     * @param string $templateName    Name of the template
     * @param array  $templateContent Content of the template
     * 
     * @return void
     */
    public function processTemplate($templateName, Array $templateContent)
    {
        $tmp       = $this->templates[$templateName];
        $rawBlocks = $this->_readBlocks($tmp);

        // process blocked content
        if (isset($this->blocks[$templateName])) {
            foreach ($rawBlocks as $key => $rawBlock) {
                if (in_array($key, $this->blocksTouched) && $key != 'unblocked') {
                    $tmp = str_replace($rawBlock, $this->blocks[$templateName][$key], $tmp);
                    $tmp = PMF_String::preg_replace('/\[.+\]/', '', $tmp);
                } elseif ($key != 'unblocked') {
                    $tmp = str_replace($rawBlock, '', $tmp);
                    $tmp = PMF_String::preg_replace('/\[.+\]/', '', $tmp);
                }
            }
        }

        // process unblocked content
        if (isset($this->blocks[$templateName]['unblocked'])) {
            $templateContent = $this->_checkContent($templateContent);
            foreach ($this->blocks[$templateName]['unblocked'] as $tplVar) {
                $varName = PMF_String::preg_replace('/[\{\}]/', '', $tplVar);
                if (isset($templateContent[$varName])) {
                    $tmp = str_replace($tplVar, $templateContent[$varName], $tmp);
                }
            }
        }

        // add magic variables for each template
        $tmp = str_replace('{tplSetName}', self::$tplSetName, $tmp);
        
        if (isset($this->outputs[$templateName])) {
            $this->outputs[$templateName] .= $tmp;
        } else {
            $this->outputs[$templateName] = $tmp;
        }
    }

    /**
     * This function prints the whole parsed template file.
     *
     * @return void
     */
    public function printTemplate()
    {
        foreach ($this->outputs as $val) {
            print str_replace("\n\n", "\n", $val);
        }
    }

    /**
     * This function adds two template outputs.
     *
     * @param array $name   Name of the template to add
     * @param array $toname Name of the new template
     * 
     * @return void
     */
    public function addTemplate(Array $name, Array $toname)
    {
        $this->outputs[$toname] .= $this->outputs[$name];
        $this->outputs[$name] = '';
    }

    /**
     * This function reads a template file.
     *
     * @param string $filename     Filename
     * @param string $tplName Name of the template
     * 
     * @return string
     */
    public function readTemplate($filename, $tplName)
    {
        if (file_exists($filename)) {
            $tplContent             = file_get_contents($filename);
            $this->blocks[$tplName] = $this->_readBlocks($tplContent);
            return $tplContent;
        } else {
            return '<p><span style="color: red;">Error:</span> Cannot open the file '.$filename.'.</p>';
        }
    }

    /**
     * This function processes the block
     *
     * @param string $templateName Name of the template
     * @param string $blockName    Block name
     * @param array  $blockContent Content of the block
     * 
     * @return void
     */
    public function processBlock($templateName, $blockName, Array $blockContent)
    {
        if (isset($this->blocks[$templateName][$blockName])) {
            $block = $this->blocks[$templateName][$blockName];
    
            // security check
            $blockContent = $this->_checkContent($blockContent);
            foreach ($blockContent as $var => $val) {
                // if array given, multiply block
                if (is_array($val)) {
                    $block = $this->_multiplyBlock($this->blocks[$templateName][$blockName], $blockContent);
                    break;
                } else {
                    $block = str_replace('{'.$var.'}', $val, $block);
                }
            }
    
            $this->blocksTouched[] = $blockName;
            $block = str_replace('&acute;', '`', $block);
            $this->blocks[$templateName][$blockName] = $block;
        }
    }

    /**
     * Set the template set name to use
     * 
     * @param $tplSetName
     * 
     * @return void
     */
    public static function setTplSetName($tplSetName)
    {
        self::$tplSetName = $tplSetName;
    }
    
    /**
     * Get name of the actual template set
     * 
     * @return string
     */
    public static function getTplSetName()
    {
        return self::$tplSetName;   
    }
    
    //
    // Private Functions
    //

    /**
     * This function multiplies blocks
     *
     * @param  string $block        Blockname
     * @param  array  $blockContent Content of block
     * 
     * @return string implode('', $tmpBlock)
     */
    private function _multiplyBlock($block, $blockContent)
    {
        $multiplyTimes = 0;

        //create the replacement array
        foreach ($blockContent as $var => $val) {
            if (is_array($val) && !$multiplyTimes) {
                //the first array in $blockContent defines $multiplyTimes
                $multiplyTimes = count($val);
                $replace[$var] = $val;
            } elseif ((is_array($val) && $multiplyTimes)) {
                //check if all further arrays in $blockContent have the same lenght
                if ($multiplyTimes == count($val)) {
                    $replace[$var] = $val;
                } else {
                    die('Wrong parameter length!');
                }
            } else{
                //multiply strings to $multiplyTimes
                for ($i=0; $i<$multiplyTimes; $i++){
                    $replace[$var][] = $val;
                }
            }
        }

        //do the replacement
        for ($i = 0; $i < $multiplyTimes; $i++) {
            $tmpBlock[$i] = $block;
            foreach ($replace as $var => $val) {
                $tmpBlock[$i] = str_replace('{'.$var.'}', $val[$i], $tmpBlock[$i]);
            }
        }

        return implode('',$tmpBlock);

    }

    /**
     * This function reads the block
     *
     * @param  string $tpl Block to read
     * 
     * @return string
     */
    private function _readBlocks($tpl)
    {
        $tmpBlocks = array();
        
        // read all blocks into $tmpBlocks
        PMF_String::preg_match_all('/\[[[:alpha:]]+\]\s*[\W\w\s\{\}\<\>\=\"\/]*?\s*\[\/[[:alpha:]]+\]/', $tpl, $tmpBlocks);
        
        $unblocked = $tpl;
        if (isset($tmpBlocks)) {
            $blockCount = count($tmpBlocks[0]);
            for ($i = 0 ; $i < $blockCount; $i++) {
                $name = '';
                //find block name
                PMF_String::preg_match('/\[.+\]/', $tmpBlocks[0][$i], $name);
                $name = PMF_String::preg_replace('/[\[\[\/\]]/', '', $name);
                //remove block tags from block
                $res = str_replace('[' . $name[0] . ']','',$tmpBlocks[0][$i]);
                $res = str_replace('[/' . $name[0] . ']','',$res);
                $tplBlocks[$name[0]] = $res;

                //unblocked content
                $unblocked = str_replace($tplBlocks[$name[0]], '', $unblocked);
                $unblocked = str_replace('[' . $name[0] . ']','',$unblocked);
                $unblocked = str_replace('[/' . $name[0] . ']','',$unblocked);
            }

            $hits = array();
            PMF_String::preg_match_all('/\{.+?\}/', $unblocked, $hits);
            $tplBlocks['unblocked'] = $hits[0];
        } else {
            // no blocks defined
            $tplBlocks = $tpl;
        }

        return $tplBlocks;
    }

    /**
     * This function checks the content
     *
     * @param  string $content Content to check
     * 
     * @return string
     */
    private function _checkContent($content)
    {
        // Security measure: avoid the injection of php/shell-code
        $search  = array('#<\?php#i', '#\{$\{#', '#<\?#', '#<\%#', '#`#', '#<script[^>]+php#mi');
        $phppattern1 = "&lt;?php";
        $phppattern2 = "&lt;?";
        $replace = array($phppattern1, '', $phppattern2, '', '' );

        // Hack: Backtick Fix
        $content = str_replace('`', '&acute;', $content);

        foreach ($content as $var => $val) {
            if (is_array($val)) {
                foreach ($val as $key => $value) {
                    $content[$var][$key] = PMF_String::preg_replace($search, $replace, $value);
                }
            } else {
                $content[$var] = PMF_String::preg_replace($search, $replace, $val);
            }
        }

        return $content;
    }
}
