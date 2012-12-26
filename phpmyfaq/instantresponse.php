<?php
/**
 * The Ajax powered instant response page.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2007-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('instantresponse', 0);

$searchString = $printInstantResponse = '';

$tpl->parse(
    'writeContent',
    array(
        'msgInstantResponse'            => $PMF_LANG['msgInstantResponse'],
        'msgDescriptionInstantResponse' => $PMF_LANG['msgDescriptionInstantResponse'],
        'searchString'                  => $searchString,
        'writeSendAdress'               => '?'.$sids.'action=instantresponse',
        'ajaxlanguage'                  => $LANGCODE,
        'printInstantResponse'          => $printInstantResponse
    )
);

$tpl->merge('writeContent', 'index');
