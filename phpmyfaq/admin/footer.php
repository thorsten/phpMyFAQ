<?php
/**
* $Id: footer.php,v 1.7 2006-06-15 21:41:57 matteo Exp $
*
* Footer of the admin area
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
</div>

<!-- Footer -->
<div>
    <div id="footer">
        <div id="copyright"><strong>phpMyFAQ <?php print $PMF_CONF["version"]; ?></strong> | &copy; 2001-2006 <a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a></div>
    </div>
</div>

<iframe id="keepPMFSessionAlive" src="session.keepalive.php<?php print $linkext; ?>&amp;lang=<?php print $LANGCODE; ?>" frameBorder="no" width="0" height="0"></iframe>

</body>
</html>
