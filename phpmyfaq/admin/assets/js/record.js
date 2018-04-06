/**
 * JavaScript functions for all FAQ record administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-11-17
 */

/*global $:false, Bloodhound: false, Handlebars: false */

$(document).ready(function () {
  'use strict';

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
    for (let aMultiples = ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'], nMultiple = 0, nApprox = nBytes / 1024; nApprox > 1; nApprox /= 1024, nMultiple++) {
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
      xhr: function() {
        const xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(event) {
          if (event.lengthComputable) {
            let percentComplete = event.loaded / event.total;
            percentComplete = parseInt(percentComplete * 100);
            $('.progress-bar').width(percentComplete);
          }
        }, false);
        return xhr;
      },
      success: function (attachments) {
        attachments.forEach(function (attachment) {
          $('.adminAttachments').
          append(
            '<li>' +
            '<a href="../index.php?action=attachment&id=' + attachment.attachmentId + '">' + attachment.fileName + '</a>' +
            '<a class="label label-danger" href="?action=delatt&amp;record_id=' + attachment.faqId +
            '&amp;id=' + attachment.attachmentId + '&amp;lang=' + attachment.faqLanguage + '">' +
            '<i class="material-icons">delete</i></a>' +
            '</li>'
          )
        });
        progress.addClass('invisible');
        $('#filesize').html('');
        $('.pmf-attachment-upload-files li').remove();
        $('.pmf-attachment-upload-files').addClass('invisible');
        $('#attachmentForm')[0].reset();
        $('#attachmentModal').modal('hide');
      },
      error: function (data) {
        console.log(data);
      },
      data: formData,
      cache: false,
      contentType: false,
      processData: false
    });
    return false;
  });

  /**
   * Delete questions
   *
   */
  $('#submitDeleteQuestions').on('click', function () {
    const questions = $('#questionSelection').serialize(),
      indicator = $('#saving_data_indicator');

    $('#returnMessage').empty();
    $.ajax({
      type: 'POST',
      url: 'index.php?action=ajax&ajax=records&ajaxaction=delete_question',
      data: questions,
      success: function (msg) {
        indicator.html('<img src="../assets/svg/spinning-circles.svg"> Deleting ...');
        $('tr td input:checked').parent().parent().parent().fadeOut('slow');
        indicator.fadeOut('slow');
        $('#returnMessage').html('<p class="alert alert-success">' + msg + '</p>');
      }
    });
    return false;
  });

  $(function () {
    // set the textarea to its previous height
    const answerHeight = localStorage.getItem('textarea.answer.height'),
      answer = $('#answer');

    if (answerHeight !== 'undefined') {
      answer.height(answerHeight);
    }

    // when reszied, store the textarea's height
    answer.on('mouseup', function () {
      localStorage.setItem('textarea.answer.height', $(this).height());
    });

    // on clicking the Preview tab, refresh the preview
    $('.markdown-tabs').find('a').on('click', function () {
      if ($(this).attr('data-markdown-tab') === 'preview') {
        $('.markdown-preview')
          .height(answer.height());
        $.post('index.php?action=ajax&ajax=markdown', {text: answer.val()}, function (result) {
          $('.markdown-preview').html(result);
        });
      }
    });
  });

  // Instantiate the bloodhound suggestion engine
  const tags = new Bloodhound({
    datumTokenizer: function (d) {
      return Bloodhound.tokenizers.whitespace(d.value);
    },
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
      url: 'index.php?action=ajax&ajax=tags&ajaxaction=list&q=%QUERY',
      wildcard: '%QUERY',
      filter: function (tags) {
        return $.map(tags.results, function (tags) {
          return {
            tagName: tags.tagName
          };
        });
      }
    }
  });

  // Initialize the bBloodhound suggestion engine
  tags.initialize();

  // Instantiate the Typeahead UI
  $('.pmf-tags-autocomplete').typeahead(null, {
    source: tags.ttAdapter(),
    displayKey: 'tags',
    name: 'tags',
    minLength: 1,
    templates: {
      empty: [
        '<div class="empty-message">',
        'unable to find any Best Picture winners that match the current query',
        '</div>'
      ].join('\n'),
      suggestion: Handlebars.compile('<div data-tagName="{{tagName}}">{{tagName}}</div>')
    }
  }).on('typeahead:selected typeahead:autocompleted', function (event, tag) {
    const tags = $('#tags');
    let currentTags = tags.data('tagList');

    if (typeof currentTags === 'undefined') {
      currentTags = tag.tagName;
    } else {
      currentTags = currentTags + ', ' + tag.tagName;
    }

    tags.data('tagList', currentTags);
    $('.pmf-tags-autocomplete').typeahead('val', currentTags);
  });
});
