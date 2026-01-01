/**
 * News administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-20
 */

import { activateNews, addNews, deleteNews, updateNews } from '../api';
import { Modal } from 'bootstrap';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { Response } from '../interfaces';

interface NewsData {
  news: string;
  newsHeader: string;
  authorName: string;
  authorEmail: string;
  active: boolean;
  comment: boolean;
  link: string;
  linkTitle: string;
  langTo: string;
  target: string;
  csrfToken: string;
  id?: string;
}

export const handleAddNews = (): void => {
  const submit = document.getElementById('submitAddNews') as HTMLButtonElement | null;
  if (submit) {
    submit.addEventListener('click', async (event: Event) => {
      event.preventDefault();
      let target = '';
      document.querySelectorAll<HTMLInputElement>('#target').forEach((item) => {
        if (item.checked) {
          target = item.value;
        }
      });

      const data: NewsData = {
        news: (document.getElementById('editor') as HTMLInputElement).value,
        newsHeader: (document.getElementById('newsheader') as HTMLInputElement).value,
        authorName: (document.getElementById('authorName') as HTMLInputElement).value,
        authorEmail: (document.getElementById('authorEmail') as HTMLInputElement).value,
        active: (document.getElementById('active') as HTMLInputElement).checked,
        comment: (document.getElementById('comment') as HTMLInputElement).checked,
        link: (document.getElementById('link') as HTMLInputElement).value,
        linkTitle: (document.getElementById('linkTitle') as HTMLInputElement).value,
        langTo: (document.getElementById('langTo') as HTMLInputElement).value,
        target: target,
        csrfToken: (document.getElementById('pmf-csrf-token') as HTMLInputElement).value,
      };
      const response = (await addNews(data)) as unknown as Response;
      if (typeof response.success === 'string') {
        pushNotification(response.success);
        setTimeout(() => {
          window.location.href = './news';
        }, 3000);
      } else {
        pushErrorNotification(response.error);
      }
    });
  }
};

export const handleNews = (): void => {
  const deleteNewsButton = document.getElementById('deleteNews') as HTMLButtonElement | null;
  if (deleteNewsButton) {
    document.querySelectorAll<HTMLButtonElement>('#deleteNews').forEach((item) => {
      item.addEventListener('click', (event: Event) => {
        event.preventDefault();
        const modal = new Modal(document.getElementById('confirmDeleteNewsModal') as HTMLElement);
        (document.getElementById('newsId') as HTMLInputElement).value = item.getAttribute('data-pmf-newsid') as string;
        modal.show();
      });
    });
    (document.getElementById('pmf-delete-news-action') as HTMLButtonElement).addEventListener(
      'click',
      async (event: Event) => {
        event.preventDefault();
        const csrfToken = (document.getElementById('pmf-csrf-token-delete') as HTMLInputElement).value;
        const id = (document.getElementById('newsId') as HTMLInputElement).value;
        const response = (await deleteNews(csrfToken, id)) as unknown as Response;
        if (typeof response.success === 'string') {
          pushNotification(response.success);
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        } else {
          pushErrorNotification(response.error);
        }
      }
    );
    document.querySelectorAll<HTMLInputElement>('#activate').forEach((item) => {
      item.addEventListener('click', async () => {
        const response = await activateNews(
          item.getAttribute('data-pmf-id') as string,
          item.checked,
          item.getAttribute('data-pmf-csrf-token') as string
        );

        if (typeof response.success === 'string') {
          pushNotification(response.success);
        } else {
          pushErrorNotification(response.error);
        }
      });
    });
  }
};

export const handleEditNews = (): void => {
  const submit = document.getElementById('submitEditNews') as HTMLButtonElement | null;
  if (submit) {
    submit.addEventListener('click', async (event: Event) => {
      event.preventDefault();
      let target = '';
      document.querySelectorAll<HTMLInputElement>('#target').forEach((item) => {
        if (item.checked) {
          target = item.value;
        }
      });

      const data: NewsData = {
        id: (document.getElementById('id') as HTMLInputElement).value,
        csrfToken: (document.getElementById('pmf-csrf-token') as HTMLInputElement).value,
        news: (document.getElementById('editor') as HTMLInputElement).value,
        newsHeader: (document.getElementById('newsheader') as HTMLInputElement).value,
        authorName: (document.getElementById('authorName') as HTMLInputElement).value,
        authorEmail: (document.getElementById('authorEmail') as HTMLInputElement).value,
        active: (document.getElementById('active') as HTMLInputElement).checked,
        comment: (document.getElementById('comment') as HTMLInputElement).checked,
        link: (document.getElementById('link') as HTMLInputElement).value,
        linkTitle: (document.getElementById('linkTitle') as HTMLInputElement).value,
        langTo: (document.getElementById('langTo') as HTMLInputElement).value,
        target: target,
      };

      const reponse = (await updateNews(data)) as unknown as Response;
      if (typeof reponse.success === 'string') {
        pushNotification(reponse.success);
        setTimeout(() => {
          window.location.href = './news';
        }, 3000);
      } else {
        pushErrorNotification(reponse.error);
      }
    });
  }
};
