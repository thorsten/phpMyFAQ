/**
 * FAQ attachment upload stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-11
 */
import { addElement, pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { uploadAttachments } from '../api';
import { Attachment } from '../interfaces';
import { bindAttachmentDeleteButton, updateAttachmentCountBadge } from './faqs';

export const appendAttachmentToList = (attachment: Attachment): void => {
  const attachmentList = document.querySelector('.adminAttachments') as HTMLElement | null;
  if (!attachmentList) {
    return;
  }

  const csrfToken = attachmentList.getAttribute('data-pmf-csrf-token') as string;
  const deleteButton = addElement(
    'button',
    {
      type: 'button',
      className: 'btn btn-sm btn-danger pmf-delete-attachment-button',
      'data-pmf-attachment-id': attachment.attachmentId,
      'data-pmf-csrf-token': csrfToken,
    },
    [
      addElement('i', {
        className: 'bi bi-trash',
        'data-pmf-attachment-id': attachment.attachmentId,
        'data-pmf-csrf-token': csrfToken,
      }),
    ]
  );
  bindAttachmentDeleteButton(deleteButton);
  attachmentList.insertAdjacentElement(
    'beforeend',
    addElement('li', { id: `attachment-id-${attachment.attachmentId}` }, [
      addElement('a', {
        className: 'me-2',
        href: `../attachment/${attachment.attachmentId}`,
        innerText: attachment.fileName,
      }),
      deleteButton,
    ])
  );
};

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

      const customNameLabel: string = filesToUpload.getAttribute('data-pmf-custom-name-label') ?? '';

      let bytes: number = 0;
      const numFiles: number = files.length;
      const rows: HTMLElement[] = [];
      for (let fileId: number = 0; fileId < numFiles; fileId++) {
        bytes += files[fileId].size;

        const name: string = files[fileId].name;
        const dotIndex: number = name.lastIndexOf('.');
        const baseName: string = dotIndex > 0 ? name.slice(0, dotIndex) : name;
        const extension: string = dotIndex > 0 ? name.slice(dotIndex) : '';

        const input: HTMLElement = addElement('input', {
          type: 'text',
          className: 'form-control pmf-attachment-custom-name',
          'data-pmf-file-index': String(fileId),
          placeholder: baseName,
          'aria-label': customNameLabel,
        });

        const inputGroupChildren: HTMLElement[] = [input];
        if (extension !== '') {
          inputGroupChildren.push(addElement('span', { className: 'input-group-text', innerText: extension }));
        }

        rows.push(
          addElement('li', { className: 'mb-2' }, [
            addElement('div', { className: 'small text-muted', innerText: name }),
            addElement('div', { className: 'input-group input-group-sm' }, inputGroupChildren),
          ])
        );
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
      fileList.append(addElement('ul', { className: 'list-unstyled mt-2' }, rows));
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
        const customNameInput = document.querySelector(
          `input.pmf-attachment-custom-name[data-pmf-file-index="${i}"]`
        ) as HTMLInputElement | null;
        formData.append('customFileNames[]', customNameInput ? customNameInput.value.trim() : '');
      }
      formData.append('record_id', (document.getElementById('attachment_record_id') as HTMLInputElement).value);
      formData.append('record_lang', (document.getElementById('attachment_record_lang') as HTMLInputElement).value);
      formData.append(
        'pmf-csrf-token',
        (document.getElementById('pmf-attachment-csrf-token') as HTMLInputElement).value
      );

      try {
        const response = await uploadAttachments(formData);
        const uploadedMessage = fileUploadButton?.getAttribute('data-pmf-msg-uploaded') ?? 'Attachments uploaded.';
        pushNotification(uploadedMessage);
        const modal = document.getElementById('attachmentModal') as HTMLElement | null;
        const modalBackdrop = document.querySelector('.modal-backdrop.fade.show') as HTMLElement | null;
        const fileSize = document.getElementById('filesize') as HTMLElement | null;
        const fileList: NodeListOf<Element> = document.querySelectorAll('.pmf-attachment-upload-files li');

        response.forEach((attachment: Attachment): void => appendAttachmentToList(attachment));
        updateAttachmentCountBadge(response.length);

        if (fileSize) {
          fileSize.textContent = '';
        }

        fileList.forEach((li: Element): void => {
          li.remove();
        });

        if (modal) {
          modal.style.display = 'none';
          modal.classList.remove('show');
        }

        if (modalBackdrop) {
          modalBackdrop.remove();
        }
      } catch (error) {
        console.error('An error occurred:', error);
        pushErrorNotification(error instanceof Error ? error.message : 'Attachment upload failed.');
      }
    });
  }
};

const uploadDroppedFiles = async (files: File[], dropzone: HTMLElement): Promise<void> => {
  const progressList = document.getElementById('pmf-attachment-dropzone-progress') as HTMLElement | null;
  const recordId = (document.getElementById('attachment_record_id') as HTMLInputElement | null)?.value ?? '';
  const recordLang = (document.getElementById('attachment_record_lang') as HTMLInputElement | null)?.value ?? '';
  const csrfToken = (document.getElementById('pmf-attachment-csrf-token') as HTMLInputElement | null)?.value ?? '';
  const maxSize = Number(dropzone.getAttribute('data-pmf-max-size'));
  const tooBigMessage = dropzone.getAttribute('data-pmf-msg-too-big') ?? 'File is too big.';
  const completedRows: HTMLElement[] = [];

  // fetch() exposes no upload progress, so files are uploaded one at a time and
  // each row flips from spinner to a result icon as its request completes.
  for (const file of files) {
    const progressIcon = addElement('span', {
      className: 'spinner-border spinner-border-sm me-2',
      role: 'status',
      'aria-hidden': 'true',
    });
    const progressRow = addElement('li', { className: 'small' }, [
      progressIcon,
      addElement('span', { innerText: file.name }),
    ]);
    progressList?.append(progressRow);

    if (Number.isFinite(maxSize) && maxSize > 0 && file.size > maxSize) {
      progressRow.classList.add('text-danger');
      progressIcon.replaceWith(addElement('i', { className: 'bi bi-x-circle me-2', 'aria-hidden': 'true' }));
      progressRow.append(addElement('span', { className: 'ms-2', innerText: tooBigMessage }));
      continue;
    }

    const formData = new FormData();
    formData.append('filesToUpload[]', file);
    formData.append('customFileNames[]', '');
    formData.append('record_id', recordId);
    formData.append('record_lang', recordLang);
    formData.append('pmf-csrf-token', csrfToken);

    try {
      const response = await uploadAttachments(formData);
      response.forEach((attachment: Attachment): void => appendAttachmentToList(attachment));
      updateAttachmentCountBadge(response.length);
      progressRow.classList.add('text-success');
      progressIcon.replaceWith(addElement('i', { className: 'bi bi-check-circle me-2', 'aria-hidden': 'true' }));
      completedRows.push(progressRow);
    } catch (error) {
      console.error('An error occurred:', error);
      progressRow.classList.add('text-danger');
      progressIcon.replaceWith(addElement('i', { className: 'bi bi-x-circle me-2', 'aria-hidden': 'true' }));
      pushErrorNotification(error instanceof Error ? error.message : 'Attachment upload failed.');
    }
  }

  // Clear only this batch's successful rows — a concurrent batch keeps its
  // live spinners, and failure rows stay visible so the user can read why a
  // file is missing from the attachment list.
  window.setTimeout((): void => {
    completedRows.forEach((row: HTMLElement): void => row.remove());
  }, 5000);
};

export const handleAttachmentDragAndDrop = (): void => {
  const dropzone = document.getElementById('pmf-attachment-dropzone') as HTMLElement | null;
  const browseButton = document.getElementById('pmf-attachment-dropzone-browse') as HTMLButtonElement | null;
  const browseInput = document.getElementById('pmf-attachment-dropzone-input') as HTMLInputElement | null;

  if (!dropzone) {
    return;
  }

  ['dragover', 'dragenter'].forEach((type: string): void => {
    dropzone.addEventListener(type, (event: Event): void => {
      event.preventDefault();
      dropzone.classList.add('pmf-dragover');
    });
  });

  ['dragleave', 'drop'].forEach((type: string): void => {
    dropzone.addEventListener(type, (): void => {
      dropzone.classList.remove('pmf-dragover');
    });
  });

  dropzone.addEventListener('drop', (event: Event): void => {
    event.preventDefault();
    const files = (event as DragEvent).dataTransfer?.files;
    if (files && files.length > 0) {
      void uploadDroppedFiles(Array.from(files), dropzone);
    }
  });

  browseButton?.addEventListener('click', (): void => {
    browseInput?.click();
  });

  browseInput?.addEventListener('change', (): void => {
    if (browseInput.files && browseInput.files.length > 0) {
      void uploadDroppedFiles(Array.from(browseInput.files), dropzone);
      browseInput.value = '';
    }
  });
};
