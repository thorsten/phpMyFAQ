/**
 * Some JavaScript functions used in the admin backend
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
 * @package   JavaScript
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Periklis Tsirakidis <tsirakidis@phpdevel.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-11-13
 */

/**
 * 
 * @param pic
 * @param title
 * @param width
 * @param height
 */
function addAttachment(pic,title,width,height)
{
    popup = window.open(pic, title, 'width='+width+', height='+height+', toolbar=no, directories=no, status=no, scrollbars=no, resizable=yes, menubar=no');
    popup.focus();
}

/**
 * 
 * @param checkBox
 */
function checkAll(checkBox)
{
    var v = checkBox.checked;
    var f = checkBox.form;
    for (var i = 0; i < f.elements.length; i++) {
        if (f.elements[i].type == "checkbox") {
            f.elements[i].checked = v;
            }
        }
}

/**
 * 
 * @param uri
 * @param name
 * @param ext
 * @param cat
 */
function addEngine(uri, name, ext, cat)
{
    if ((typeof window.sidebar == "object") && (typeof window.sidebar.addSearchEngine == "function")) {
        window.sidebar.addSearchEngine(uri+"/"+name+".src", uri+"/images/"+name+"."+ext, name, cat);
    } else {
        alert('Mozilla Firefox, Mozilla or Netscape 6 or later is needed to install the search plugin!');
    }
}

/**
 * Displays or hides a div block
 *
 * @param  string id Id of the block
 * @return void
 */
function showhideCategory(id)
{
    if ($('#' + id).css('display') == 'none') {
        $('#' + id).fadeIn("slow");
    } else {
        $('#' + id).fadeOut("slow");
    }
}


/**
 * Displays or hides the login form
 *
 * @return void
 */
function loginForm()
{
    if ($('#loginForm').hasClass('hidden')) {
        $('#loginForm').removeClass('hidden');
        $('#loginForm').fadeIn();
    } else {
        $('#loginForm').fadeOut();
        $('#loginForm').addClass('hidden');
    }
}

 
/**
 * Displays or hides a configuration block
 * 
 * @param  string container
 * @return void
 */
function toggleConfig(container)
{
    if ($('#config' + container).css('display') == 'none') {
    	$('#config' + container).fadeIn("slow");
    } else {
        $('#config' + container).fadeOut("slow");
    }
}

/**
 * deletes all options from given select-object.
 *
 * @param select
 * @return void
 */
function select_clear(select)
{
    while (select.length > 0) {
        select.remove(0);
    }
}

/**
* adds an option to the given select-object.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param select node
* @param string
* @param text node
* @param string
* @return void
*/
function select_addOption(select, value, content, classValue)
{
    var opt;
    opt = document.createElement("option");
    opt.value = value;
    if (classValue) {
        opt.className = classValue;
    }
    opt.appendChild(content);
    select.appendChild(opt);
}

/**
* selects all list options in the select with the given ID.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param string
* @return void
*/
function select_selectAll(select_id)
{
    var select_options = document.getElementById(select_id).options;
    for (var i = 0; i < select_options.length; i++) {
        select_options[i].selected = true;
    }
}

/**
* unselects all list options in the select with the given ID.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param string
* @return void
*/
function select_unselectAll(select_id)
{
    var select_options = document.getElementById(select_id).options;
    for (var i = 0; i < select_options.length; i++) {
        select_options[i].selected = false;
    }
}

/**
 * checks all checkboxes in form with the given ID.
 *
 * @access  public
 * @author  Lars Tiedemann, <php@larstiedemann.de>
 * @param   string
 * @return  void
 */
function form_checkAll(form_id)
{
    var inputElements = document.getElementById(form_id).getElementsByTagName('input');
    for (var i = 0, ele; ele = inputElements[i]; i++) {
        if (ele.type == "checkbox") {
            ele.checked = true;
        }
    }
}

/**
 * unchecks all checkboxes in form with the given ID.
 *
 * @access  public
 * @author  Lars Tiedemann, <php@larstiedemann.de>
 * @param   string
 * @return  void
 */
function form_uncheckAll(form_id)
{
    var inputElements = document.getElementById(form_id).getElementsByTagName('input');
    for (var i = 0, ele; ele = inputElements[i]; i++) {
        if (ele.type == "checkbox") {
            ele.checked = false;
        }
    }
}

/**
* returns the text content of a child element.
*
* When having a dom structure like this:
* <item id="1">
*   <name>Item Name</name>
*   <value>Text Value</value>
* </item>
* text_getFromParent(document.getElementById(1), "name")
* would return "Item Name".
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param Object select
* @return void
*/
function text_getFromParent(parentObject, childElement)
{
    var result = "";
    result = parentObject.getElementsByTagName(childElement)[0];
    if (result) {
        if (result.childNodes.length > 1) {
            return result.childNodes[1].nodeValue;
        } else {
            if (result.firstChild) {
                return result.firstChild.nodeValue;
            } else {
                return "";
            }
        }
    } else {
        return "n/a";
    }
}

/**
* deletes all rows from given table-object.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param table
* @return void
*/
function table_clear(table)
{
    while (table.rows.length > 0) {
        table.deleteRow(0);
    }
}

/**
* inserts a new row into the given table at the given position.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param table
* @param int
* @param node
* @param node
* @return void
*/
function table_addRow(table, rowNumber, col1, col2)
{
    var td1;
    var td2;
    var tr;
    td1 = document.createElement("td");
    td1.appendChild(col1);
    td2 = document.createElement("td");
    td2.appendChild(col2);
    tr = table.insertRow(rowNumber);
    tr.appendChild(td1);
    tr.appendChild(td2);
}

/**
 * Displays or hides the info boxes
 *
 * @return void
 */
function infoBox(infobox_id)
{
    if ($('#' + infobox_id).css('display') == 'none') {
        $('.faqTabContent').hide();
        $('#' + infobox_id).show();
    } else {
        $('#' + infobox_id).hide();
    }
}

/**
 * Saves all content from the given form via Ajax
 *
 * @param string action   Actions: savecomment, savefaq, savequestion,
 *                        saveregistration, savevoting, sendcontact,
 *                        sendtofriends
 * @param string formName Name of the current form
 * 
 * @return void
 */
function saveFormValues(action, formName)
{
    var formValues = $('#formValues');

    $('#loader').show();
    $('#loader').fadeIn(400).html('<img src="images/ajax-loader.gif" />Saving ...');

    $.ajax({
        type:     'post',
        url:      'ajaxservice.php?action=' + action,
        data:     formValues.serialize(),
        dataType: 'json',
        cache:    false,
        success:  function(json) {
            if (json.success == undefined) {
                $('#' + formName + 's').html('<p class="error">' + json.error + '</p>');
                $('#loader').hide();
            } else {
                $('#' + formName + 's').html('<p class="success">' + json.success + '</p>');
                $('#' + formName + 's').fadeIn("slow");
                $('#loader').hide();
                $('#' + formName + 'Form').hide();
                $('#formValues')[0].reset();
                // @todo add reload of content
            }
        }
    });
    
    return false;
}

/**
 * Auto-suggest function for instant response
 *
 * @return void
 */
function autoSuggest()
{
    $('input#instantfield').keyup(function()
    {
        var search   = $('#instantfield').val();
        var language = $('#ajaxlanguage').val();
        var category = $('#searchcategory').val();

        if (search.length > 0) {
            $.ajax({
                type:    "POST",
                url:     "ajaxresponse.php",
                data:    "search=" + search + "&ajaxlanguage=" + language + "&searchcategory=" + category,
                success: function(searchresults)
                {
                    $("#instantresponse").empty();
                    if (searchresults.length > 0)  {
                        $("#instantresponse").append(searchresults);
                    }
                }
            });
        }
    });

    $('#instantform').submit(function()
    {
        return false;
    });
}

/**
 * Saves the voting by Ajax
 * 
 * @param type
 * @param id
 * @param value
 */
function saveVoting(type, id, value)
{
    $.ajax({
        type:     'post',
        url:      'ajaxservice.php?action=savevoting',
        data:     'type=' + type + '&id=' + id + '&vote=' + value,
        dataType: 'json',
        cache:    false,
        success:  function(json) {
            if (json.success == undefined) {
                $('#votings').html('<p class="error">' + json.error + '</p>');
                $('#loader').hide();
            } else {
                $('#votings').html('<p class="success">' + json.success + '</p>');
                $('#votings').fadeIn("slow");
                $('#loader').hide();
                $('#votingForm').hide();
            }
        }
    });

    return false;
}

/**
 * Checks the content of a question by Ajax
 *
 * @param type
 * @param id
 * @param value
 */
function checkQuestion()
{
    var formValues = $('#formValues');

    $('#loader').show();
    $('#loader').fadeIn(400).html('<img src="images/ajax-loader.gif" />Saving ...');

    $.ajax({
        type:     'post',
        url:      'ajaxservice.php?action=savequestion',
        data:     formValues.serialize(),
        dataType: 'json',
        cache:    false,
        success:  function(json) {
            if (json.result == undefined && json.success == undefined) {
                $('#qerror').html('<p class="error">' + json.error + '</p>');
                $('#loader').hide();
            } else if (json.success == undefined) {
                $('#qerror').empty();
                $('#questionForm').fadeOut('slow');
                $('#answerForm').html(json.result);
                $('#answerForm').fadeIn("slow");
                $('#loader').hide();
                $('#formValues').append('<input type="hidden" name="save" value="1" />');
                $('#captcha').val('');
                refreshCaptcha('ask');
            } else {
                $('#answers').html('<p class="success">' + json.success + '</p>');
                $('#answers').fadeIn("slow");
                $('#answerForm').fadeOut('slow');
                $('#loader').hide();
                $('#formValues').hide();
            }
        }
    });

    return false;
}

/**
 * Refreshes a captcha image
 * 
 * @param string action
 */
function refreshCaptcha(action)
{
    $.ajax({
        url: 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime(),
        success: function(result) {
            $("#captchaImage").attr('src', 'index.php?action=' + action + '&gen=img&ck=' + new Date().getTime());
            $("#captcha").val('');
        }
    });
}

/**
 * Toggle fieldsets
 *
 * @param string fieldset ID of the fieldset
 *
 * @return void
 */
function toggleFieldset(fieldset)
{
    if ($('#div_' + fieldset).css('display') == 'none') {
        $('#div_' + fieldset).fadeIn('fast');
    } else {
        $('#div_' + fieldset).fadeOut('fast');
    }
}

/**
 * Adds the link to the attachment in the main FAQ window
 * @param integer attachmentId
 * @param string
 */
function addAttachmentLink(attachmentId, fileName)
{
    window.opener.
        $('.adminAttachments').
        append('<li><a href="../index.php?action=attachment&id=' + attachmentId +'">' + fileName + '</a></li>');
    window.close();
}

/**
 * Closes the current window
 *
 */
function closeWindow()
{
    window.close();
}