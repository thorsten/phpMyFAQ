/**
 * JavaScript functions for all FAQ record administration stuff
 *
 * @deprecated needs to be rewritten without jQuery
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-11-17
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  /** Initialize the custom file input plugin */
  bsCustomFileInput.init();

  /** File upload handling. */
  $('#filesToUpload').on('change', function () {
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
  $('#pmf-attachment-modal-close').on('click', function () {
    $('#filesize').html('');
    $('.pmf-attachment-upload-files li').remove();
    $('.pmf-attachment-upload-files').addClass('invisible');
    $('.custom-file-input').removeClass('is-invalid');
    $('#attachmentForm')[0].reset();
  });

  /** File upload via Ajax */
  $('#pmf-attachment-modal-upload').on('click', function (event) {
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
      xhr: function () {
        const xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener(
          'progress',
          function (event) {
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
      success: (attachments) => {
        attachments.forEach(function (attachment) {
          $('.adminAttachments').append(
            '<li>' +
              '<a href="../index.php?action=attachment&id=' +
              attachment.attachmentId +
              '">' +
              attachment.fileName +
              '</a>' +
              '<a class="badge bg-danger" href="?action=delatt&amp;record_id=' +
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
      error: () => {
        $('.custom-file-input').addClass('is-invalid');
        $('.pmf-attachment-upload-files').addClass('invisible');
        $('.progress').addClass('invisible');
      },
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
    });
    return false;
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
        success: (data) => {
          response(
            data.map((tags) => {
              return {
                tagName: tags.tagName,
              };
            })
          );
        },
      });
    },
    displayText: (tags) => {
      return typeof tags !== 'undefined' && typeof tags.tagName !== 'undefined' ? tags.tagName : tags;
    },
    updater: (event) => {
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
