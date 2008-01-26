<?php
/**
 * PMF_Template
 *
 * The PMF_Template class provides methods and functions for the
 * template parser
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @since     2002-08-22
 * @copyright 2002-2008 phpMyFAQ Team
 * @version   CVS: $Id: Template.php,v 1.6 2008-01-26 11:55:37 thorstenr Exp $
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
    private $blocks;

    /**
     * Constructor
     *
     * Combine all template files into the main templates array
     *
     * @param  array $myTemplate Templaes
     * @access public
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author Jan Mergler <jan.mergler@gmx.de>
     */
    public function __construct($myTemplate)
    {
        foreach ($myTemplate as $templateName => $filename) {
            $this->templates[$templateName] = $this->readTemplate($filename, $templateName);
        }
    }

    /**
     * This function merges two templates
     *
     * @param   string  $name
     * @param   string  $toname
     * @return  void
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function includeTemplate($name, $toname)
    {
        $this->outputs[$toname] = str_replace('{'.$name.'}', $this->outputs[$name], $this->outputs[$toname]);
        $this->outputs[$name] = '';
    }

    /**
     * Parses the template
     *
     * @param   string
     * @param   array
     * @return  void
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Jan Mergler <jan.mergler@gmx.de>
     */
    public function processTemplate($templateName, $templateContent)
    {
        $tmp       = $this->templates[$templateName];
        $rawBlocks = $this->_readBlocks($tmp);

        // process blocked content
        if (isset($this->blocks[$templateName])) {
            foreach ($rawBlocks as $key => $rawBlock) {
                $tmp = str_replace($rawBlock, $this->blocks[$templateName][$key], $tmp);
                $tmp = preg_replace('/\[.+\]/', '', $tmp);
            }
        }

        // process unblocked content
        if (isset($this->blocks[$templateName]['unblocked'])) {

            $templateContent = $this->_checkContent($templateContent);
            foreach ($this->blocks[$templateName]['unblocked'] as $tplVar) {
                $varName = preg_replace('/[\{\}]/', '', $tplVar);
                if (isset($templateContent[$varName])) {
                    $tmp = str_replace($tplVar, $templateContent[$varName], $tmp);
                }
            }
        }

        if (isset($this->outputs[$templateName])) {
            $this->outputs[$templateName] .= $tmp;
        } else {
            $this->outputs[$templateName] = $tmp;
        }
    }

    /**
     * This function prints the whole parsed template file.
     *
     * @return  void
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
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
     * @param   array
     * @param   array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addTemplate($name, $toname)
    {
        $this->outputs[$toname] .= $this->outputs[$name];
        $this->outputs[$name] = '';
    }

    /**
     * This function reads a template file.
     *
     * @param   string $filename
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Jan Mergler <jan.mergler@gmx.de>
     */
    public function readTemplate($filename, $templateName)
    {
        if (file_exists($filename)) {

            $tpl = file_get_contents($filename);

            // read template blocks
            $this->blocks[$templateName] = $this->_readBlocks($tpl);

            return $tpl;
        } else {
            return '<p><span style="color: red;">Error:</span> Cannot open the file '.$filename.'.</p>';
        }
    }

    /**
     * This function processes the block
     *
     * @param  string $templateName
     * @param  string $blockName
     * @param  array  $blockContent
     * @return void
     * @author Jan Mergler <jan.mergler@gmx.de>
     */
    public function processBlock($templateName, $blockName, $blockContent)
    {
        $block = $this->blocks[$templateName][$blockName];

        // security check
        $blockContent = $this->_checkContent($blockContent);

        foreach ($blockContent as $var => $val) {
            if (is_array($val)) {
                foreach ($val as $item) {
                    $tmpBlock .= str_replace('{'.$var.'}', $item, $block);
                }
                $block = $tmpBlock;

            } else {
                $block = str_replace('{'.$var.'}', $val, $block);
            }

        }

        // Hack: Backtick Fix
        $block = str_replace('&acute;', '`', $block);

        $this->blocks[$templateName][$blockName] = $block;
    }

    //
    // Private Functions
    //

    /**
     * This function reads the block
     *
     * @param  string $tpl
     * @return string
     * @author Jan Mergler <jan.mergler@gmx.de>
     */
    private function _readBlocks($tpl)
    {
        $tmpBlocks = array();

        // read all blocks into $tmpBlocks
        preg_match_all('/\[.+\]\s*[\w\s\{\}\<\>\=\"\/]*\s*\[\/.+\]/', $tpl, $tmpBlocks);

        $unblocked = $tpl;

        if (isset($tmpBlocks)) {

            $blockCount = count($tmpBlocks[0]);

            for ($i = 0 ; $i < $blockCount; $i++) {

                $name = '';

                //find block name
                preg_match('/\[.+\]/', $tmpBlocks[0][$i], $name);
                $name = preg_replace('/[\[\[\/\]]/', '', $name);

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

            preg_match_all('/\{.+?\}/', $unblocked, $hits);
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
     * @param  string $content
     * @return string
     * @author Jan Mergler <jan.mergler@gmx.de>
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
            $content[$var] = preg_replace($search, $replace, $val);
        }

        return $content;
    }
}
