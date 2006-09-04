/**
* $Id: functions.js,v 1.3 2006-09-04 16:59:12 matteo Exp $
*
* Some JavaScript functions used in the admin backend
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Periklis Tsirakidis <tsirakidis@phpdevel.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @author       Minoru TODA <todam@netjapan.co.jp>
* @since        2003-11-13
* @copyright    (c) 2001-2006 phpMyFAQ Team
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

function Picture(pic,title,width,height)
{
    popup = window.open(pic, title, 'width='+width+', height='+height+', toolbar=no, directories=no, status=no, scrollbars=no, resizable=yes, menubar=no');
    popup.focus();
}

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

function getxmlhttp()
{
    var xmlhttp = false;

    // Try to get XMLHTTP in Microsoft environment
    try {
        xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (e) {
            xmlhttp = false;
        }
    }

    // Try to get standard XML HTTP
    if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
        xmlhttp = new XMLHttpRequest();
    }

    return xmlhttp;
}

function addEngine(uri, name, ext, cat)
{
    if ((typeof window.sidebar == "object") && (typeof window.sidebar.addSearchEngine == "function")) {
        window.sidebar.addSearchEngine(uri+"/"+name+".src", uri+"/images/"+name+"."+ext, name, cat);
    } else {
        alert('Mozilla Firefox, Mozilla or Netscape 6 or later is needed to install the search plugin!');
    }
}

function focusOnUsernameField()
{
    if (document.getElementById('faqusername')) {
        document.getElementById('faqusername').focus();
    }
}

function focusOnSearchField()
{
    if (document.getElementById('searchfield')) {
        document.getElementById('searchfield').focus();
    }
}

/**
 * showhideCategory()
 *
 * Displays or hides a div block
 *
 * @param    string
 * @return   void
 * @access   public
 * @since    2006-03-04
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function showhideCategory(id)
{
    if (document.getElementById(id).style.display == 'none') {
        document.getElementById(id).style.display = 'block';
    } else {
        document.getElementById(id).style.display = 'none';
    }
}

/**
* deletes all options from given select-object.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
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
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param string
* @return void
*/
function form_checkAll(form_id)
{
    var inputElements = document.getElementById(form_id).getElementsByTagName('input');
    for (var i = 0; i < inputElements.length; i++) {
        var type = inputElements[i].getAttribute('type');
        if ((type == "checkbox" || type == "CHECKBOX")) {
            inputElements[i].setAttribute('checked', "checked");
        }
    }
}

/**
* unchecks all checkboxes in form with the given ID.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @param string
* @return void
*/
function form_uncheckAll(form_id)
{
    var inputElements = document.getElementById(form_id).getElementsByTagName('input');
    for (var i = 0; i < inputElements.length; i++) {
        var type = inputElements[i].getAttribute('type');
        if ((type == "checkbox" || type == "CHECKBOX") && inputElements[i].getAttribute('checked')) {
            inputElements[i].removeAttributeNode(inputElements[i].getAttributeNode('checked'));
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
* Function to get a pretty formatted output of a variable
*
* NOTE: Just for debugging!
*
* @param    string
* @return   void
* @access   public
* @since    2005-12-26
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function pmf_dump(data)
{
    var s = '';
    for(idx in data){
        s += idx + '('+typeof data[idx]+'): ' + data[idx]+'\n';
    }
    document.write('<pre>' + s + '</pre>');
}

/**
* Hide a <div> container
*
* @param    string
* @return   void
* @access   public
* @since    2006-01-07
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function hide(id)
{
    document.getElementById(id).style.display = 'hidden'; 
}

/**
* Show a <div> container
*
* @param    string
* @return   void
* @access   public
* @since    2006-01-07
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function show(id)
{
    document.getElementById(id).style.display = 'inline'; 
}
