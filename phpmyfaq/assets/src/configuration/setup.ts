/**
 * Setup functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-24
 */

import { insertAfter } from '../utils';

export const selectDatabaseSetup = (event: Event): void => {
  const form = document.getElementById('phpmyfaq-setup-form') as HTMLFormElement;
  const inputs = form.getElementsByTagName('input') as HTMLCollectionOf<HTMLInputElement>;
  const database = document.getElementById('dbdatafull') as HTMLElement;
  const databasePort = document.getElementById('sql_port') as HTMLInputElement;
  const sqlite = document.getElementById('dbsqlite') as HTMLElement;

  const target = event.target as HTMLSelectElement;

  if (target.value === 'sqlite3') {
    for (let i: number = 0; i < inputs.length; i++) {
      inputs[i].removeAttribute('required');
    }
  } else {
    document.getElementById('sql_server')?.setAttribute('required', 'required');
    document.getElementById('sql_port')?.setAttribute('required', 'required');
    document.getElementById('sql_user')?.setAttribute('required', 'required');
    document.getElementById('faqpassword')?.setAttribute('required', 'required');
    document.getElementById('sql_db')?.setAttribute('required', 'required');
  }

  switch (target.value) {
    case 'mysqli':
      databasePort.value = '3306';
      sqlite.className = 'd-none';
      database.className = 'd-block';
      break;
    case 'pgsql':
      databasePort.value = '5432';
      sqlite.className = 'd-none';
      database.className = 'd-block';
      break;
    case 'sqlsrv':
      databasePort.value = '1433';
      sqlite.className = 'd-none';
      database.className = 'd-block';
      break;
    case 'sqlite3':
      sqlite.className = 'd-block';
      database.className = 'd-none';
      break;
    default:
      sqlite.className = 'd-none';
      database.className = 'd-block';
      break;
  }
};

export const addElasticsearchServerInput = (): void => {
  const wrapper = document.getElementById('elasticsearch-server-wrapper') as HTMLElement;
  const input = document.createElement('input') as HTMLInputElement;

  // Set attributes for input
  input.className = 'form-control mt-1';
  input.type = 'text';
  input.name = 'elasticsearch_server[]';
  input.placeholder = '127.0.0.1:9200';

  insertAfter(wrapper, input);
};

export const stepIndicator = (step: number): void => {
  // This function removes the "active" class of all steps...
  const steps = document.getElementsByClassName('stepIndicator') as HTMLCollectionOf<HTMLElement>;
  for (let i: number = 0; i < steps.length; i++) {
    steps[i].className = steps[i].className.replace(' active', '');
  }
  //... and adds the "active" class on the current step:
  steps[step].className += ' active';
};
