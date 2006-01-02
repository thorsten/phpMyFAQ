<?php
/**
* $Id: footer.php,v 1.5 2006-01-02 16:51:26 thorstenr Exp $
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
        <div><a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ</a> <?php print $PMF_CONF["version"]; ?> | &copy; 2001-2005 <a href="http://www.phpmyfaq.de/impressum.php" target="_blank">phpMyFAQ Team</a></div>
    </div>
</div>

</body>
</html>
