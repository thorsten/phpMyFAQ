<?php
/**
 * $Id: microsummary.php,v 1.1 2006-09-05 20:22:18 thorstenr Exp $
 *
 * Microsummary backend
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since       2006-09-05
 * @copyright   (c) 2006 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}