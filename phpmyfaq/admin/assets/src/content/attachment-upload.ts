/**
 * FAQ attachment upload stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-11
 */
import { addElement } from '../../../../assets/src/utils';
import { uploadAttachments } from '../api';
import { Attachment } from '../interfaces';

export const handleAttachmentUploads = (): void => {
  const filesToUpload = document.getElementById('filesToUpload') as HTMLInputElement | null;
  const fileUploadButton = document.getElementById('pmf-attachment-modal-upload') as HTMLButtonElement | null;

  if (filesToUpload) {
    filesToUpload.addEventListener('change', (): void => {
      const files: FileList | null = filesToUpload.files;
      const fileSize = document.getElementById('filesize') as HTMLElement;
      const fileList = document.querySelector('.pmf-attachment-upload-files') as HTMLElement;

      if (!files || files.length === 0) {
        return;
      }

      fileList.classList.remove('invisible');

      let bytes: number = 0;
      const numFiles: number = files.length;
      const fileItems: HTMLElement[] = [];
      for (let fileId: number = 0; fileId < numFiles; fileId++) {
        bytes += files[fileId].size;
        fileItems.push(addElement('li', { innerText: files[fileId].name }));
      }

      let output: string = bytes + ' bytes';
      for (
        let multiples: string[] = ['KiB', 'MiB', 'GiB', 'TiB'], multiple: number = 0, approx: number = bytes / 1024;
        approx > 1;
        approx /= 1024, multiple++
      ) {
        output = approx.toFixed(2) + ' ' + multiples[multiple] + ' (' + bytes + ' bytes)';
      }

      fileSize.textContent = output;
      fileList.append(addElement('ul', { className: 'mt-2' }, fileItems));
    });

    fileUploadButton?.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      event.stopImmediatePropagation();

      const files: FileList | null = filesToUpload.files;
      if (!files || files.length === 0) {
        console.error('No files selected for upload.');
        return;
      }

      const formData = new FormData();
      for (let i: number = 0; i < files.length; i++) {
        formData.append('filesToUpload[]', files[i]);
      }
      formData.append('record_id', (document.getElementById('attachment_record_id') as HTMLInputElement).value);
      formData.append('record_lang', (document.getElementById('attachment_record_lang') as HTMLInputElement).value);

      try {
        const response = (await uploadAttachments(formData)) as unknown as Attachment[];
        const modal = document.getElementById('attachmentModal') as HTMLElement;
        const modalBackdrop = document.querySelector('.modal-backdrop.fade.show') as HTMLElement;
        const attachmentList = document.querySelector('.adminAttachments') as HTMLElement;
        const fileSize = document.getElementById('filesize') as HTMLElement;
        const fileList: NodeListOf<Element> = document.querySelectorAll('.pmf-attachment-upload-files li');

        response.forEach((attachment: Attachment): void => {
          const csrfToken = attachmentList.getAttribute('data-pmf-csrf-token') as string;
          attachmentList.insertAdjacentElement(
            'beforeend',
            addElement('li', {}, [
              addElement('a', {
                className: 'me-2',
                href: `../index.php?action=attachment&id=${attachment.attachmentId}`,
                innerText: attachment.fileName,
              }),
              addElement(
                'button',
                {
                  type: 'button',
                  className: 'btn btn-sm btn-danger pmf-delete-attachment-button',
                  'data-pmfAttachmentId': attachment.attachmentId,
                  'data-pmfCsrfToken': csrfToken,
                },
                [
                  addElement('i', {
                    className: 'bi bi-trash',
                    'data-pmfAttachmentId': attachment.attachmentId,
                    'data-pmfCsrfToken': csrfToken,
                  }),
                ]
              ),
            ])
          );
        });

        fileSize.textContent = '';
        fileList.forEach((li: Element): void => {
          li.remove();
        });

        modal.style.display = 'none';
        modal.classList.remove('show');
        modalBackdrop.remove();
      } catch (error) {
        console.error('An error occurred:', error);
      }
    });
  }
};
