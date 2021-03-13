<?php

/**
 * Footer of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<!-- Footer -->
<footer class="sticky-footer bg-white mt-3">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      Proudly powered by <strong>phpMyFAQ <?= $faqConfig->getVersion(); ?></strong> |
      <a target="_blank" rel="noopener" href="https://www.phpmyfaq.de/documentation">phpMyFAQ documentation</a> |
      Follow us on <a target="_blank" rel="noopener" href="https://twitter.com/phpMyFAQ">Twitter</a> |
      Like us on <a target="_blank" rel="noopener" href="https://facebook.com/phpMyFAQ"> Facebook</a> |
      &copy; 2001-<?= date('Y') ?> <a target="_blank" rel="noopener" href="https://www.phpmyfaq.de/">phpMyFAQ Team</a>
    </div>
  </div>
    <?php
    if (DEBUG) {
        printf('<hr><div class="container">DEBUG INFORMATION:<br>%s</div>', $faqConfig->getDb()->log());
    }
    ?>
</footer>
<!-- End of Footer -->

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>


<?php
if (isset($auth)) {
    ?>
  <iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?= $faqLangCode ?>" width="0" height="0"
          style="display: none;"></iframe>
    <?php
    if (
        isset($auth) && (('takequestion' == $action) || ('editentry' == $action) || ('editpreview' == $action) ||
            ('add-news' == $action) || ('edit-news' == $action) || ('copyentry' == $action))
    ) {
        if ($faqConfig->get('main.enableWysiwygEditor') == true) {
            ?>
          <script>

            // Bootstrap tooltips
            $().tooltip({ placement: 'bottom' });

            // TinyMCE
            tinyMCE.init({
              // General options
              mode: 'exact',
              language: '<?=(Language::isASupportedTinyMCELanguage($faqLangCode) ? $faqLangCode : 'en') ?>',
              selector: 'textarea#<?= ('add-news' == $action || 'edit-news' == $action) ? 'news' : 'answer' ?>',
              menubar: false,
              theme: 'modern',
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
                'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker fullpage toc',
                'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking codesample',
                'save table contextmenu directionality emoticons template paste textcolor imagetools colorpicker textpattern help phpmyfaq',
              ],
              relative_urls: false,
              convert_urls: false,
              document_base_url: '<?= $faqConfig->getDefaultUrl() ?>',
              remove_linebreaks: false,
              use_native_selects: true,
              paste_remove_spans: true,
              entities: '10',
              entity_encoding: 'raw',
              toolbar1: 'newdocument | undo redo | bold italic underline subscript superscript strikethrough | styleselect | formatselect | fontselect | fontsizeselect | outdent indent | alignleft aligncenter alignright alignjustify | removeformat',
              toolbar2: 'insertfile | cut copy paste pastetext codesample | bullist numlist | link unlink anchor image media | charmap | insertdatetime | table | forecolor backcolor emoticons | searchreplace | spellchecker | hr | pagebreak | code | phpmyfaq print | preview | custFontSize | toc | fullscreen',
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

              // Security, see https://www.tiny.cloud/docs/release-notes/release-notes56/#securityfixes
              invalid_elements: 'iframe,object,embed',

              // Save function
              save_onsavecallback: () => {
                phpMyFAQSave();
              },

              // phpMyFAQ CSS
              content_css: '<?php $faqConfig->getDefaultUrl() ?>/assets/dist/styles.css?<?= time(); ?>',

              // Replace values for the template plugin
              template_replace_values: {
                username: '<?= addslashes($user->userdata->get('display_name')) ?>',
                user_id: '<?= $user->userdata->get('user_id') ?>',
              },

              // File browser
              // @deprecated have to be rewritten for TinyMCE v5 in phpMyFAQ v3.2
              file_browser_callback_types: 'image media',
              file_browser_callback: function(fieldName, url, type, win) {
                let mediaBrowser = 'media.browser.php';
                mediaBrowser += (mediaBrowser.indexOf('?') < 0) ? '?type=' + type : '&type=' + type;
                tinymce.activeEditor.windowManager.open({
                  title: 'Select media file',
                  url: mediaBrowser,
                  width: 640,
                  height: 480,
                }, {
                  window: win,
                  input: fieldName,
                });

                return false;
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

            function phpMyFAQSave() {
              const indicator = $('#pmf-admin-saving-data-indicator'),
                input = document.createElement('input');
              indicator.html('<i class="fa fa-cog fa-spin fa-fw"></i> Saving ...');
              input.setAttribute('name', $('button:submit')[0].name);
              input.setAttribute('id', 'temporarySaveButton');
              $('#answer')[0].parentNode.appendChild(input);
              // Submit the form by an ajax request
                <?php if (isset($faqData['id']) && $faqData['id'] === 0) : ?>
              let data = {
                action: 'ajax',
                ajax: 'recordAdd',
              };
                <?php else : ?>
              let data = {
                action: 'ajax',
                ajax: 'recordSave',
              };
                <?php endif; ?>
              $.each($('#faqEditor').serializeArray(), function(i, field) {
                data[field.name] = field.value;
              });

              $.post('index.php', data, null);
              indicator.html('<?= $PMF_LANG['ad_entry_savedsuc'] ?>');
              $('#temporarySaveButton').remove();
              indicator.fadeOut(5000);
            }
          </script>
            <?php
        }
    }
}
?>
</body>
</html>
