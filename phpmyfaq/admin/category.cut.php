<?php
/******************************************************************************
 * File:				category.cut.php
 * Description:			cuts a category
 * Author:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-12-25
 * Last change:			2004-06-18
 * Copyright:           (c) 2001-2004 Thorsten Rinne
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
 ******************************************************************************/

if ($permission["editcateg"]) {
    print "<h2>".$PMF_LANG["ad_categ_updatecateg"]."</h2>\n";
    $category = $_REQUEST["cat"];
    
    $cat = new category;
    $cat->buildTree();
    
    foreach ($cat->catTree as $cat) {
        $indent = "";
        for ($i = 0; $i < $cat["indent"]; $i++) {
            $indent .= "&nbsp;&nbsp;&nbsp;";
            }
        print $indent."<strong style=\"vertical-align: top;\">&middot; ".$cat["name"]."</strong> ";
        print "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=pastecategory&amp;cat=".$category."&amp;after=".$cat["id"]."\" title=\"".$PMF_LANG["ad_categ_paste"]."\"><img src=\"images/paste.gif\" width=\"16\" height=\"16\" alt=\"".$PMF_LANG["ad_categ_paste"]."\" border=\"0\" title=\"".$PMF_LANG["ad_categ_paste"]."\" /></a>\n";
        print "<br />";
        }
    print $PMF_LANG["ad_categ_new_main_cat"]." <a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=pastecategory&amp;cat=".$category."&amp;after=0\" title=\"".$PMF_LANG["ad_categ_paste"]."\"><img src=\"images/paste.gif\" width=\"16\" height=\"16\" alt=\"".$PMF_LANG["ad_categ_paste"]."\" border=\"0\" title=\"".$PMF_LANG["ad_categ_paste"]."\" /></a>\n";
    }
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>