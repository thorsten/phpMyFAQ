<?php
/**
 * The main stop words configuration frontend
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Anatoliy Belsky
 * @since      2009-04-01
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission['editconfig']) {
    exit();
}

// actions defined by url: user_action=
$userAction = PMF_Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_STRING, 'listConfig');

// Save the configuration
if ('save' == $userAction) {
	
} else if ('load' == $userAction) {
    
    
}

?>
<table class="list">
<tr>
	<td>
	<select onchange="loadStopWordsByLang(this.options[this.selectedIndex].value)">
	<option value="none">---</option>
<?php 
    foreach($languageCodes as $key => $value) {
?><option value="<?php print $key ?>"><?php print $value?></option>
		
<?php
    }
?>
	</select>
	</td>
</tr>
<tr><td>
<div id="stopwords_content"></div>
</td></tr>
</table>
<script>

function loadStopWordsByLang(lang) {
    if('none' == lang) {
		return;
    }
}
</script>
