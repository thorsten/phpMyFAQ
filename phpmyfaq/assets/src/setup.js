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
import { handlePasswordToggle } from './utils';

export const selectDatabaseSetup = (event) => {
  const database = document.getElementById('dbdatafull');
  const databasePort = document.getElementById('sql_port');
  const sqlite = document.getElementById('dbsqlite');

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

const stepIndicator = (step) => {
  // This function removes the "active" class of all steps...
  let i,
    steps = document.getElementsByClassName('stepIndicator');
  for (i = 0; i < steps.length; i++) {
    steps[i].className = steps[i].className.replace(' active', '');
  }
  //... and adds the "active" class on the current step:
  steps[step].className += ' active';
};

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // Toggle password visibility
  handlePasswordToggle();

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
  let currentTab = 0;
  const nextButton = document.getElementById('nextBtn');
  const prevButton = document.getElementById('prevBtn');
  if (nextButton) {
    nextButton.addEventListener('click', (event) => {
      event.preventDefault();
      nextPrev(1);
    });
  }
  if (prevButton) {
    prevButton.addEventListener('click', (event) => {
      event.preventDefault();
      nextPrev(-1);
    });
  }
  showTab(currentTab);

  function showTab(n) {
    const currentStep = document.getElementsByClassName('step');

    currentStep[n].style.display = 'block';

    const prevButton = document.getElementById('prevBtn');
    const nextButton = document.getElementById('nextBtn');
    if (n === 0) {
      prevButton.style.display = 'none';
    } else {
      prevButton.style.display = 'inline';
    }
    if (n === currentStep.length - 1) {
      nextButton.innerHTML = 'Submit';
    } else {
      nextButton.innerHTML = 'Next';
    }
    stepIndicator(n);
  }

  const nextPrev = (n) => {
    const currentStep = document.getElementsByClassName('step');

    if (n === 1 && !validateForm()) {
      return false;
    }

    currentStep[currentTab].style.display = 'none';
    currentTab = currentTab + n;
    if (currentTab >= currentStep.length) {
      document.getElementById('phpmyfaq-setup-form').submit();
      return false;
    }
    showTab(currentTab);
  };

  const validateForm = () => {
    let currentStep,
      y,
      i,
      valid = true;
    currentStep = document.getElementsByClassName('step');
    y = currentStep[currentTab].querySelectorAll('input,select');

    for (i = 0; i < y.length; i++) {
      if (y[i].value === '' && y[i].hasAttribute('required')) {
        y[i].className += ' is-invalid';
        // and set the current valid status to false
        valid = false;
      }
    }
    // If the valid status is true, mark the step as finished and valid:
    if (valid) {
      document.getElementsByClassName('stepIndicator')[currentTab].className += ' finish';
    }

    return valid; // return the valid status
  };

  const resetValidateForm = () => {
    let currentStep, y, i;
    currentStep = document.getElementsByClassName('step');
    y = currentStep[currentTab].getElementsByTagName('input');

    for (i = 0; i < y.length; i++) {
      if (y[i].value === '' && y[i].hasAttribute('required')) {
        y[i].className -= ' is-invalid';
      }
    }
  };
});
