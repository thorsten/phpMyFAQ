/**
 * Setup functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-24
 */

import { insertAfter } from '../utils';

export const selectDatabaseSetup = (event) => {
  const form = document.getElementById('phpmyfaq-setup-form');
  const inputs = form.getElementsByTagName('input');
  const database = document.getElementById('dbdatafull');
  const databasePort = document.getElementById('sql_port');
  const sqlite = document.getElementById('dbsqlite');

  if (event.target.value === 'sqlite3') {
    for (let i = 0; i < inputs.length; i++) {
      inputs[i].removeAttribute('required');
    }
  } else {
    document.getElementById('sql_server')?.setAttribute('required', 'required');
    document.getElementById('sql_port')?.setAttribute('required', 'required');
    document.getElementById('sql_user')?.setAttribute('required', 'required');
    document.getElementById('faqpassword')?.setAttribute('required', 'required');
    document.getElementById('sql_db')?.setAttribute('required', 'required');
  }

  switch (event.target.value) {
    case 'mysqli':
      databasePort.value = 3306;
      sqlite.className = 'd-none';
      database.className = 'd-block';
      break;
    case 'pgsql':
      databasePort.value = 5432;
      sqlite.className = 'd-none';
      database.className = 'd-block';
      break;
    case 'sqlsrv':
      databasePort.value = 1433;
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

export const addElasticsearchServerInput = () => {
  const wrapper = document.getElementById('elasticsearch-server-wrapper');
  const input = document.createElement('input');

  // Set attributes for input
  input.className = 'form-control';
  input.className += ' mt-1';
  input.type = 'text';
  input.name = 'elasticsearch_server[]';
  input.placeholder = `127.0.0.1:9200`;

  insertAfter(wrapper, input);
};

export const stepIndicator = (step) => {
  // This function removes the "active" class of all steps...
  let i,
    steps = document.getElementsByClassName('stepIndicator');
  for (i = 0; i < steps.length; i++) {
    steps[i].className = steps[i].className.replace(' active', '');
  }
  //... and adds the "active" class on the current step:
  steps[step].className += ' active';
};
