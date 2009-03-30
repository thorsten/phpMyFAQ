<?php
/**
 * Frontend for search log statistics
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-03-30
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

if ($permission['viewlog']) {
	
	
	
} else {
    print $PMF_LANG["err_NotAuth"];
}