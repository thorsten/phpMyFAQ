<?php
/**
 * AJAX: handling of Ajax configuration calls
 * 
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-04-01
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);


switch($ajax_action) {
    
    case 'load_stop_words_by_lang':
        $stopwords_lang = PMF_Filter::filterInput(INPUT_GET, 'stopwords_lang', FILTER_SANITIZE_STRING);
        
        $stop_words_list = PMF_Stopwords::getInstance()->getByLang($stopwords_lang);
        
        header('Content-Type: application/json');
        print json_encode($stop_words_list);
        
        break;
        
    case 'delete_stop_word':
        
        break;
        
    case 'save_stop_word':
        
        $stopword_id = PMF_Filter::filterInput(INPUT_GET, 'stopword_id', FILTER_VALIDATE_INT);
        $stopword = PMF_Filter::filterInput(INPUT_GET, 'stopword', FILTER_SANITIZE_STRING);
        $stopword_lang = PMF_Filter::filterInput(INPUT_GET, 'stopword_lang', FILTER_SANITIZE_STRING);
        
        if(null != $stopword) {
            $pmf_sw = PMF_Stopwords::getInstance();
            $pmf_sw->setLanguage($stopword_lang);
            if($pmf_sw->match($stopword) && null != $stopword_id) {
                $pmf_sw->update($stopword_id, $stopword);
            } else {
                $pmf_sw->add($stopword);
            }
        }
        
        break;
}
