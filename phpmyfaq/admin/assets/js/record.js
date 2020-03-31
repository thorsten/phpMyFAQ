/**
 * JavaScript functions for all FAQ record administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2013-11-17
 */

$(function() {
  // Show help for keywords and users
  $('#keywords').on('focus', () => {
    showHelp('keywords');
  });
  $('#tags').on('focus', () => {
    showHelp('tags');
  });

  let categories = $('#phpmyfaq-categories option:selected')
    .map(function() {
      return $(this).val();
    })
    .get();

  getCategoryPermissions(categories);

  // Override FAQ permissions with Category permission to avoid confused users
  $('#phpmyfaq-categories').on('click', () => {
    categories = $('#phpmyfaq-categories option:selected')
      .map(function() {
        return $(this).val();
      })
      .get();
    getCategoryPermissions(categories);
  });

  const faqId = document.getElementById('record_id').value;
  if (faqId > 0) {
    getFaqPermissions(faqId);
  }
});

function getCategoryPermissions(categories) {
  fetch(`index.php?action=ajax&ajax=categories&ajaxaction=getpermissions&categories=${categories}`)
    .then(response => {
      return response.json();
    })
    .then(permissions => {
      setPermissions(permissions);
    });
}

function getFaqPermissions(faqId) {
  const csrfToken = document.getElementById('csrf').value;
  fetch(`index.php?action=ajax&ajax=records&ajaxaction=permissions&faq-id=${faqId}&csrf=${csrfToken}`)
    .then(response => {
      return response.json();
    })
    .then(permissions => {
      setPermissions(permissions);
    });
}

function setPermissions(permissions) {
  const perms = permissions;

  // Users
  if (-1 === parseInt(perms.user[0])) {
    $('#restrictedusers').prop('checked', false);
    $('#allusers').prop('checked', true);
  } else {
    $('#allusers').prop('checked', false);
    $('#restrictedusers').prop('checked', true);
    $.each(perms.user, function(key, value) {
      $(".selected-users option[value='" + value + "']").prop('selected', true);
    });
  }

  // Groups
  if (-1 === parseInt(perms.group[0])) {
    $('#restrictedgroups')
      .prop('checked', false)
      .prop('disabled', false);
    $('#allgroups')
      .prop('checked', true)
      .prop('disabled', false);
  } else {
    $('#allgroups')
      .prop('checked', false)
      .prop('disabled', true);
    $('#restrictedgroups')
      .prop('checked', true)
      .prop('disabled', false);
    $.each(perms.group, function(key, value) {
      $("#selected-groups option[value='" + value + "']").prop('selected', true);
    });
  }
}

function showHelp(option) {
  const optionHelp = $('#' + option + 'Help');
  optionHelp.removeClass('hide');
  optionHelp.fadeOut(2500);
}

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  /** Initialize the custom file input plugin */
  bsCustomFileInput.init();

  /** File upload handling. */
  $('#filesToUpload').on('change', function() {
    const files = $('#filesToUpload')[0].files,
      fileSize = $('#filesize'),
      fileList = $('.pmf-attachment-upload-files');
    fileList.removeClass('invisible').append('<ul>');

    let nBytes = 0,
      nFiles = files.length;
    for (let nFileId = 0; nFileId < nFiles; nFileId++) {
      nBytes += files[nFileId].size;
      fileList.append('<li>' + files[nFileId].name + '</li>');
    }
    let sOutput = nBytes + ' bytes';
    for (
      let aMultiples = ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'], nMultiple = 0, nApprox = nBytes / 1024;
      nApprox > 1;
      nApprox /= 1024, nMultiple++
    ) {
      sOutput = nApprox.toFixed(2) + ' ' + aMultiples[nMultiple] + ' (' + nBytes + ' bytes)';
    }
    fileSize.html(sOutput);
    fileList.append('</ul>');
  });

  /** Handler for closing the modal */
  $('#pmf-attachment-modal-close').on('click', function() {
    $('#filesize').html('');
    $('.pmf-attachment-upload-files li').remove();
    $('.pmf-attachment-upload-files').addClass('invisible');
    $('#attachmentForm')[0].reset();
  });

  /** File upload via Ajax */
  $('#pmf-attachment-modal-upload').on('click', function(event) {
    event.preventDefault();
    event.stopImmediatePropagation();

    const files = $('#filesToUpload')[0].files,
      progress = $('.progress');
    const formData = new FormData();

    for (let i = 0; i < files.length; i++) {
      formData.append('filesToUpload[]', files[i]);
    }
    formData.append('record_id', $('#attachment_record_id').val());
    formData.append('record_lang', $('#attachment_record_lang').val());

    progress.removeClass('invisible');
    $.ajax({
      url: 'index.php?action=ajax&ajax=att&ajaxaction=upload',
      type: 'POST',
      xhr: function() {
        const xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener(
          'progress',
          function(event) {
            if (event.lengthComputable) {
              let percentComplete = event.loaded / event.total;
              percentComplete = parseInt(percentComplete * 100);
              $('.progress-bar').width(percentComplete);
            }
          },
          false
        );
        return xhr;
      },
      success: function(attachments) {
        attachments.forEach(function(attachment) {
          $('.adminAttachments').append(
            '<li>' +
              '<a href="../index.php?action=attachment&id=' +
              attachment.attachmentId +
              '">' +
              attachment.fileName +
              '</a>' +
              '<a class="badge badge-danger" href="?action=delatt&amp;record_id=' +
              attachment.faqId +
              '&amp;id=' +
              attachment.attachmentId +
              '&amp;lang=' +
              attachment.faqLanguage +
              '">' +
              '<i aria-hidden="true" class="fa fa-trash"></i></a>' +
              '</li>'
          );
        });
        progress.addClass('invisible');
        $('#filesize').html('');
        $('.pmf-attachment-upload-files li').remove();
        $('.pmf-attachment-upload-files').addClass('invisible');
        $('#attachmentForm')[0].reset();
        $('#attachmentModal').modal('hide');
      },
      error: function(data) {
        console.log(data);
      },
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
    });
    return false;
  });

  /** Delete questions */
  $('#submitDeleteQuestions').on('click', function() {
    const questions = $('#questionSelection').serialize(),
      indicator = $('#pmf-admin-saving-data-indicator');

    $('#returnMessage').empty();
    $.ajax({
      type: 'POST',
      url: 'index.php?action=ajax&ajax=records&ajaxaction=delete_question',
      data: questions,
      success: function(msg) {
        indicator.html('<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Deleting ...</span>');
        $('tr td input:checked')
          .parent()
          .parent()
          .parent()
          .fadeOut('slow');
        indicator.fadeOut('slow');
        $('#returnMessage').html('<p class="alert alert-success">' + msg + '</p>');
      },
    });
    return false;
  });

  $(function() {
    // set the textarea to its previous height
    const answerHeight = localStorage.getItem('textarea.answer.height'),
      answer = $('#answer-markdown');

    if (answerHeight !== 'undefined') {
      answer.height(answerHeight);
    }

    // when resized, store the textarea's height
    answer.on('mouseup', function() {
      localStorage.setItem('textarea.answer.height', $(this).height());
    });

    // on clicking the Preview tab, refresh the preview
    $('.markdown-tabs')
      .find('a')
      .on('click', function() {
        if ($(this).attr('data-markdown-tab') === 'preview') {
          $('.markdown-preview').height(answer.height());
          $.ajax({
            type: 'POST',
            url: 'index.php?action=ajax&ajax=markdown',
            data: 'text=' + answer.val(),
            success: data => {
              $('.markdown-preview').html(data);
            },
          });
        }
      });
  });

  // Typeahead
  $('.pmf-tags-autocomplete').typeahead({
    autoSelect: true,
    delay: 300,
    fitToElement: true,
    minLength: 1,
    showHintOnFocus: 'all',
    source: (request, response) => {
      const tags = $('#tags');
      let currentTags = tags.data('tag-list');

      if (currentTags.length > 0) {
        request = request.substr(currentTags.length + 1, request.length);
      }
      $.ajax({
        url: 'index.php?action=ajax&ajax=tags&ajaxaction=list',
        type: 'GET',
        dataType: 'JSON',
        data: 'q=' + request.trim(),
        success: data => {
          response(
            data.map(tags => {
              return {
                tagName: tags.tagName,
              };
            })
          );
        },
      });
    },
    displayText: tags => {
      return typeof tags !== 'undefined' && typeof tags.tagName !== 'undefined' ? tags.tagName : tags;
    },
    updater: event => {
      const tags = $('#tags');
      let currentTags = tags.data('tag-list');
      if (typeof currentTags === 'undefined') {
        currentTags = event.tagName;
      } else {
        currentTags = currentTags + ', ' + event.tagName;
      }
      tags.data('tagList', currentTags);
      tags.val(currentTags);
      return event;
    },
  });
});
