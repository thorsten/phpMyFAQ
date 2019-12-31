/**
 * Setup functions
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-12-24
 */

/*global $: false */

$(document).ready(function() {
  'use strict';

  const setupType = $('#sql_type'),
    setupTypeOptions = $('#sql_type option'),
    setupDatabasePort = document.getElementById('sql_port'),
    $dbSqlite = $('#dbsqlite'),
    $dbFull = $('#dbdatafull');
  let lastIpSegment = 2;

  const selectDatabaseSetup = event => {
    switch (event.target.value) {
      case 'mysqli':
        setupDatabasePort.value = 3306;
        $dbSqlite.hide();
        $dbFull.show();
        break;
      case 'pgsql':
        setupDatabasePort.value = 5432;
        $dbSqlite.hide();
        $dbFull.show();
        break;
      case 'sqlsrv':
        setupDatabasePort.value = 1433;
        $dbSqlite.hide();
        $dbFull.show();
        break;
      case 'sqlite3':
        $dbSqlite.show();
        $dbFull.hide();
        break;
      default:
        $dbSqlite.hide();
        $dbFull.show();
        break;
    }
  };

  const addElasticsearchServerInput = event => {
    const current = $(event.currentTarget);
    if ('add' === current.attr('data-action')) {
      const wrapper = document.querySelector('#elasticsearch_server-wrapper');
      const div = document.createElement('div');
      div.className = 'input-group';
      const input = document.createElement('input');
      input.className = 'form-control';
      input.className += ' mt-1';
      input.type = 'text';
      input.name = 'elasticsearch_server[]';
      input.placeholder = `127.0.0.${lastIpSegment++}:9200`;
      div.appendChild(input);
      wrapper.append(div);
    }
    return false;
  };

  $('#phpmyfaq-setup-form a.pmf-add-elasticsearch-host').on('click', addElasticsearchServerInput);
  setupType.on('change', selectDatabaseSetup);

  if (setupTypeOptions.length === 1 && setupType.val() === 'sqlite3') {
    $dbSqlite.show().removeClass('d-none');
    $dbFull.hide();
  }
});
