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

if ($permission['editconfig']) {
    printf('<h2>%s</h2', $PMF_LANG['ad_menu_stopwordsconfig']);
?>

<table class="list">
<tr>
    <td>
    <select onchange="loadStopWordsByLang(this.options[this.selectedIndex].value)" id="stopwords_lang_selector">
    <option value="none">---</option>
<?php 
    foreach($languageCodes as $key => $value) {
?><option value="<?php print strtolower($key) ?>"><?php print $value?></option>
        
<?php
    }
?>
    </select>
    <span id="stopwords_loading_indicator"></span>
    </td>
</tr>
<tr><td>
<div id="stopwords_content"></div>
</td></tr>
</table>
<script type="text/javascript">
/* <![CDATA[ */

/**
 * column count in the stop words table
 */
var max_cols = 4;


/**
 * Load stop words by language, build html and put 
 * it into stopwords_content container
 *
 * @param string lang language to retrieve the stopwords by
 *
 * @return void
 */
function loadStopWordsByLang(lang)
{
    if('none' == lang) {
        return;
    }

    $('#stopwords_loading_indicator').html('<img src="images/indicator.gif" />');
    
    $.get("index.php",
		  {action: "ajax", ajax: 'config', ajaxaction: "load_stop_words_by_lang", stopwords_lang: lang},
		  function (data, textStatus) {
		      $('#stopwords_content').html(buildStopWordsHTML(data));
		      $('#stopwords_loading_indicator').html('');
          },
          'json'
	);
}


/**
 * Supposed is stop words json data
 */
function buildStopWordsHTML(data)
{
    if('object' != typeof(data)) {
        return '';
    }
    
    var html = '<table class="list">';
    var elem_id;
    for(var i = 0; i < data.length; i++) {

        if(i % max_cols == 0) {
            html += '<tr id="stopwords_group_' + i + '">';
        }
        
        /**
         * id atribute is of the format stopword_<id>_<lang>
         */
        elem_id = buildStopWordInputElemId(data[i].id, data[i].lang);
        
        html += '<td>';
        html += buildStopWordInputElement(elem_id, data[i].stopword);
        html += '</td>';

        if(i % max_cols == max_cols - 1) {
            html += '</tr>';
        }
    }

    html += '</table>';
    html += '<a href="javascript: addStopWordInputElem();"><img src="images/add.gif" /></a>'

    return html;   
}

function buildStopWordInputElement(elem_id, stopword)
{
    elem_id = elem_id || buildStopWordInputElemId();
    stopword = stopword || '';
    var attrs = 'onblur="saveStopWord(this.id)" onkeydown="saveStopWord(this.id, event)" onfocus="saveOldValue(this.id)"';
    var element = '<input id="' + elem_id + '" value="' + stopword + '" ' + attrs + ' />';

    return element;
}

/**
 * Id atribute is of the format stopword_<id>_<lang>
 *
 * @param integer id database id of the word
 * @param string lang
 *
 * @return string 
 */
function buildStopWordInputElemId(id, lang)
{
    id = id || -1;
    lang = lang || $('#stopwords_lang_selector').val();
    
    return 'stopword_' + id + '_' + lang;
}


function parseStopWordInputElemId(elem_id)
{
    var info = elem_id.split('_');

    return {id: info[1], lang: info[2]};
}

function saveStopWord(elem_id, e)
{
    e = e || window.event || undefined;

    if(undefined != e) {
        var key = e.charCode || e.keyCode || 0;
        if(13 == key) {
            if('' == $('#' + elem_id).val()) {
                deleteStopWord(elem_id);
            } else {
                $('#' + elem_id).blur();
            }
        }

        return;
    }
    
    var info =  parseStopWordInputElemId(elem_id);
    
    if($('#' + elem_id).attr('old_value') != $('#' + elem_id).attr('value')) {        
        $.get("index.php",
              {action: "ajax",
               ajax: 'config',
               ajaxaction: "save_stop_word",
               stopword_id: info.id,
               stopword: $('#' + elem_id).val(),
               stopwords_lang: info.lang}
          );
    } else {
        if(0 > info.id && '' == $('#' + elem_id).attr('value')) {
            $('#' + elem_id).remove();
            return;
        }
    }
}

function saveOldValue(elem_id)
{
    $('#' + elem_id).attr('old_value', $('#' + elem_id).attr('value'));
}

function deleteStopWord(elem_id)
{
    var info = parseStopWordInputElemId(elem_id);

    $('#' + elem_id).fadeOut('slow');
    
    $.get("index.php",
            {action: "ajax",
             ajax: 'config',
             ajaxaction: "delete_stop_word",
             stopword_id: info.id,
             stopwords_lang: info.lang},
            function (){
                 loadStopWordsByLang(info.lang)
            }
        );
}

function addStopWordInputElem()
{
    var word = prompt('<?php print $PMF_LANG["ad_config_stopword_input"]?>', '');
    var lang = $('#stopwords_lang_selector').val();
    
    if(!!word) {
       $.get("index.php",
       {action: "ajax",
        ajax: 'config',
        ajaxaction: "save_stop_word",
        stopword: word,
        stopwords_lang: lang},
        function (){
            loadStopWordsByLang(lang)
       }
       );
    }
} 
/* ]]> */
</script>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}
