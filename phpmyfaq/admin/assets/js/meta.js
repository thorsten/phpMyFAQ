
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
            '<a href="javascript:;" id="delete-META-' + data.added +
            '" class="btn btn-danger pmf-meta-delete"><i class="material-icons">delete</i></a>' +
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
    const csrf = this.getAttribute('data-csrf-token');

    if (confirm('Are you sure?')) {
      $.get('index.php',
        { action: 'ajax', ajax: 'config', ajaxaction: 'delete_meta', meta_id: id, csrf: csrf },
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