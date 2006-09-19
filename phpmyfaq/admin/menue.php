<?php
/**
* $Id: menue.php,v 1.18 2006-09-19 21:28:33 matteo Exp $
*
* Navigation menue of the admin area
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Minoru TODA <todam@netjapan.co.jp>
* @since        2003-02-26
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>


<div id="langform">
    <form action="<?php print $linkext; ?>" method="post">
    <label for="language"><?php print $PMF_LANG['msgLangaugeSubmit']; ?></label>
    <?php print selectLanguages($LANGCODE); ?><br />
    <input type="submit" name="submit" value="Go" class="submit" />
    </form>
</div>

<div id="sessionexpiration">
    <label for="session">Time to your session expiration</label>
    <div id="sessioncounter">Loading...</div>
</div>
