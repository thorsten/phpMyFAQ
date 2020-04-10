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
 * @copyright 2003-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-26
 */

use phpMyFAQ\Language;
use phpMyFAQ\Template;

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
    <footer class="sticky-footer bg-white">
      <div class="container my-auto">
        <div class="copyright text-center my-auto">
          Proudly powered by <strong>phpMyFAQ <?= $faqConfig->get('main.currentVersion'); ?></strong> |
          <a href="https://www.phpmyfaq.de/documentation" target="_blank">phpMyFAQ documentation</a> |
          Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> |
          <i aria-hidden="true" class="fa fa-apple"></i> Available on the
          <a target="_blank" href="https://itunes.apple.com/app/phpmyfaq/id977896957">App Store</a> |
          &copy; 2001-<?= date('Y') ?> <a href="https://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a>
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
    if (isset($auth) && (('takequestion' == $action) || ('editentry' == $action) || ('editpreview' == $action) ||
            ('add-news' == $action) || ('edit-news' == $action) || ('copyentry' == $action))) {
        if ($faqConfig->get('main.enableWysiwygEditor') == true) {
            ?>
          <script>

            // Bootstrap tooltips
            $().tooltip({placement: 'bottom'});

            // TinyMCE
            tinyMCE.init({
              // General options
              mode: 'exact',
              language: '<?=(Language::isASupportedTinyMCELanguage($faqLangCode) ? $faqLangCode : 'en') ?>',
              selector: 'textarea#<?= ('add-news' == $action || 'edit-news' == $action) ? 'news' : 'answer' ?>',
              theme: 'modern',
              plugins: [
                'print preview fullpage searchreplace autolink directionality visualblocks visualchars fullscreen',
                'image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime',
                'advlist lists textcolor wordcount imagetools contextmenu colorpicker textpattern help autosave',
                'phpmyfaq'
              ],
              relative_urls: false,
              convert_urls: false,
              document_base_url: '<?= $faqConfig->getDefaultUrl() ?>',
              remove_linebreaks: false,
              use_native_selects: true,
              paste_remove_spans: true,
              entities: '10',
              entity_encoding: 'raw',
              toolbar1: 'formatselect | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | removeformat',
              toolbar2: 'insertfile | paste codesample | link image preview media | forecolor backcolor emoticons | phpmyfaq print',
              height: '<?= ('add-news' == $action || 'edit-news' == $action) ? '20vh' : '50vh' ?>',
              image_advtab: true,
              image_class_list: [
                {title: 'None', value: ''},
                {title: 'Responsive', value: 'img-fluid'}
              ],
              image_dimensions: true,

              // Formatting
              style_formats: [
                {
                  title: 'Headers', items: [
                    {title: 'h1', block: 'h1'},
                    {title: 'h2', block: 'h2'},
                    {title: 'h3', block: 'h3'},
                    {title: 'h4', block: 'h4'},
                    {title: 'h5', block: 'h5'},
                    {title: 'h6', block: 'h6'}
                  ]
                },

                {
                  title: 'Blocks', items: [
                    {title: 'p', block: 'p'},
                    {title: 'div', block: 'div'},
                    {title: 'pre', block: 'pre'},
                    {title: 'code', block: 'code'}
                  ]
                },

                {
                  title: 'Containers', items: [
                    {title: 'blockquote', block: 'blockquote', wrapper: true},
                    {title: 'figure', block: 'figure', wrapper: true}
                  ]
                }
              ],

              paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,h4,h5,h6",
              paste_data_images: true,
              visualblocks_default_state: true,
              end_container_on_empty_block: true,
              extended_valid_elements: "code[class],video[*],audio[*],source[*],iframe[*]",
              removeformat: [
                {selector: '*', attributes: ['style'], split: false, expand: false, deep: true}
              ],
              importcss_append: true,

              // Save function
              save_onsavecallback: () => {
                phpMyFAQSave();
              },

              // phpMyFAQ CSS
              content_css: '../assets/dist/styles.css?<?= time(); ?>',

              // Replace values for the template plugin
              template_replace_values: {
                username: '<?= addslashes($user->userdata->get('display_name')) ?>',
                user_id: '<?= $user->userdata->get('user_id') ?>'
              },

              // File browser
              file_browser_callback: function (fieldName, url, type, win) {
                let fileBrowser = 'image.browser.php';
                fileBrowser += (fileBrowser.indexOf('?') < 0) ? '?type=' + type : '&type=' + type;
                tinymce.activeEditor.windowManager.open({
                  title: 'Select an image',
                  url: fileBrowser,
                  width: 640,
                  height: 480
                }, {
                  window: win,
                  input: fieldName
                });

                return false;
              },

              // without images_upload_url set, Upload tab won't show up
              images_upload_url: 'index.php?action=ajax&ajax=image&ajaxaction=upload',

              // override default upload handler to simulate successful upload
              images_upload_handler: (blobInfo, success, failure) => {
                let xhr, formData;

                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', 'index.php?action=ajax&ajax=image&ajaxaction=upload&csrf=<?= $user->getCsrfTokenFromSession() ?>');

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
              csrf: $('#csrf').val()
            });

            function phpMyFAQSave() {
              const indicator = $('#pmf-admin-saving-data-indicator'),
                input = document.createElement('input');
              indicator.html('<i class="fa fa-cog fa-spin fa-fw"></i> Saving ...');
              input.setAttribute('name', $('button:submit')[0].name);
              input.setAttribute('id', 'temporarySaveButton');
              $('#answer')[0].parentNode.appendChild(input);
              // Submit the form by an ajax request
              <?php if (isset($faqData['id']) && $faqData['id'] === 0): ?>
              let data = {
                action: 'ajax',
                ajax: 'recordAdd'
              };
              <?php else: ?>
              let data = {
                action: 'ajax',
                ajax: 'recordSave'
              };
              <?php endif; ?>
              $.each($('#faqEditor').serializeArray(), function (i, field) {
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
