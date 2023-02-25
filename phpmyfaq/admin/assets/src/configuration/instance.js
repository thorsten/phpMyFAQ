/**
 * Multi Instance Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-28
 */

import { Modal } from 'bootstrap';
import { addElement } from '../../../../assets/src/utils';

export const handleInstances = () => {
  const addInstance = document.querySelector('.pmf-instance-add');
  const deleteInstance = document.querySelectorAll('.pmf-instance-delete');
  const container = document.getElementById('pmf-modal-add-instance');

  if (addInstance) {
    const modal = new Modal(container);
    addInstance.addEventListener('click', (event) => {
      event.preventDefault();
      const csrf = document.querySelector('#csrf').value;
      const url = document.querySelector('#url').value;
      const instance = document.querySelector('#instance').value;
      const comment = document.querySelector('#comment').value;
      const email = document.querySelector('#email').value;
      const admin = document.querySelector('#admin').value;
      const password = document.querySelector('#password').value;

      fetch('index.php?action=ajax&ajax=config&ajaxaction=add-instance', {
        method: 'POST',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          csrf: csrf,
          url: url,
          instance: instance,
          comment: comment,
          email: email,
          admin: admin,
          password: password,
        }),
      })
        .then(async (response) => {
          if (response.status === 200) {
            return response.json();
          }
          throw new Error('Network response was not ok: ', { cause: { response } });
        })
        .then((response) => {
          const table = document.querySelector('.table tbody');
          const row = addElement('tr', { id: `row-instance-${response.added}` }, [
            addElement('td', { innerText: response.added }),
            addElement('td', {}, [
              addElement('a', {
                href: response.url,
                target: '_blank',
                innerText: response.url,
              }),
            ]),
            addElement('td', { innerText: instance }),
            addElement('td', { innerText: comment }),
            addElement('td', {}, [
              addElement(
                'a',
                {
                  href: `?action=edit-instance&instance_id=${response.added}`,
                  classList: 'btn btn-info',
                },
                [addElement('i', { classList: 'fa fa-pencil', ariaHidden: true })]
              ),
            ]),
            addElement('td', {}, [
              addElement(
                'button',
                {
                  classList: 'btn btn-danger',
                  'data-delete-instance-id': `${response.added}`,
                  type: 'button',
                },
                [
                  addElement('i', {
                    ariaHidden: true,
                    classList: 'fa fa-trash',
                    'data-delete-instance-id': `${response.added}`,
                  }),
                ]
              ),
            ]),
          ]);
          table.appendChild(row);
          modal.hide();
        })
        .catch(async (error) => {
          const table = document.querySelector('.table');
          const errorMessage = await error.cause.response.json();
          table.insertAdjacentElement(
            'afterend',
            addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
          );
        });
    });
  }

  if (deleteInstance) {
    deleteInstance.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const instanceId = event.target.getAttribute('data-delete-instance-id');
        const csrf = event.target.getAttribute('data-csrf-token');

        if (confirm('Are you sure?')) {
          fetch('index.php?action=ajax&ajax=config&ajaxaction=delete-instance', {
            method: 'POST',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: csrf,
              instanceId: instanceId,
            }),
          })
            .then(async (response) => {
              if (response.status === 200) {
                return response.json();
              }
              throw new Error('Network response was not ok: ', { cause: { response } });
            })
            .then((response) => {
              const row = document.getElementById(`row-instance-${response.deleted}`);
              row.addEventListener('click', () => (row.style.opacity = '0'));
              row.addEventListener('transitionend', () => row.remove());
            })
            .catch(async (error) => {
              const table = document.querySelector('.table');
              const errorMessage = await error.cause.response.json();
              table.insertAdjacentElement(
                'afterend',
                addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
              );
            });
        }
      });
    });
  }
};
