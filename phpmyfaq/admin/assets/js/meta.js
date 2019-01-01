/**
 * JavaScript functions for all FAQ section administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-10
 */

/*global $:false */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // Add meta entry
  $('.pmf-meta-add').on('click', (event) => {
    event.preventDefault();
    const csrf = $('#csrf').val();
    const pageId = $('#page_id').val();
    const type = $('#type').val();
    const content = $('#content').val();

    $.get('index.php',
      {
        action: 'ajax', ajax: 'config', ajaxaction: 'add_meta', csrf: csrf, page_id: pageId, type: type, content: content
      },
      (data) => {
        if (typeof(data.added) === 'undefined') {
          $('.table').after(
            '<div class="alert alert-danger">Could not add meta data</div>'
          );
        } else {
          $('.modal').modal('hide');
          $('.table tbody').append(
            '<tr id="row-instance-' + data.added + '">' +
            '<td>' + data.added + '</td>' +
            '<td>' + pageId + '</td>' +
            '<td>' + type + '</td>' +
            '<td>' + content +'</td>' +
            '<td>' +
            '<a href="?action=meta.edit&id='+ data.added + '" class="btn btn-success">' +
            '<i aria-hidden="true" class="fas fa-pencil"></i>' +
            '</a>' +
            '<a href="javascript:;" id="delete-meta-' + data.added +
            '" class="btn btn-danger pmf-meta-delete"><i aria-hidden="true" class="fas fa-trash"></i></a>' +
            '</td>' +
            '</tr>'
          );
        }
      },
      'json'
    );
  });

  // Delete meta data
  $('.pmf-meta-delete').on('click', (event) => {
    event.preventDefault();
    const targetId = event.target.id.split('-');
    const id = targetId[2];
    const csrf = event.target.dataset.csrf;

    if (confirm('Are you sure?')) {
      $.get('index.php',
        { action: 'ajax', ajax: 'config', ajaxaction: 'delete_meta', 'meta_id': id, csrf: csrf },
        function(data) {
          if (typeof(data.deleted) === 'undefined') {
            $('.table').after(
              '<div class="alert alert-danger">Could not add meta data</div>'
          );
          } else {
            $('#row-meta-' + id).fadeOut('slow');
          }
        },
        'json'
      );
    }
  });
});
