<?php
/**
 * The main stop words configuration frontend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig')) {
    printf(
        '<header class="row"><div class="col-lg-12"><h2 class="page-header"><i aria-hidden="true" class="fa fa-wrench fa-fw"></i> %s</h2></div></header>',
        $PMF_LANG['ad_menu_stopwordsconfig']
    );

    $sortedLanguageCodes = $languageCodes;
    asort($sortedLanguageCodes);
    reset($sortedLanguageCodes);
    ?>
    <div class="row">
        <div class="col-lg-12">

            <p>
                <?php echo $PMF_LANG['ad_stopwords_desc'] ?>
            </p>
            <p>
                <select onchange="loadStopWordsByLang(this.options[this.selectedIndex].value)"
                        id="stopwords_lang_selector">
                <option value="none">---</option>
    <?php foreach ($sortedLanguageCodes as $key => $value) {
    ?>
        <option value="<?php echo strtolower($key);
    ?>"><?php echo $value;
    ?></option>
    <?php 
}
    ?>
                </select>
                <span id="stopwords_loading_indicator"></span>
            </p>

            <div id="stopwords_content"></div>

            <script>

        /**
         * column count in the stop words table
         */
        var max_cols = 4;


        /**
         * Load stop words by language, build html and put
         * it into stopwords_content container
         *
         * @param lang language to retrieve the stopwords by
         *
         * @return void
         */
        function loadStopWordsByLang(lang)
        {
            if('none' == lang) {
                return;
            }

            $('#stopwords_loading_indicator').html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i>');

            $.get("index.php",
                  {action: "ajax", ajax: 'config', ajaxaction: "load_stop_words_by_lang", stopwords_lang: lang},
                  function (data, textStatus) {
                      $('#stopwords_content').html(buildStopWordsHTML(data));
                      $('#stopwords_loading_indicator').html('');
                  },
                  'json'
            );
        }


        /**
         * Build complete html contents to view and edit stop words
         *
         * @param data Supposed is stop words json data
         *
         * @return string
         */
        function buildStopWordsHTML(data)
        {
            if('object' != typeof(data)) {
                return '';
            }

            var html = '<table class="list">';
            var elem_id;
            for(var i = 0; i < data.length; i++) {

                if(i % max_cols == 0) {
                    html += '<tr id="stopwords_group_' + i + '">';
                }

                /**
                 * id atribute is of the format stopword_<id>_<lang>
                 */
                elem_id = buildStopWordInputElemId(data[i].id, data[i].lang);

                html += '<td>';
                html += buildStopWordInputElement(elem_id, data[i].stopword);
                html += '</td>';

                if(i % max_cols == max_cols - 1) {
                    html += '</tr>';
                }
            }

            html += '</table>';
            html += '<a class="btn btn-primary" href="javascript: addStopWordInputElem();"><i aria-hidden="true" class="fa fa-add fa fa-white"></i> <?php echo $PMF_LANG['ad_config_stopword_input'] ?></a>';

            return html;
        }


        /**
         * Build an input element to view and edit stop word
         *
         * @param elem_id id of the html element
         * @param stopword
         *
         * @return string
         */
        function buildStopWordInputElement(elem_id, stopword)
        {
            elem_id = elem_id || buildStopWordInputElemId();
            stopword = stopword || '';
            var attrs = 'onblur="saveStopWord(this.id)" onkeydown="saveStopWordHandleEnter(this.id, event)" onfocus="saveOldValue(this.id)"';
            var element = '<input class="form-control" id="' + elem_id + '" value="' + stopword + '" ' + attrs + '>';

            return element;
        }

        /**
         * Id atribute is of the format stopword_<id>_<lang>
         *
         * @param id database id of the word
         * @param lang
         *
         * @return string
         */
        function buildStopWordInputElemId(id, lang)
        {
            id = id || -1;
            lang = lang || $('#stopwords_lang_selector').val();

            return 'stopword_' + id + '_' + lang;
        }

        /**
         * Parse the stopword element id and return a clean object
         *
         * @param elem_id input element id
         *
         * @return object
         */
        function parseStopWordInputElemId(elem_id)
        {
            var info = elem_id.split('_');

            return {id: info[1], lang: info[2]};
        }

        /**
         * Handle enter press on a stop word input element
         *
         * @param elem_id input element id
         * @param e event
         *
         * @return void
         */
        function saveStopWordHandleEnter(elem_id, e)
        {
            e = e || window.event || undefined;

            if(undefined != e) {
                var key = e.charCode || e.keyCode || 0;
                if(13 == key) {
                    if('' == $('#' + elem_id).val()) {
                        deleteStopWord(elem_id);
                    } else {
                        // this blur action will cause saveStopWord() call
                        $('#' + elem_id).blur();
                    }
                }
            }
        }

        /**
         * Save stopword doing an ajax call
         *
         * @param elem_id input element id
         *
         * @return void
         */
        function saveStopWord(elem_id)
        {
            var info =  parseStopWordInputElemId(elem_id);

            if ($('#' + elem_id).attr('old_value') !== $('#' + elem_id).val()) {
                $.get("index.php", {
                    action: "ajax",
                    ajax: 'config',
                    ajaxaction: "save_stop_word",
                    stopword_id: info.id,
                    stopword: $('#' + elem_id).val(),
                    stopwords_lang: info.lang,
                    csrf: '<?php echo $user->getCsrfTokenFromSession();
    ?>'
                    }
                );
            } else {
                if (0 > info.id && '' == $('#' + elem_id).val()) {
                    $('#' + elem_id).remove();
                    return;
                }
            }
        }

        /**
         * Save the value of the stop word input element.
         * This is bound on onfocus.
         *
         * @param elem_id input element id
         *
         * @return void
         */
        function saveOldValue(elem_id)
        {
            $('#' + elem_id).attr('old_value', $('#' + elem_id).val());
        }


        /**
         * Handle stop word delete doing an ajax request.
         *
         * @param elem_id input element id
         *
         * @return void
         */
        function deleteStopWord(elem_id)
        {
            var info = parseStopWordInputElemId(elem_id);

            $('#' + elem_id).fadeOut('slow');

            $.get("index.php", {
                    action: "ajax",
                    ajax: 'config',
                    ajaxaction: "delete_stop_word",
                    stopword_id: info.id,
                    stopwords_lang: info.lang,
                    csrf: '<?php echo $user->getCsrfTokenFromSession();
    ?>'
                },
                function () {
                    loadStopWordsByLang(info.lang)
                }
            );
        }

        /**
         * Handle stop word add prompting for a new word and doing an ajax request.
         *
         * @return void
         */
        function addStopWordInputElem()
        {
            var word = prompt('<?php echo $PMF_LANG['ad_config_stopword_input']?>', '');
            var lang = $('#stopwords_lang_selector').val();

            if (!!word) {
                $.get("index.php", {
                        action: "ajax",
                        ajax: 'config',
                        ajaxaction: "save_stop_word",
                        stopword: word,
                        stopwords_lang: lang,
                        csrf: '<?php echo $user->getCsrfTokenFromSession();
    ?>'
                },
                function () {
                    loadStopWordsByLang(lang)
                }
                );
            }
        }
        </script>
        </div>
    </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
