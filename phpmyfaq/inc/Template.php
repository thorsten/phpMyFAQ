<?php
/**
 * $Id: Template.php,v 1.3 2007-02-28 20:10:41 thorstenr Exp $
 *
 * PMF_Template
 *
 * The PMF_Template class provides methods and functions for the
 * template parser
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Eden Akhavi <eden.akhavi@ltt.com>
 * @author      Bianka Martinovic <blackbird@webbird.de>
 * @package     phpmyfaqTemplate
 * @since       2002-08-22
 * @copyright   (c) 2002-2007 phpMyFAQ Team
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
     * @var   mixed
     * @see   __construct(), processTemplate()
     */
    var $templates = array();

    /**
     * The output array
     *
     * @var   mixed
     * @see   includeTemplate(), processTemplate(), printTemplate(), addTemplate()
     */
	var $outputs = array();

    /**
    * Constructor
    *
    * Combine all template files into the main templates array
    *
    * @param    array
    * @access   public
    */
	function PMF_Template($myTemplate)
    {
		foreach ($myTemplate as $templateName => $filename) {
            $this->templates[$templateName] = $this->readTemplate($filename);
        }
    }

    /**
    * This function merges two templates
    *
    * @param    string
    * @param    string
    * @access   public
    */
    function includeTemplate($name, $toname)
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
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de
     */
	function processTemplate($templateName, $myTemplate)
    {
        $tmp = $this->templates[$templateName];

        // Security measure: avoid the injection of php/shell-code
        $search  = array('#<\?php#i', '#\{$\{#', '#<\?#', '#<\%#', '#`#', '#<script[^>]+php#mi');
        $phppattern1 = "&lt;?php";
        $phppattern2 = "&lt;?";
        if (isset($PMF_CONF['parse_php']) && $PMF_CONF['parse_php']) {
            $phppattern1 = "<?php";
            $phppattern2 = "<?";
        }
        $replace = array($phppattern1, '', $phppattern2, '', '' );

        // Hack: Backtick Fix
        $myTemplate = str_replace('`', '&acute;', $myTemplate);

        foreach ($myTemplate as $var => $val) {
            $val = preg_replace($search, $replace, $val);
            $tmp = str_replace('{'.$var.'}', $val, $tmp);
        }

        // Hack: Backtick Fix
        $tmp = str_replace('&acute;', '`', $tmp);

		if (isset($this->outputs[$templateName])) {
			$this->outputs[$templateName] .= $tmp;
        } else {
			$this->outputs[$templateName] = $tmp;
        }
    }

    /**
    * This function prints the whole parsed template file.
    *
    * @access   public
    */
	function printTemplate()
    {
		foreach ($this->outputs as $val) {
            print str_replace("\n\n", "\n", $val);
        }
	}

    /**
    * getTemplateContents()
    *
    * Returns the parsed template, but don't print
    *
    * @return   string
    * @access   public
    * @since    2006-01-03
    * @author   Bianka Martinovic <blackbird@webbird.de>
    */
	function getTemplateContents()
	{
		foreach ($this->outputs as $val) {
			$output .= str_replace("\n\n", "\n", $val);
		}
		return $output;
	}

    /**
    * This function adds two template outputs.
    *
    * @param    array
    * @param    array
    * @access   public
    */
	function addTemplate($name, $toname)
    {
		$this->outputs[$toname] .= $this->outputs[$name];
		$this->outputs[$name] = '';
	}

    /**
    * This function reads a template file.
    *
    * @param    string $filename
    * @return   string
    * @access   private
    */
	function readTemplate($filename)
    {
		if (file_exists($filename)) {
			return file_get_contents($filename);
        } else {
             return '<p><span style="color: red;">Error:</span> Cannot open the file '.$filename.'.</p>';
        }
	}
}
