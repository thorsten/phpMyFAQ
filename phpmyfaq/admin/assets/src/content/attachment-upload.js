/**
 * FAQ attachment upload  stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-11
 */

import { addElement } from '../../../../assets/src/utils';

export const handleAttachmentUploads = () => {
  const filesToUpload = document.getElementById('filesToUpload');
  const fileUploadButton = document.getElementById('pmf-attachment-modal-upload');

  // Calculate upload size and show file to upload
  if (filesToUpload) {
    filesToUpload.addEventListener('change', function () {
      const files = filesToUpload.files;
      const fileSize = document.getElementById('filesize');
      const fileList = document.querySelector('.pmf-attachment-upload-files');

      fileList.classList.remove('invisible');

      let bytes = 0;
      let numFiles = files.length;
      let fileItems = [];
      for (let fileId = 0; fileId < numFiles; fileId++) {
        bytes += files[fileId].size;
        fileItems.push(addElement('li', { innerText: files[fileId].name }));
      }

      let output = bytes + ' bytes';
      for (
        let multiples = ['KiB', 'MiB', 'GiB', 'TiB'], multiple = 0, approx = bytes / 1024;
        approx > 1;
        approx /= 1024, multiple++
      ) {
        output = approx.toFixed(2) + ' ' + multiples[multiple] + ' (' + bytes + ' bytes)';
      }

      fileSize.innerHTML = output;
      fileList.append(addElement('ul', { className: 'mt-2' }, fileItems));
    });

    // handle upload button
    fileUploadButton.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();

      const files = filesToUpload.files;
      const formData = new FormData();

      for (let i = 0; i < files.length; i++) {
        formData.append('filesToUpload[]', files[i]);
      }
      formData.append('record_id', document.getElementById('attachment_record_id').value);
      formData.append('record_lang', document.getElementById('attachment_record_lang').value);

      fetch('index.php?action=ajax&ajax=att&ajaxaction=upload', {
        method: 'POST',
        cache: 'no-cache',
        body: formData,
      })
        .then(async (response) => {
          if (response.ok) {
            return response.json();
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((attachments) => {
          const modal = document.getElementById('attachmentModal');
          const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');
          const attachmentList = document.querySelector('.adminAttachments');
          const fileSize = document.getElementById('filesize');
          const fileList = document.querySelectorAll('.pmf-attachment-upload-files li');
          attachments.forEach((attachment) => {
            attachmentList.insertAdjacentElement(
              'afterend',
              addElement('li', {}, [
                addElement('a', {
                  className: 'me-2',
                  href: `../index.php?action=attachment&id=${attachment.attachmentId}`,
                  innerText: attachment.fileName,
                }),
                addElement('a', {
                  className: 'badge bg-danger',
                  href: `?action=delatt&record_id=${attachment.faqId}&id=${attachment.attachmentId}&lang=attachment.faqLanguage`,
                  innerHTML: '<i aria-hidden="true" class="fa fa-trash"></i>',
                }),
              ])
            );
          });
          fileSize.innerHTML = '';
          fileList.forEach((li) => {
            li.remove();
          });
          modal.style.display = 'none';
          modal.classList.remove('show');
          modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);
        })
        .catch(async (error) => {
          const errors = await error.cause.response.json();
          console.log(errors);
        });
    });
  }
};
