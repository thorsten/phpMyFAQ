/**
 * Setup functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-12-24
 */

import { insertAfter } from './utils';

export const selectDatabaseSetup = (event) => {
  const database = document.getElementById('dbdatafull');
  const databasePort = document.getElementById('sql_port');
  const sqlite = document.getElementById('dbsqlite');

  switch (event.target.value) {
    case 'mysqli':
      databasePort.value = 3306;
      sqlite.style.display = 'none';
      database.style.display = 'block';
      break;
    case 'pgsql':
      databasePort.value = 5432;
      sqlite.style.display = 'none';
      database.style.display = 'block';
      break;
    case 'sqlsrv':
      databasePort.value = 1433;
      sqlite.style.display = 'none';
      database.style.display = 'block';
      break;
    case 'sqlite3':
      sqlite.style.display = 'block';
      database.style.display = 'none';
      break;
    default:
      sqlite.style.display = 'none';
      database.style.display = 'block';
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

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // Switch between database selection
  const setupType = document.getElementById('sql_type');
  if (setupType) {
    setupType.addEventListener('change', selectDatabaseSetup);
  }

  // Add more Elasticsearch server inputs
  const addElasticsearch = document.getElementById('pmf-add-elasticsearch-host');
  if (addElasticsearch) {
    addElasticsearch.addEventListener('click', addElasticsearchServerInput);
  }

  // Wizard
  const setupTabs = document.querySelectorAll('.setup-content');
  const navListItems = document.querySelectorAll('div.setup-panel div a');
  const nextButtons = document.querySelectorAll('.btn-next');

  setupTabs.forEach((setupContent) => {
    setupContent.style.display = 'none';
  });

  navListItems.forEach((navListItem) => {
    navListItem.addEventListener('click', (event) => {
      event.preventDefault();

      const target = event.target.getAttribute('href');
      const item = event.target;
      const setupContent = document.getElementById(target.replace('#', ''));

      if (item.getAttribute('disabled') === null || item.getAttribute('disabled') === 'disabled') {
        navListItem.classList.remove('btn-primary');
        navListItem.classList.add('btn-secondary');
        item.classList.remove('btn-secondary');
        item.classList.add('btn-primary');
        item.setAttribute('disabled', '');
        if (setupContent) {
          setupContent.style.display = 'block';
        }
      }
    });
  });

  nextButtons.forEach((nextButton) => {
    nextButton.addEventListener('click', (event) => {
      event.preventDefault();

      const currentStep = event.target.getAttribute('data-pmf-current-step');
      const nextStep = event.target.getAttribute('data-pmf-next-step');

      const currentSetupContent = document.getElementById(currentStep);
      const nextSetupContent = document.getElementById(nextStep);

      const currentInputs = currentSetupContent.querySelectorAll(
        'input[type="text"],input[type="url"],input[type="email"],input[type="number"],input[type="password"]'
      );
      let isValid = true;

      if (currentInputs) {
        currentInputs.forEach((input) => {
          input.classList.remove('is-invalid');
        });

        currentInputs.forEach((input) => {
          if (!input.validity.valid) {
            isValid = false;
            input.classList.add('is-invalid');
          }
        });
      }

      if (isValid && nextStep === 'submit') {
        event.stopPropagation();
        document.forms['phpmyfaq-setup-form'].submit();
      }

      if (isValid && nextStep !== 'submit') {
        currentSetupContent.style.display = 'none';
        nextSetupContent.style.display = 'block';
        document.querySelector(`div.setup-panel div a[href="#${nextStep}"]`).dispatchEvent(new Event('click'));
      }
    });
  });

  document.querySelector('div.setup-panel div a.btn-primary').dispatchEvent(new Event('click'));
});
