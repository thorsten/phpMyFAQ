<?php

/**
 * The PMF_Template class provides methods and functions for the
 * template parser.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-22
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Template.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-22
 */
class PMF_Template
{
    /**
     * The template array.
     *
     * @var array
     */
    public $templates = [];

    /**
     * The output array.
     *
     * @var array
     */
    private $outputs = [];

    /**
     * The blocks array.
     *
     * @var array
     */
    private $blocks = [];

    /**
     * array containing the touched blocks.
     *
     * @var array
     */
    private $blocksTouched = [];

    /**
     * Name of active template set.
     *
     * @var string
     */
    private static $tplSetName;

    /**
     * Constructor.
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
            $this->templates[$templateName] = $this->_include(
                'assets/template/'.$tplSetName.'/'.$filename,
                $templateName
            );
        }
    }

    /**
     * This function merges two templates.
     *
     * @param string $from Name of the template to include
     * @param string $into Name of the new template
     */
    public function merge($from, $into)
    {
        $this->outputs[$into] = str_replace('{'.$from.'}', $this->outputs[$from], $this->outputs[$into]);
        $this->outputs[$from] = null;
    }

    /**
     * Parses the template.
     *
     * @param string $templateName    Name of the template
     * @param array  $templateContent Content of the template
     */
    public function parse($templateName, Array $templateContent)
    {
        $tmp = $this->templates[$templateName];
        $rawBlocks = $this->_readBlocks($tmp);

        // process blocked content
        if (isset($this->blocks[$templateName])) {
            foreach ($rawBlocks as $key => $rawBlock) {
                if (in_array($key, $this->blocksTouched) && $key != 'unblocked') {
                    $tmp = str_replace($rawBlock, $this->blocks[$templateName][$key], $tmp);
                    $tmp = str_replace('['.$key.']', '', $tmp);
                    $tmp = str_replace('[/'.$key.']', '', $tmp);
                } elseif ($key != 'unblocked') {
                    $tmp = str_replace($rawBlock, '', $tmp);
                    $tmp = str_replace('['.$key.']', '', $tmp);
                    $tmp = str_replace('[/'.$key.']', '', $tmp);
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
     * This function renders the whole parsed templates and returns it.
     *
     * @return string
     */
    public function render()
    {
        $output = '';
        foreach ($this->outputs as $val) {
            $output .= str_replace("\n\n", "\n", $val);
        }

        return $output;
    }

    /**
     * This function adds two parsed templates.
     *
     * @param string $from Name of the template to add
     * @param string $into Name of the new template
     */
    public function add($from, $into)
    {
        $this->outputs[$into] .= $this->outputs[$from];
        $this->outputs[$from] = null;
    }

    /**
     * This function processes the block.
     *
     * @param string $templateName Name of the template
     * @param string $blockName    Block name
     * @param array  $blockContent Content of the block
     */
    public function parseBlock($templateName, $blockName, Array $blockContent)
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
     * Set the template set name to use.
     *
     * @param $tplSetName
     */
    public static function setTplSetName($tplSetName)
    {
        self::$tplSetName = $tplSetName;
    }

    /**
     * Get name of the actual template set.
     *
     * @return string
     */
    public static function getTplSetName()
    {
        return self::$tplSetName;
    }

    //
    // Protected and private methods
    //

    /**
     * This function reads a template file.
     *
     * @param string $filename Filename
     * @param string $tplName  Name of the template
     *
     * @return string
     */
    protected function _include($filename, $tplName)
    {
        if (file_exists($filename)) {
            $tplContent = file_get_contents($filename);
            $this->blocks[$tplName] = $this->_readBlocks($tplContent);

            return $tplContent;
        } else {
            return '<p><span style="color: red;">Error:</span> Cannot open the file '.$filename.'.</p>';
        }
    }

    /**
     * This function multiplies blocks.
     *
     * @param string $block        Blockname
     * @param array  $blockContent Content of block
     *
     * @return string implode('', $tmpBlock)
     */
    private function _multiplyBlock($block, $blockContent)
    {
        $multiplyTimes = 0;
        $replace = [];
        $tmpBlock = [];

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
            } else {
                //multiply strings to $multiplyTimes
                for ($i = 0; $i < $multiplyTimes; ++$i) {
                    $replace[$var][] = $val;
                }
            }
        }

        //do the replacement
        for ($i = 0; $i < $multiplyTimes; ++$i) {
            $tmpBlock[$i] = $block;
            foreach ($replace as $var => $val) {
                $tmpBlock[$i] = str_replace('{'.$var.'}', $val[$i], $tmpBlock[$i]);
            }
        }

        return implode('', $tmpBlock);
    }

    /**
     * This function reads the block.
     *
     * @param string $tpl Block to read
     *
     * @return array
     */
    private function _readBlocks($tpl)
    {
        $tmpBlocks = [];

        // read all blocks into $tmpBlocks
        PMF_String::preg_match_all('/\[([[:alpha:]]+)\]\s*[\W\w\s\{\}\<\>\=\"\/]*?\s*\[\/\1\]/', $tpl, $tmpBlocks);

        $unblocked = $tpl;
        if (isset($tmpBlocks)) {
            $blockCount = count($tmpBlocks[0]);
            for ($i = 0; $i < $blockCount; ++$i) {
                $name = '';
                //find block name
                PMF_String::preg_match('/\[.+\]/', $tmpBlocks[0][$i], $name);
                $name = PMF_String::preg_replace('/[\[\[\/\]]/', '', $name);
                //remove block tags from block
                $res = str_replace('['.$name[0].']', '', $tmpBlocks[0][$i]);
                $res = str_replace('[/'.$name[0].']', '', $res);
                $tplBlocks[$name[0]] = $res;

                //unblocked content
                $unblocked = str_replace($tplBlocks[$name[0]], '', $unblocked);
                $unblocked = str_replace('['.$name[0].']', '', $unblocked);
                $unblocked = str_replace('[/'.$name[0].']', '', $unblocked);
            }

            $hits = [];
            PMF_String::preg_match_all('/\{.+?\}/', $unblocked, $hits);
            $tplBlocks['unblocked'] = $hits[0];
        } else {
            // no blocks defined
            $tplBlocks = $tpl;
        }

        return $tplBlocks;
    }

    /**
     * This function checks the content.
     *
     * @param string $content Content to check
     *
     * @return array
     */
    private function _checkContent($content)
    {
        // Security measure: avoid the injection of php/shell-code
        $search = array('#<\?php#i', '#\{$\{#', '#<\?#', '#<\%#', '#`#', '#<script[^>]+php#mi');
        $phppattern1 = '&lt;?php';
        $phppattern2 = '&lt;?';
        $replace = array($phppattern1, '', $phppattern2, '', '');

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
