<?php
/**
* $Id: menue.php,v 1.4 2005-09-25 09:47:02 thorstenr Exp $
*
* Navigation menue of the admin area
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-26
* @copyright    (c) 2001-2005 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
<div id="menue">
    <ul>
        <li><a href="<?php print $linkext; ?>"><?php print $PMF_LANG["ad_menu_startpage"]; ?></a></li>
<?php
if ($permission["adduser"] || $permission["edituser"] || $permission["deluser"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=user"><?php print $PMF_LANG["ad_menu_user_administration"]; ?></a></li>
<?php
}
if ($permission["addcateg"] || $permission["editcateg"] || $permission["delcateg"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=category"><?php print $PMF_LANG["ad_menu_categ_edit"]; ?></a></li>
<?php
}
if ($permission["addbt"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=editentry"><?php print $PMF_LANG["ad_entry_add"]; ?></a></li>
<?php
}
if ($permission["editbt"] || $permission["delbt"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=accept"><?php print $PMF_LANG["ad_menu_entry_aprove"]; ?></a></li>
<?php
}
if ($permission["editbt"] || $permission["delbt"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=view"><?php print $PMF_LANG["ad_menu_entry_edit"]; ?></a></li>
<?php
}
if ($permission['addglossary'] || $permission['editglossary'] || $permission['delglossary']) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=glossary"><?php print $PMF_LANG['ad_menu_glossary']; ?></a></li>
<?php
}
if ($permission["addnews"] || $permission["editnews"] || $permission["delnews"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=news&amp;do=edit"><?php print $PMF_LANG["ad_menu_news_edit"]; ?></a></li>
<?php
}
if ($permission["delquestion"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=question"><?php print $PMF_LANG["ad_menu_open"]; ?></a></li>
<?php
}
if ($permission["viewlog"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=statistik"><?php print $PMF_LANG["ad_menu_stat"]; ?></a></li>
<?php
}
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=cookies"><?php print $PMF_LANG["ad_menu_cookie"]; ?></a></li>
<?php
if ($permission["viewlog"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=viewsessions"><?php print $PMF_LANG["ad_menu_session"]; ?></a></li>
<?php
}
if ($permission["adminlog"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=adminlog"><?php print $PMF_LANG["ad_menu_adminlog"]; ?></a></li>
<?php
}
if ($permission["passwd"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=passwd"><?php print $PMF_LANG["ad_menu_passwd"]; ?></a></li>
<?php
}
if ($permission["editconfig"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=editconfig"><?php print $PMF_LANG["ad_menu_editconfig"]; ?></a></li>
<?php
}
if ($permission["backup"] || $permission["restore"]) {
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=csv"><?php print $PMF_LANG["ad_menu_backup"]; ?></a></li>
<?php
}
?>
        <li><a href="<?php print $linkext; ?>&amp;aktion=export"><?php print $PMF_LANG["ad_menu_export"]; ?></a></li>
        <li><a href="<?php print $linkext; ?>&amp;aktion=logout"><?php print $PMF_LANG["ad_menu_logout"]; ?></a></li>
    </ul>
</div>