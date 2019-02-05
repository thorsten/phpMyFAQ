/**
 * JavaScript functions for user frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @package   Administration
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-05-02
 */

/*global $:false, getUserData: false */

/**
 * Fetches the user rights as JSON object and checks the checkboxes
 *
 * @param userId User ID
 */
function getUserRights(userId) {
  'use strict';

  $.getJSON('index.php?action=ajax&ajax=user&ajaxaction=get_user_rights&user_id=' + userId,
    (data) => {
      $.each(data, (i, val) => {
        $('#user_right_' + val).attr('checked', true);
      });
      $('#rights_user_id').val(userId);
    });
}

/**
 * Updates the user data in forms
 *
 * @param userId User ID
 */
function updateUser(userId) {
  'use strict';

  getUserData(userId);
  getUserRights(userId);
}


$(document).ready(function () {
  'use strict';

  const button = $('#checkAll');
  const buttonOverridePassword = $('.pmf-user-password-override-action');

  button.data('type', 'check');
  button.on('click', (event) => {
    const checkbox = $('.permission');
    event.preventDefault();
    if (button.data('type') === 'check') {
      checkbox.prop('checked', true);
      button.data('type', 'uncheck');
    } else {
      checkbox.prop('checked', false);
      button.data('type', 'check');
    }
  });

  buttonOverridePassword.on('click', (event) => {
    event.preventDefault();

    // Fetch data
    $.ajax({
      url: 'index.php?action=ajax&ajax=user&ajaxaction=overwrite_password',
      type: 'POST',
      data: $('#pmf-modal-user-password-override form').serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('#saving_data_indicator').html('<img src="../assets/svg/spinning-circles.svg"> Saving ...');
      },
      success: function (message) {
        $('.pmf-admin-override-password').replaceWith('<p>✓ ' + message.success + '</p>');
        $('#pmf-modal-user-password-override').modal('hide');
        $('#saving_data_indicator').fadeOut();
      }
    });
    return false;
  });

  $('.pmf-user-autocomplete').typeahead({
    autoSelect: true,
    delay: 300,
    minLength: 1,
    source: (request, response) => {
      $.ajax({
        url: 'index.php?action=ajax&ajax=user&ajaxaction=get_user_list',
        type: 'GET',
        dataType: 'JSON',
        data: 'q=' + request,
        success: (data) => {
          response(data.map((item) => {
            return {
              user_id: item.user_id,
              name: item.name
            };
          }));
        }
      });
    },
    displayText: (item) => {
      return typeof item !== 'undefined' && typeof item.name !== 'undefined' ? item.name : item;
    },
    afterSelect: (user) => {
      $('#user_list_select').val(user.user_id);
      updateUser(user.user_id);
    }
  });

});
