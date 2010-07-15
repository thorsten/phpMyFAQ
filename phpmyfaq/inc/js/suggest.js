/**
 * Instant Response / Suggest code
 *
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-12-04
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

$('#instantfield').keyup(function() 
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
