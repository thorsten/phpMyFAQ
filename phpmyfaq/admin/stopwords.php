<?php

/**
 * The main stop words configuration frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-04-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-wrench"></i>
        <?= $PMF_LANG['ad_menu_stopwordsconfig'] ?>
    </h1>
  </div>

<?php
if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $sortedLanguageCodes = $languageCodes;
    asort($sortedLanguageCodes);
    reset($sortedLanguageCodes);
    ?>
  <div class="row">
    <div class="col-lg-12">

      <p>
        <?= $PMF_LANG['ad_stopwords_desc'] ?>
      </p>
      <p>
        <label for="stopwords_lang_selector"><?= $PMF_LANG['ad_entry_locale'] ?>:</label>
        <select onchange="loadStopWordsByLang(this.options[this.selectedIndex].value)"
                id="stopwords_lang_selector">
          <option value="none">---</option>
            <?php foreach ($sortedLanguageCodes as $key => $value) { ?>
              <option value="<?= strtolower($key) ?>"><?= $value ?></option>
            <?php } ?>
        </select>
        <span id="stopwords_loading_indicator"></span>
      </p>

      <div class="mb-3" id="stopwords_content"></div>

      <script>

        /**
         * column count in the stop words table
         */
        const maxCols = 4;

        /**
         * Load stop words by language, build html and put
         * it into stop words_content container
         *
         * @param lang language to retrieve the stop words by
         * @return void
         */
        function loadStopWordsByLang(lang) {
          if ('none' === lang) {
            return;
          }

          $('#stopwords_loading_indicator').html('<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Loading...</span>');

          $.get('index.php',
            { action: 'ajax', ajax: 'config', ajaxaction: 'load_stop_words_by_lang', stopwords_lang: lang },
            (data) => {
              $('#stopwords_content').html(buildStopWordsHTML(data));
              $('#stopwords_loading_indicator').html('<i class="fa fa-spell-check" aria-hidden="true"></i>');
            },
            'json',
          );
        }

        /**
         * Build complete html contents to view and edit stop words
         *
         * @param data Supposed is stop words json data
         *
         * @return string
         */
        function buildStopWordsHTML(data) {
          if ('object' != typeof (data)) {
            return '';
          }

          let html = '<table class="table table-hover">';
          let elem_id;
          for (let i = 0; i < data.length; i++) {

            if (i % maxCols === 0) {
              html += '<tr id="stopwords_group_' + i + '">';
            }

            // id attribute is of the format stopword_<id>_<lang>
            elem_id = buildStopWordInputElemId(data[i].id, escape(data[i].lang));

            html += '<td>';
            html += buildStopWordInputElement(elem_id, escape(data[i].stopword));
            html += '</td>';

            if (i % maxCols === maxCols - 1) {
              html += '</tr>';
            }
          }

          html += '</table>';
          html += '<a class="btn btn-primary" href="javascript: addStopWordInputElem();"><i aria-hidden="true" class="fa fa-plus"></i> <?= $PMF_LANG['ad_config_stopword_input'] ?></a>';

          return html;
        }


        /**
         * Build an input element to view and edit stop word
         *
         * @param elementId id of the html element
         * @param stopword
         *
         * @return string
         */
        function buildStopWordInputElement(elementId, stopword) {
          elementId = elementId || buildStopWordInputElemId();
          stopword = stopword || '';
          const attrs = 'onblur="saveStopWord(this.id)" onkeydown="saveStopWordHandleEnter(this.id, event)" onfocus="saveOldValue(this.id)"';
          return '<input class="form-control form-control-sm" id="' + elementId + '" value="' + escape(stopword) + '" ' + attrs + '>';
        }

        /**
         * Id attribute is of the format stopword_<id>_<lang>
         *
         * @param id database id of the word
         * @param lang
         *
         * @return string
         */
        function buildStopWordInputElemId(id, lang) {
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
        function parseStopWordInputElemId(elem_id) {
          const info = elem_id.split('_');

          return { id: info[1], lang: info[2] };
        }

        /**
         * Handle enter press on a stop word input element
         *
         * @param elem_id input element id
         * @param event
         *
         * @return void
         */
        function saveStopWordHandleEnter(elem_id, event) {
          const element = $('#' + elem_id);
          event = event || window.event || undefined;

          if (undefined !== event) {
            const key = event.charCode || event.keyCode || 0;
            if (13 === key) {
              if ('' === element.val()) {
                deleteStopWord(elem_id);
              } else {
                // this blur action will cause saveStopWord() call
                element.blur();
              }
            }
          }
        }

        /**
         * Save stopword doing an ajax call
         *
         * @param elem_id input element id
         * @return void
         */
        function saveStopWord(elem_id) {
          const info = parseStopWordInputElemId(elem_id);
          const element = $('#' + elem_id);

          if (element.attr('old_value') !== element.val()) {
            $.get('index.php', {
                action: 'ajax',
                ajax: 'config',
                ajaxaction: 'save_stop_word',
                stopword_id: info.id,
                stopword: element.val(),
                stopwords_lang: info.lang,
                csrf: '<?= $user->getCsrfTokenFromSession();
                ?>',
              },
            );
          } else {
            if (0 > info.id && '' === element.val()) {
              element.remove();
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
        function saveOldValue(elem_id) {
          const element = $('#' + elem_id);
          element.attr('old_value', element.val());
        }


        /**
         * Handle stop word delete doing an ajax request.
         *
         * @param elem_id input element id
         *
         * @return void
         */
        function deleteStopWord(elem_id) {
          const info = parseStopWordInputElemId(elem_id);
          const element = $('#' + elem_id);

          element.fadeOut('slow');

          $.get('index.php', {
              action: 'ajax',
              ajax: 'config',
              ajaxaction: 'delete_stop_word',
              stopword_id: info.id,
              stopwords_lang: info.lang,
              csrf: '<?= $user->getCsrfTokenFromSession() ?>',
            },
            function() {
              loadStopWordsByLang(info.lang);
            },
          );
        }

        /**
         * Handle stop word add prompting for a new word and doing an ajax request.
         *
         * @return void
         */
        function addStopWordInputElem() {
          const word = prompt('<?= $PMF_LANG['ad_config_stopword_input']?>', '');
          const lang = $('#stopwords_lang_selector').val();

          if (!!word) {
            $.get('index.php', {
                action: 'ajax',
                ajax: 'config',
                ajaxaction: 'save_stop_word',
                stopword: word,
                stopwords_lang: lang,
                csrf: '<?= $user->getCsrfTokenFromSession() ?>',
              },
              function() {
                loadStopWordsByLang(lang);
              },
            );
          }
        }

        const escape = (text) => {
          const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
          };

          return text.replace(/[&<>"']/g, (mapped) => {
            return map[mapped];
          });
        };

      </script>
    </div>
  </div>
    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
