/**
 * JavaScript functions for all FAQ section administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-10
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // Add meta entry
  $('.pmf-meta-add').on('click', (event) => {
    event.preventDefault();
    const csrf = $('#csrf').val();
    const pageId = $('#page_id').val();
    const type = $('#type').val();
    const content = $('#meta-content').val();

    $.get(
      'index.php',
      {
        action: 'ajax',
        ajax: 'config',
        ajaxaction: 'add_meta',
        csrf: csrf,
        page_id: pageId,
        type: type,
        content: content,
      },
      (data) => {
        if (typeof data.added === 'undefined') {
          $('.table').after('<div class="alert alert-danger">Could not add meta data</div>');
        } else {
          $('.modal').modal('hide');
          $('.table tbody').append(
            '<tr id="row-instance-' +
              data.added +
              '">' +
              '<td>' +
              data.added +
              '</td>' +
              '<td>' +
              escape(pageId) +
              '</td>' +
              '<td>' +
              type +
              '</td>' +
              '<td>' +
              escape(content) +
              '</td>' +
              '<td>' +
              '<a href="?action=meta.edit&id=' +
              data.added +
              '" class="btn btn-success">' +
              '<i aria-hidden="true" class="fa fa-pencil"></i>' +
              '</a>' +
              ' <a href="#" id="delete-meta-' +
              data.added +
              '" class="btn btn-danger pmf-meta-delete"><i aria-hidden="true" class="fa fa-trash"></i></a>' +
              ' <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#codeModal"' +
              ' data-code-snippet="' +
              escape(pageId) +
              '">' +
              '<i aria-hidden="true" class="fa fa-code"></i>' +
              '</button>' +
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
      $.get(
        'index.php',
        { action: 'ajax', ajax: 'config', ajaxaction: 'delete_meta', meta_id: id, csrf: csrf },
        function (data) {
          if (typeof data.deleted === 'undefined') {
            $('.table').after('<div class="alert alert-danger">Could not add meta data</div>');
          } else {
            $('#row-meta-' + id).fadeOut('slow');
          }
        },
        'json'
      );
    }
  });

  $('#codeModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const codeSnippet = button.data('code-snippet');
    const modal = $(this);
    modal.find('.modal-body textarea').val('{{ ' + codeSnippet + ' | meta }}');
  });

  const escape = (text) => {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };

    return text.replace(/[&<>"']/g, (mapped) => {
      return map[mapped];
    });
  };
});
