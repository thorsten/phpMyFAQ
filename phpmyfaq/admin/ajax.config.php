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
$instanceId    = PMF_Filter::filterInput(INPUT_GET, 'instanceId', FILTER_VALIDATE_INT);
$stopwordId    = PMF_Filter::filterInput(INPUT_GET, 'stopword_id', FILTER_VALIDATE_INT);
$stopword      = PMF_Filter::filterInput(INPUT_GET, 'stopword', FILTER_SANITIZE_STRING);
$stopwordsLang = PMF_Filter::filterInput(INPUT_GET, 'stopwords_lang', FILTER_SANITIZE_STRING);

$http = new PMF_Helper_Http();

switch ($ajaxAction) {

    case 'add_instance':

        $url      = PMF_Filter::filterInput(INPUT_GET, 'url', FILTER_SANITIZE_STRING);
        $instance = PMF_Filter::filterInput(INPUT_GET, 'instance', FILTER_SANITIZE_STRING);
        $comment  = PMF_Filter::filterInput(INPUT_GET, 'comment', FILTER_SANITIZE_STRING);
        $install  = PMF_Filter::filterInput(INPUT_GET, 'install', FILTER_SANITIZE_STRING);

        $data = array(
            'url'      => $url,
            'instance' => $instance,
            'comment'  => $comment
        );

        $faqInstance = new PMF_Instance($faqConfig);
        $instanceId = $faqInstance->addInstance($data);

        $faqInstanceClient = new PMF_Instance_Client($faqConfig);
        $faqInstanceClient->createClient($faqInstance);

        if ('yes' === $install) {
            // @todo
            // - create new folder for client
            // - copy /config folder stuff
            // - create /images folder
            // - copy /template stuff
        }

        if (0 !== $instanceId) {
            $payload = array('added' => $instanceId);
        } else {
            $payload = array('error' => $instanceId);
        }
        $http->sendJsonWithHeaders($payload);
        break;

    case 'delete_instance':
        if (null !== $instanceId) {
            $faqInstance = new PMF_Instance($faqConfig);
            if ($faqInstance->removeInstance($instanceId)) {
                $payload = array('deleted' => $instanceId);
            } else {
                $payload = array('error' => $instanceId);
            }
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'edit_instance':
        if (null !== $instanceId) {
            $faqInstance = new PMF_Instance($faqConfig);
            if ($faqInstance->removeInstance($instanceId)) {
                $payload = array('deleted' => $instanceId);
            } else {
                $payload = array('error' => $instanceId);
            }
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'load_stop_words_by_lang':
        if (PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $stopwordsList = PMF_Stopwords::getInstance($faqConfig)->getByLang($stopwordsLang);

            $payload = $stopwordsList;
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'delete_stop_word':
        if (null != $stopwordId && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $oStopwords = PMF_Stopwords::getInstance($faqConfig);
            $oStopwords->setLanguage($stopwordsLang);
            $oStopwords->remove($stopwordId);
        }
        break;

    case 'save_stop_word':
        if (null != $stopword && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $oStopwords = PMF_Stopwords::getInstance($faqConfig);
            $oStopwords->setLanguage($stopwordsLang);
            if (null !== $stopwordId && -1 < $stopwordId) {
                $oStopwords->update($stopwordId, $stopword);
            } elseif (!$oStopwords->match($stopword)){
                $oStopwords->add($stopword);
            }
        }
        break;
}
