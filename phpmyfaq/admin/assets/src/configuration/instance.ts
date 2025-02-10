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
import { InstanceResponse, Response } from '../interfaces';
import { addInstance, deleteInstance } from '../api';

export const handleInstances = (): void => {
  const addInstanceButton = document.querySelector('.pmf-instance-add') as HTMLElement;
  const deleteInstanceButton = document.querySelectorAll('.pmf-instance-delete') as NodeListOf<HTMLElement>;
  const container = document.getElementById('pmf-modal-add-instance') as HTMLElement;

  if (addInstanceButton) {
    const modal = new Modal(container);
    addInstanceButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const csrf = (document.querySelector('#pmf-csrf-token') as HTMLInputElement).value as string;
      const url = (document.querySelector('#url') as HTMLInputElement).value as string;
      const instance = (document.querySelector('#instance') as HTMLInputElement).value as string;
      const comment = (document.querySelector('#comment') as HTMLInputElement).value as string;
      const email = (document.querySelector('#email') as HTMLInputElement).value as string;
      const admin = (document.querySelector('#admin') as HTMLInputElement).value as string;
      const password = (document.querySelector('#password') as HTMLInputElement).value as string;

      try {
        const response = (await addInstance(
          csrf,
          url,
          instance,
          comment,
          email,
          admin,
          password
        )) as unknown as InstanceResponse;

        if (response.added) {
          const table = document.querySelector('.table tbody') as HTMLElement;
          const row: Element = addElement('tr', { id: `row-instance-${response.added}` }, [
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
                  href: `./instance/edit/${response.added}`,
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
                  'data-delete-instance-id': `${response.added}`,
                  type: 'button',
                },
                [
                  addElement('i', {
                    ariaHidden: true,
                    classList: 'bi bi-trash',
                    'data-delete-instance-id': `${response.added}`,
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
        const table = document.querySelector('.table') as Element;
        table.insertAdjacentElement(
          'afterend',
          addElement('div', { classList: 'alert alert-danger', innerText: error })
        );
      }
    });
  }

  if (deleteInstanceButton) {
    deleteInstanceButton.forEach((element: HTMLElement): void => {
      element.addEventListener('click', async (event: Event): Promise<void> => {
        event.preventDefault();

        const instanceId = (event.target as HTMLElement).getAttribute('data-delete-instance-id') as string;
        const csrf = (event.target as HTMLElement).getAttribute('data-csrf-token') as string;

        if (confirm('Are you sure?')) {
          try {
            const response = (await deleteInstance(csrf, instanceId)) as unknown as InstanceResponse;

            if (response.deleted) {
              const row = document.getElementById(`row-instance-${response.deleted}`) as HTMLElement;
              row.addEventListener('click', (): string => (row.style.opacity = '0'));
              row.addEventListener('transitionend', (): void => row.remove());
            } else {
              throw new Error('Network response was not ok');
            }
          } catch (error) {
            const table = document.querySelector('.table') as HTMLElement;
            const errorMessage = (await (error as any).cause.response.json()) as Response;
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
