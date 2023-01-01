/**
 * Admin template metadata configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-11
 */

import { Modal } from 'bootstrap';
import { addElement, escape } from '../../../../assets/src/utils';

export const handleTemplateMetaData = () => {
  const addMetaDataButton = document.querySelector('.pmf-meta-add');
  const deleteMetaDataButton = document.querySelectorAll('.pmf-meta-delete');
  const codeModal = document.querySelector('#codeModal');

  const container = document.getElementById('addMetaModal');

  // Add template metadata
  if (addMetaDataButton) {
    const modal = new Modal(container);
    addMetaDataButton.addEventListener('click', (event) => {
      event.preventDefault();

      const csrfToken = document.getElementById('csrf').value;
      const pageId = document.getElementById('page_id').value;
      const type = document.getElementById('type').value;
      const content = document.getElementById('meta-content').value;

      fetch('index.php?action=ajax&ajax=config&ajaxaction=add-template-metadata', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: csrfToken,
          pageId: pageId,
          type: type,
          content: content,
        }),
      })
        .then(async (response) => {
          if (response.status === 200) {
            return response.json();
          }
          throw new Error('Network response was not ok.');
        })
        .then((response) => {
          const table = document.querySelector('.table tbody');
          const row = addElement('tr', { id: `row-meta-${response.added}` }, [
            addElement('td', { innerText: response.added }),
            addElement('td', { innerText: escape(pageId) }),
            addElement('td', { innerText: escape(type) }),
            addElement('td', { innerText: content }),
            addElement('td', {}, [
              addElement(
                'a',
                {
                  href: `?action=meta.edit&id=${response.added}`,
                  classList: 'btn btn-sm btn-success',
                },
                [addElement('i', { classList: 'fa fa-pencil', ariaHidden: true })]
              ),
              addElement(
                'a',
                {
                  href: '#',
                  id: `delete-meta-${response.added}`,
                  classList: 'btn btn-sm btn-danger pmf-meta-delete',
                },
                [addElement('i', { classList: 'fa fa-trash', ariaHidden: true })]
              ),
              addElement(
                'button',
                {
                  classList: 'btn btn-sm btn-primary',
                  'data-bs-toggle': 'modal',
                  'data-bs-target': '#codeModal',
                  'data-code-snippet': escape(pageId),
                  type: 'button',
                },
                [
                  addElement('i', {
                    ariaHidden: true,
                    classList: 'fa fa-code',
                  }),
                ]
              ),
            ]),
          ]);

          table.appendChild(row);
          modal.hide();
        })
        .catch((error) => {
          const table = document.querySelector('.table');
          table.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: error })
          );
        });
    });

    // delete template metadata
    deleteMetaDataButton.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const metaId = event.target.getAttribute('data-delete-meta-id');
        const csrf = event.target.getAttribute('data-csrf-token');

        if (confirm('Are you sure?')) {
          fetch('index.php?action=ajax&ajax=config&ajaxaction=delete-template-metadata', {
            method: 'POST',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: csrf,
              metaId: metaId,
            }),
          })
            .then(async (response) => {
              if (response.status === 200) {
                return response.json();
              }
              throw new Error('Network response was not ok.');
            })
            .then((response) => {
              const row = document.getElementById(`row-meta-${response.deleted}`);
              row.addEventListener('click', () => (row.style.opacity = '0'));
              row.addEventListener('transitionend', () => row.remove());
            })
            .catch((error) => {
              const table = document.querySelector('.table');
              table.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-danger', innerText: error })
              );
            });
        }
      });
    });

    // handle code snippet modal
    codeModal.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      const codeSnippet = button.getAttribute('data-code-snippet');
      const modal = event.target;

      modal.querySelector('.modal-body textarea').value = `{{ ${codeSnippet} | meta }}`;
    });
  }
};
