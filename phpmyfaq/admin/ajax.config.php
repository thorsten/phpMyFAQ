<?php
/**
 * AJAX: handling of Ajax configuration calls
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2009-04-01
 * @copyright 2009 phpMyFAQ Team
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
            $stopwordsList = PMF_Stopwords::getInstance()->getByLang($stopwordsLang);
            
            header('Content-Type: application/json');
            print json_encode($stopwordsList);
        }
        break;
        
    case 'delete_stop_word':
        if (null != $stopwordId && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $oStopwords = PMF_Stopwords::getInstance();
            $oStopwords->setLanguage($stopwordsLang);
            $oStopwords->remove($stopwordId);
        }
        break;
        
    case 'save_stop_word':
        if (null != $stopword && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $oStopwords = PMF_Stopwords::getInstance();
            $oStopwords->setLanguage($stopwordsLang);
            if (null !== $stopwordId && -1 < $stopwordId) {
                $oStopwords->update($stopwordId, $stopword);
            } elseif (!$oStopwords->match($stopword)){
                $oStopwords->add($stopword);
            }
        }
        break;
}
