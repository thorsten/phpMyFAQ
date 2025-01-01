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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-20
 */

import { activateNews, addNews, deleteNews, updateNews } from '../api';
import { Modal } from 'bootstrap';

export const handleAddNews = () => {
  const submit = document.getElementById('submitAddNews');
  if (submit) {
    submit.addEventListener('click', async (event) => {
      event.preventDefault();
      let target = '';
      document.querySelectorAll('#target').forEach((item) => {
        if (item.checked) {
          target = item.value;
        }
      });

      const data = {
        news: document.getElementById('editor').value,
        newsHeader: document.getElementById('newsheader').value,
        authorName: document.getElementById('authorName').value,
        authorEmail: document.getElementById('authorEmail').value,
        active: document.getElementById('active').checked,
        comment: document.getElementById('comment').checked,
        link: document.getElementById('link').value,
        linkTitle: document.getElementById('linkTitle').value,
        langTo: document.getElementById('langTo').value,
        target: target,
        csrfToken: document.getElementById('pmf-csrf-token').value,
      };
      await addNews(data);
    });
  }
};

export const handleNews = () => {
  const deleteNewsButton = document.getElementById('deleteNews');
  if (deleteNewsButton) {
    document.querySelectorAll('#deleteNews').forEach((item) => {
      item.addEventListener('click', (event) => {
        event.preventDefault();
        const modal = new Modal(document.getElementById('confirmDeleteNewsModal'));
        document.getElementById('newsId').value = item.getAttribute('data-pmf-newsid');
        modal.show();
      });
    });
    document.getElementById('pmf-delete-news-action').addEventListener('click', async (event) => {
      event.preventDefault();
      const csrfToken = document.getElementById('pmf-csrf-token-delete').value;
      const id = document.getElementById('newsId').value;
      await deleteNews(csrfToken, id);
    });
    document.querySelectorAll('#activate').forEach((item) => {
      item.addEventListener('click', async () => {
        await activateNews(item.getAttribute('data-pmf-id'), item.checked, item.getAttribute('data-pmf-csrf-token'));
      });
    });
  }
};

export const handleEditNews = () => {
  const submit = document.getElementById('submitEditNews');
  if (submit) {
    submit.addEventListener('click', async (event) => {
      event.preventDefault();
      let target = '';
      document.querySelectorAll('#target').forEach((item) => {
        if (item.checked) {
          target = item.value;
        }
      });

      const data = {
        id: document.getElementById('id').value,
        csrfToken: document.getElementById('pmf-csrf-token').value,
        news: document.getElementById('editor').value,
        newsHeader: document.getElementById('newsheader').value,
        authorName: document.getElementById('authorName').value,
        authorEmail: document.getElementById('authorEmail').value,
        active: document.getElementById('active').checked,
        comment: document.getElementById('comment').checked,
        link: document.getElementById('link').value,
        linkTitle: document.getElementById('linkTitle').value,
        langTo: document.getElementById('langTo').value,
        target: target,
      };

      await updateNews(data);
    });
  }
};
