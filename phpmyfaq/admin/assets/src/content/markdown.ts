/**
 * Markdown administration stuff
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
 * @since     2023-03-05
 */

import { Modal } from 'bootstrap';
import { fetchMarkdownContent, fetchMediaBrowserContent } from '../api';
import { MediaBrowserApiResponse, Response } from '../interfaces';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

export const handleMarkdownForm = (): void => {
  const answerHeight = localStorage.getItem('phpmyfaq.answer.height') as string;
  const answer = document.getElementById('answer-markdown') as HTMLTextAreaElement;
  const markdownTabs = document.getElementById('markdown-tabs') as HTMLElement;
  const insertImage = document.getElementById('pmf-markdown-insert-image') as HTMLElement;
  const insertImageButton = document.getElementById('pmf-markdown-insert-image-button') as HTMLElement;
  const imageUpload = document.getElementById('pmf-markdown-upload-image') as HTMLElement;
  const imageUploadInput = document.getElementById('pmf-markdown-upload-image-input') as HTMLElement;

  // Store the height of the textarea
  if (answer) {
    if (answerHeight !== 'undefined') {
      answer.style.height = answerHeight as string;
    }

    answer.addEventListener('mouseup', () => {
      localStorage.setItem('phpmyfaq.answer.height', answer.style.height);
    });
  }

  // Handle the Markdown preview
  if (markdownTabs) {
    const tab = document.querySelector('a[data-markdown-tab="preview"]') as HTMLElement;

    if (tab) {
      tab.addEventListener('shown.bs.tab', async (): Promise<void> => {
        const preview = document.getElementById('markdown-preview') as HTMLElement;
        if (preview && answer) {
          preview.style.height = answer.style.height;
          try {
            const response = (await fetchMarkdownContent(answer.value)) as unknown as Response;
            preview.innerHTML = response.success;
          } catch (error) {
            if (error instanceof Error) {
              console.error(error);
            } else {
              console.error('Unknown error:', error);
            }
          }
        }
      });
    }
  }

  // Handle inserting images from Modal
  if (insertImage) {
    const container = document.getElementById('pmf-markdown-insert-image-modal') as HTMLElement;
    const modal = new Modal(container);
    insertImage.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      modal.show();

      const response = (await fetchMediaBrowserContent()) as MediaBrowserApiResponse;

      if (response.success) {
        const list = document.getElementById('pmf-markdown-insert-image-list') as HTMLElement;
        list.innerHTML = ''; // Clear previous content

        response.data.sources.forEach((source): void => {
          source.files.forEach((file): void => {
            const listItem = document.createElement('div') as HTMLElement;
            listItem.classList.add('list-group-item', 'd-flex', 'align-items-center');
            listItem.innerHTML = `
              <div class="form-check me-2">
                <input type="checkbox" class="form-check-input" id="checkbox-${file.file}" data-image-url="${source.baseurl}/${source.path}/${file.file}">
                <label class="form-check-label d-none" for="checkbox-${file.file}" aria-hidden="true">Select</label>
              </div>
              <img src="${source.baseurl}/${source.path}/${file.file}" class="img-thumbnail" alt="${file.file}" style="height: 100px;">
            `;
            list.appendChild(listItem);
          });
        });
      }
    });

    // Add event listener to the insert image button
    insertImageButton.addEventListener('click', (): void => {
      const checkboxes = document.querySelectorAll('.form-check-input:checked') as NodeListOf<HTMLInputElement>;
      let markdownImages: string = '';

      checkboxes.forEach((checkbox: HTMLInputElement): void => {
        const imageUrl = (checkbox as HTMLInputElement).dataset.imageUrl as string;
        if (imageUrl) {
          markdownImages += `![Image](${imageUrl})\n`;
        }
      });

      // Insert the Markdown images at the cursor position
      const startPos: number = answer.selectionStart;
      const endPos: number = answer.selectionEnd;
      answer.value = answer.value.substring(0, startPos) + markdownImages + answer.value.substring(endPos);
      answer.setSelectionRange(startPos + markdownImages.length, startPos + markdownImages.length);
      answer.focus();

      modal.hide();
    });
  }

  // Handle image upload
  if (imageUpload) {
    imageUpload.addEventListener('click', (event: Event): void => {
      event.preventDefault();
      imageUploadInput.click();
    });

    imageUploadInput.addEventListener('change', async (event: Event): Promise<void> => {
      const input = event.target as HTMLInputElement;
      const csrfToken = (document.getElementById('pmf-markdown-upload-image-csrf-token') as HTMLInputElement)
        .value as string;
      if (input.files) {
        const formData = new FormData();
        for (const file of input.files) {
          formData.append('files[]', file);
        }

        try {
          const response = await fetch('./api/content/images?csrf=' + csrfToken, {
            method: 'POST',
            body: formData,
          });

          if (!response.ok) {
            throw new Error('Network response was not ok');
          }

          const responseData = await response.json();
          if (responseData.success) {
            pushNotification('Files uploaded successfully');
          } else {
            pushErrorNotification('Upload failed:' + responseData.messages);
          }
        } catch (error) {
          pushErrorNotification('Error uploading files: ' + error);
        }
      }
    });
  }
};
