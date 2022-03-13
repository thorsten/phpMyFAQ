<?php

/**
 * Footer of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-26
 */

use phpMyFAQ\Language;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
            </div>
        </main>

        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Proudly powered by <strong>phpMyFAQ <?= $faqConfig->getVersion(); ?></strong></div>
                    <div>
                        <a target="_blank" rel="noopener" href="https://www.phpmyfaq.de/documentation">Documentation</a>
                        &middot;
                        <a target="_blank" rel="noopener" href="https://twitter.com/phpMyFAQ">Twitter</a>
                        &middot;
                        <a target="_blank" rel="noopener" href="https://facebook.com/phpMyFAQ">Facebook</a>
                        &middot;
                        &copy; 2001-<?= date('Y') ?> <a target="_blank" rel="noopener" href="https://www.phpmyfaq.de/">phpMyFAQ Team</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>


<?php
if (DEBUG) {
    printf('<hr><div class="container">DEBUG INFORMATION:<br>%s</div>', $faqConfig->getDb()->log());
}

if (isset($auth)) {
    ?>
  <iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?= $faqLangCode ?>" width="0" height="0"
          style="display: none;"></iframe>
    <?php
    if ((('takequestion' == $action) || ('editentry' == $action) || ('editpreview' == $action) ||
        ('add-news' == $action) || ('edit-news' == $action) || ('copyentry' == $action))
    ) {
        if ($faqConfig->get('main.enableWysiwygEditor') == true) {
            ?>
          <script>

            // Bootstrap tooltips
            // $().tooltip({ placement: 'bottom' });

            // TinyMCE
            tinymce.init({
              // General options
              mode: 'exact',
              language: '<?=(Language::isASupportedTinyMCELanguage($faqLangCode) ? $faqLangCode : 'en') ?>',
              selector: 'textarea#<?= ('add-news' == $action || 'edit-news' == $action) ? 'news' : 'answer' ?>',
              menubar: false,
              theme: 'silver',
              fontsize_formats: '6pt 8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 36pt 48pt',
              font_formats:
                'Arial=arial,helvetica,sans-serif;' +
                'Arial Black=arial black,avant garde;' +
                'Calibri=calibri;' +
                'Comic Sans MS=comic sans ms,sans-serif;' +
                'Courier New=courier new,courier;' +
                'Georgia=georgia,palatino;' +
                'Helvetica=helvetica;' +
                'Impact=impact,chicago;' +
                'Symbol=symbol;' +
                'Tahoma=tahoma,arial,helvetica,sans-serif;' +
                'Terminal=terminal,monaco;' +
                'Times New Roman=times new roman,times;' +
                'Verdana=verdana,geneva;' +
                'Webdings=webdings;' +
                'Wingdings=wingdings,zapf dingbats',
              plugins: [
                'advlist autolink link image lists charmap print preview hr anchor pagebreak fullpage toc',
                'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking codesample',
                'save table directionality emoticons template paste imagetools textpattern help phpmyfaq',
              ],
              mobile: {
                theme: 'silver',
                toolbar_mode: 'floating',
                toolbar1: "link unlink image | table bullist numlist",
              },
              relative_urls: false,
              convert_urls: false,
              document_base_url: '<?= $faqConfig->getDefaultUrl() ?>',
              remove_linebreaks: false,
              use_native_selects: true,
              paste_remove_spans: true,
              entities: '10',
              entity_encoding: 'raw',
              toolbar1: 'newdocument | undo redo | bold italic underline subscript superscript strikethrough | styleselect | formatselect | fontselect | fontsizeselect | outdent indent | alignleft aligncenter alignright alignjustify | removeformat | insertfile | cut copy paste pastetext codesample | bullist numlist | link unlink anchor image media | charmap | insertdatetime | table | forecolor backcolor emoticons | searchreplace | spellchecker | hr | pagebreak | code | phpmyfaq print | preview | custFontSize | toc | fullscreen',
              height: '<?= ('add-news' == $action || 'edit-news' == $action) ? '20vh' : '50vh' ?>',
              image_advtab: true,
              image_class_list: [
                { title: 'None', value: '' },
                { title: 'Responsive', value: 'img-fluid' },
              ],
              image_dimensions: true,

              // Formatting
              style_formats: [
                {
                  title: 'Headers', items: [
                    { title: 'h1', block: 'h1' },
                    { title: 'h2', block: 'h2' },
                    { title: 'h3', block: 'h3' },
                    { title: 'h4', block: 'h4' },
                    { title: 'h5', block: 'h5' },
                    { title: 'h6', block: 'h6' },
                  ],
                },

                {
                  title: 'Blocks', items: [
                    { title: 'p', block: 'p' },
                    { title: 'div', block: 'div' },
                    { title: 'pre', block: 'pre' },
                    { title: 'code', block: 'code' },
                  ],
                },

                {
                  title: 'Containers', items: [
                    { title: 'blockquote', block: 'blockquote', wrapper: true },
                    { title: 'figure', block: 'figure', wrapper: true },
                  ],
                },
              ],

              paste_word_valid_elements: 'b,strong,i,em,h1,h2,h3,h4,h5,h6',
              paste_data_images: true,
              visualblocks_default_state: true,
              end_container_on_empty_block: true,
              extended_valid_elements: 'code[class],video[*],audio[*],source[*]',
              removeformat: [
                { selector: '*', attributes: ['style'], split: false, expand: false, deep: true },
              ],
              importcss_append: true,

              // phpMyFAQ CSS
              content_css: '<?php $faqConfig->getDefaultUrl() ?>/assets/dist/styles.css?<?= time(); ?>',

              // Replace values for the template plugin
              template_replace_values: {
                username: '<?= addslashes($user->userdata->get('display_name')) ?>',
                user_id: '<?= $user->userdata->get('user_id') ?>',
              },

              // File browser
              file_picker_types: 'image media',
              file_picker_callback: (callback, value, meta) => {
                  const type = meta.filetype
                  const w = window,
                      d = document,
                      e = d.documentElement,
                      g = d.getElementsByTagName('body')[0],
                      x = w.innerWidth || e.clientWidth || g.clientWidth,
                      y = w.innerHeight || e.clientHeight || g.clientHeight

                  let mediaBrowser = 'media.browser.php';
                  mediaBrowser += (mediaBrowser.indexOf('?') < 0) ? '?type=' + type : '&type=' + type;

                  tinymce.activeEditor.windowManager.openUrl({
                      url: mediaBrowser,
                      title: 'Select media file',
                      width: x * 0.8,
                      height: y * 0.8,
                      resizable: "yes",
                      close_previous: "no",
                      onMessage: function (api, data) {
                          if (data.mceAction === 'phpMyFAQMediaBrowserAction') {
                              callback(data.url);
                              api.close();
                          }
                      }
                  });
              },

              // without images_upload_url set, Upload tab won't show up
              images_upload_url: 'index.php?action=ajax&ajax=image&ajaxaction=upload',

              // override default upload handler to simulate successful upload
              // @todo rewrite this piece of code...
              images_upload_handler: (blobInfo, success, failure) => {
                let xhr, formData;

                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', 'index.php?action=ajax&ajax=image&ajaxaction=upload&csrf=<?= $user->getCsrfTokenFromSession(
                ) ?>');

                xhr.onload = () => {
                  let json;

                  if (xhr.status !== 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                  }

                  json = JSON.parse(xhr.responseText);

                  if (!json || typeof json.location !== 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                  }

                  success(json.location);
                };

                formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                xhr.send(formData);
              },

              // Custom params
              csrf: $('#csrf').val(),
            });
          </script>
            <?php
        }
    }
}
?>
</body>
</html>
