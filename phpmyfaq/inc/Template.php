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
 * @author    Guillaume Le Maout <>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-22
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Template
 *
 * @category  phpMyFAQ
 * @package   PMF_Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @author    Guillaume Le Maout <>
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
     * Is ajax active or not 
     * 
     * @var bool
     */    
    public $ajax_active;
    
    /** 
     * Name of ajax request
     * 
     * @var string
     */
    public $ajax_request;    
        
    /** 
     * "true" if change language request
     * 
     * @var string
     */
    public $change_lang;    
    
    /**
     * Ajax template variables list
     * 
     * @var array
     */    
    public $varAjax = array();

    /** 
     * Modified template content for ajax
     *
     * @var string
     */
    public $ajaxHTML;
    
    /** 
     * Ajax template variables map
     * 
     * @var string
     */
    public $parsedTemplates = array();
    
    /** 
     * Ajax template variables map modified
     *
     * @var string
     */
    public $ajaxOutput = array();

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
        if ($this->ajax_active && $this->ajax_request) {
            foreach ($this->ajaxOutput as $var => $val) {
                foreach ($val as $key => $value) {
                    if (strstr($value, '{' . $name . '}')) {
                        $this->ajaxOutput[$var][$key] = str_replace('{' . $name . '}', 
                                                                    $this->outputs[$name], 
                                                                    $this->ajaxOutput[$var][$key]);
                    }
                }
            }
        } else {
            $this->outputs[$toname] = str_replace('{'.$name.'}', 
                                                  $this->outputs[$name], 
                                                  $this->outputs[$toname]);
            $this->outputs[$name]   = '';
        }
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
        if ($this->ajax_active&&$templateName == "index") {
            $tmp  = $this->ajaxHTML;
            if ($this->ajax_request) {
                $this->setAjaxOutput();
                if (!count($this->ajaxOutput)){
                    $this->setAjaxOutput();
                } 

                foreach ($this->ajaxOutput as $var => $val) {
                    foreach ($val as $key => $value) {
                        $this->ajaxOutput[$var][$key] = $this->processContent($this->ajaxOutput[$var][$key], 
                                                                              $templateName, 
                                                                              $templateContent);
                    }
                }
            }
        } else {
            $tmp  = $this->templates[$templateName];
        }

        $tmp = $this->processContent($tmp, $templateName, $templateContent);
    }
    
    /**
     * Parses the template
     *
     * @param  string $templateName    Name of the template
     * @param  array  $templateContent Content of the template
     * @return void
     */
    private function processContent($tmp, $templateName, Array $templateContent) 
    {
        $rawBlocks = $this->_readBlocks($tmp);

        // process blocked content
        if (isset($this->blocks[$templateName])) {
            foreach ($rawBlocks as $key => $rawBlock) {
                if (in_array($key, $this->blocksTouched) && $key != 'unblocked') {
                    $tmp = str_replace($rawBlock, $this->blocks[$templateName][$key], $tmp);
                    $tmp = str_replace('[' . $key . ']', '', $tmp);
                    $tmp = str_replace('[/' . $key . ']', '', $tmp);
                } elseif ($key != 'unblocked') {
                    $tmp = str_replace($rawBlock, '', $tmp);
                    $tmp = str_replace('[' . $key . ']', '', $tmp);
                    $tmp = str_replace('[/' . $key . ']', '', $tmp);
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
		return $tmp;
    }

    /**
     * This function prints the whole parsed template file.
     *
     * @return void
     */
    public function printTemplate()
    {
        if ($this->ajax_active&&$this->ajax_request) {
            print json_encode($this->ajaxOutput);
        } else {
            foreach ($this->outputs as $val) {
                print str_replace("\n\n", "\n", $val);
            }
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
        $this->outputs[$name]    = '';
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
        PMF_String::preg_match_all('/\[([[:alpha:]]+)\]\s*[\W\w\s\{\}\<\>\=\"\/]*?\s*\[\/\1\]/', $tpl, $tmpBlocks);
        
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
    
    /**
     * Init ajax output
     *
     * @return void
     */
    public function TemplateAjaxInit()
    {    
        $tpl_doc = new DOMDocument();
        
        //If debug mode wrap and use debug console for ajax error response
        if (DEBUG) {
            $index_tpl = str_replace('{debugMessages}', '<div id="debugconsole">{debugMessages}</div>', $this->templates["index"]);
        } else {
            $index_tpl = $this->templates["index"];
        }
        
        $tpl_doc->loadHTML($index_tpl);
        $head = $tpl_doc->getElementsByTagName('head')->item(0);
        
        //Change STYLE node to LINK node to switch stylesheets dynamically with IE
        $style_items = $tpl_doc->getElementsByTagName('style');
        $lim = $style_items->length;
        for ($i = 0; $i < $lim; $i++) {
            $style = $style_items->item(0);
            $link = $tpl_doc->createElement('link');
            $href = preg_replace('/\s*@import\s+url\(([^)]+)\s*\)\s*;\s*/', '$1', $style->nodeValue);
            $link->setAttributeNode(new DOMAttr('href', $href));
            $link->setAttributeNode(new DOMAttr('media', $style->getAttribute('media')));
            $link->setAttributeNode(new DOMAttr('rel', 'stylesheet'));
            $link->setAttributeNode(new DOMAttr('type', 'text/css'));
            $head->replaceChild($link, $style);
        }
        
        //Create a "map" of the document
        $this->processTemplateAjax($tpl_doc);
        
        //Add javascript to handle response
        $script = $tpl_doc->createElement("script");
        $script->setAttributeNode(new DOMAttr('type', 'text/javascript'));
        $script->setAttributeNode(new DOMAttr('src', 'inc/js/ajax_menux.js'));
        $head->appendChild($script);
        
        //Save result
        $this->ajaxHTML = urldecode($tpl_doc->saveHTML());
    }
    
    /**
     * Set ajax output
     *
     * @return void
     */
    private function setAjaxOutput()
    {
        $pattern = "/{(".implode('|', array_keys(array_filter($this->varAjax, array($this, 'filterAjaxVar')))).")}/msi";
        foreach ($this->parsedTemplates as $id => $couples) {
            foreach ($couples as $key => $value) {
                if ($this->change_lang||preg_match($pattern, $value)) {
                    $this->ajaxOutput[$id][$key] = $value;
                }
            }
        }
    }
    
    /**
     * Filter ajax output callback
     *
     * @return bool
     */
    private function filterAjaxVar($var)
    {    
        if ($this->ajax_request) {
            return PMF_String::preg_match('/'.$var.'/msi', $this->ajax_request);
        } else {
            return true;
        }
    }
    
    /**
     * Parse template content with DOM extension
     * 
     * @param DOMNode $root
     * @return void
    */
    private function processTemplateAjax($root)
    {
        if ($root->hasAttributes()) {
            // Look for template variable in node attributes
            if (!isset($attr_arr)) {
                $attr_arr =  array();
            }
            $attr_arr = $this->search_attr_node($root);
            if ($attr_arr) {
                $this->parsedTemplates[$this->get_node_id($root)] = $attr_arr;
            }
        }
        
        $children     = $root->childNodes;
        $tmp_node_arr = array();
        if (isset($children->length)) {
            // Look for template variable in HTML / childrens
            for($i = 0; $i < $children->length; $i++) {
                $child = $children->item($i);
                if ($child->nodeType == 3 || $child->nodeType == 4 && $child->parentNode->nodeType < 9) {
                    if ($this->search_text_node($child)) {
                        // Look for template variable in node text
                        $id = $this->get_node_id($root);
                        $this->parsedTemplates[$id]['html'] = $this->get_node_innerHTML($root); 
                    }
                } elseif($this->search_node_HTML($child)) {
                    array_push($tmp_node_arr, $child);
                }
            }
            
            for ($i = 0; $i < count($tmp_node_arr); $i++) {
                $this->processTemplateAjax($tmp_node_arr[$i]);
            }
        }
    }
    
    /**
     * Search template variable in node HTML
     * 
     * @param DOMNode $node
     * @return bool
    */
    private function search_node_HTML($node)
    {    
        if ($node->nodeType >= 9) {
            return true;
        } else {
            return $this->search_vars($this->get_node_HTML($node));
        }
    }
    
    /**
     * Search template variable in node attributes
     * 
     * @param DOMNode $node
     * @return false or array
    */
    private function search_attr_node($node)
    {
        $tmp_arr =  array();
        $attrs   = $node->attributes; 
        foreach ($attrs as $i => $attr) {
            if ($this->search_vars($attr->value)) {
                $tmp_arr[$attr->name] = urldecode($attr->value);
            }
        }
        if (count($tmp_arr)) { 
            return $tmp_arr;
        } else {
            return false;
        }
    }
    
    /**
     * Search template variable in node test
     * 
     * @param DOMNode $node
     * @return void
    */
    private function search_text_node($node)
    {
        return $this->search_vars($node->nodeValue);
    }
    
    /**
     * 
     * @param unknown_type $text
     */
    private function search_vars($text)
    {    
        if (DEBUG) {
            $pattern = "/{(?!meta|baseHref|phpmyfaqversion)\w+}/msi";
        } else {
            $pattern = "/{(?!debug|meta|baseHref|phpmyfaqversion)\w+}/msi";
        }

        if (PMF_String::preg_match($pattern, $text)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Find a node ID or generate and set one
     * 
     * @param DOMNode $node
     * @return string
    */
    private function get_node_id(DOMNode $node)
    {
        if ($node->hasAttributes() && $node->attributes->getNamedItem('id')) {
            $id = $node->attributes->getNamedItem('id')->nodeValue;
        } else {
            $id = "pmf_gen-".count($this->parsedTemplates);
            $node->setAttributeNode(new DOMAttr('id', $id));
        }
        return $id;
    }
    
    /**
     * Get a node HTML
     * 
     * @param DOMNode $node
     * @return string
    */
    private function get_node_HTML(DOMNode $node)
    {
        $tmp_dom = new DOMDocument(); 
        $tmp_dom->appendChild($tmp_dom->importNode($node, true)); 
        return urldecode(trim($tmp_dom->saveHTML())); 
    }
    
    /**
     * Get a node innerHTML
     * 
     * @param DOMNode $node
     * @return string
    */
    private function get_node_innerHTML($node)
    {
        $tmp_dom  = new DOMDocument();
        $nodeList = $node->childNodes;
        for ($i = 0; $i < $nodeList->length; $i++) {
            $tmp_dom->appendChild($tmp_dom->importNode($nodeList->item($i), true)); 
        }
        return urldecode(trim($tmp_dom->saveHTML())); 
    }
}
