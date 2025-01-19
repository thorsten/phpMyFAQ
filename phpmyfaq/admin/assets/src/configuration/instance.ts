/**
 * Multi Instance Handling
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-02-28
 */

import { Modal } from 'bootstrap';
import { addElement } from '../../../../assets/src/utils';

interface InstanceResponse {
  added: string;
  url: string;
  deleted: string;
}

export const handleInstances = (): void => {
  const addInstance = document.querySelector('.pmf-instance-add') as HTMLElement;
  const deleteInstance = document.querySelectorAll('.pmf-instance-delete') as NodeListOf<HTMLElement>;
  const container = document.getElementById('pmf-modal-add-instance') as HTMLElement;

  if (addInstance) {
    const modal = new Modal(container);
    addInstance.addEventListener('click', async (event) => {
      event.preventDefault();
      const csrf = (document.querySelector('#pmf-csrf-token') as HTMLInputElement).value;
      const url = (document.querySelector('#url') as HTMLInputElement).value;
      const instance = (document.querySelector('#instance') as HTMLInputElement).value;
      const comment = (document.querySelector('#comment') as HTMLInputElement).value;
      const email = (document.querySelector('#email') as HTMLInputElement).value;
      const admin = (document.querySelector('#admin') as HTMLInputElement).value;
      const password = (document.querySelector('#password') as HTMLInputElement).value;

      try {
        const response = await fetch('./api/instance/add', {
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
        });

        if (response.status === 200) {
          const responseData: InstanceResponse = await response.json();
          const table = document.querySelector('.table tbody') as HTMLElement;
          const row = addElement('tr', { id: `row-instance-${responseData.added}` }, [
            addElement('td', { innerText: responseData.added }),
            addElement('td', {}, [
              addElement('a', {
                href: responseData.url,
                target: '_blank',
                innerText: responseData.url,
              }),
            ]),
            addElement('td', { innerText: instance }),
            addElement('td', { innerText: comment }),
            addElement('td', {}, [
              addElement(
                'a',
                {
                  href: `./instance/edit/${responseData.added}`,
                  classList: 'btn btn-info',
                },
                [addElement('i', { classList: 'bi bi-pencil', ariaHidden: true })]
              ),
            ]),
            addElement('td', {}, [
              addElement(
                'button',
                {
                  classList: 'btn btn-danger',
                  'data-delete-instance-id': `${responseData.added}`,
                  type: 'button',
                },
                [
                  addElement('i', {
                    ariaHidden: true,
                    classList: 'bi bi-trash',
                    'data-delete-instance-id': `${responseData.added}`,
                  }),
                ]
              ),
            ]),
          ]);
          table.appendChild(row);
          modal.hide();
        } else {
          throw new Error('Network response was not ok');
        }
      } catch (error) {
        const table = document.querySelector('.table') as HTMLElement;
        const errorMessage = await (error as any).cause.response.json();
        table.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
        );
      }
    });
  }

  if (deleteInstance) {
    deleteInstance.forEach((element) => {
      element.addEventListener('click', async (event) => {
        event.preventDefault();

        const instanceId = (event.target as HTMLElement).getAttribute('data-delete-instance-id') as string;
        const csrf = (event.target as HTMLElement).getAttribute('data-csrf-token') as string;

        if (confirm('Are you sure?')) {
          try {
            const response = await fetch('./api/instance/delete', {
              method: 'POST',
              headers: {
                Accept: 'application/json, text/plain, */*',
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                csrf: csrf,
                instanceId: instanceId,
              }),
            });

            if (response.status === 200) {
              const responseData: InstanceResponse = await response.json();
              const row = document.getElementById(`row-instance-${responseData.deleted}`) as HTMLElement;
              row.addEventListener('click', () => (row.style.opacity = '0'));
              row.addEventListener('transitionend', () => row.remove());
            } else {
              throw new Error('Network response was not ok');
            }
          } catch (error) {
            const table = document.querySelector('.table') as HTMLElement;
            const errorMessage = await (error as any).cause.response.json();
            table.insertAdjacentElement(
              'afterend',
              addElement('div', { classList: 'alert alert-danger', innerText: errorMessage.error })
            );
          }
        }
      });
    });
  }
};
