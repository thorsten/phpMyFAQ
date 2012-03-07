<?php
/**
 * AJAX: handling of Ajax configuration calls
 *
 * PHP 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-01
 */

if (!defined('IS_VALID_PHPMYFAQ') || !$permission['editconfig']) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction    = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$stopwordId    = PMF_Filter::filterInput(INPUT_GET, 'stopword_id', FILTER_VALIDATE_INT);
$stopword      = PMF_Filter::filterInput(INPUT_GET, 'stopword', FILTER_SANITIZE_STRING);
$stopwordsLang = PMF_Filter::filterInput(INPUT_GET, 'stopwords_lang', FILTER_SANITIZE_STRING);

switch ($ajaxAction) {
    
    case 'load_stop_words_by_lang':
        if (PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $stopwordsList = PMF_Stopwords::getInstance($db, $Language)->getByLang($stopwordsLang);
            
            header('Content-Type: application/json');
            print json_encode($stopwordsList);
        }
        break;
        
    case 'delete_stop_word':
        if (null != $stopwordId && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $oStopwords = PMF_Stopwords::getInstance($db, $Language);
            $oStopwords->setLanguage($stopwordsLang);
            $oStopwords->remove($stopwordId);
        }
        break;
        
    case 'save_stop_word':
        if (null != $stopword && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $oStopwords = PMF_Stopwords::getInstance($db, $Language);
            $oStopwords->setLanguage($stopwordsLang);
            if (null !== $stopwordId && -1 < $stopwordId) {
                $oStopwords->update($stopwordId, $stopword);
            } elseif (!$oStopwords->match($stopword)){
                $oStopwords->add($stopword);
            }
        }
        break;
}
