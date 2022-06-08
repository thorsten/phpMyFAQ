/**
 * JavaScript functions for user frontend
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2022 phpMyFAQ Team
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

  resetUserRights();
  $.getJSON('index.php?action=ajax&ajax=user&ajaxaction=get_user_rights&user_id=' + userId, (data) => {
    $.each(data, (i, val) => {
      document.getElementById('user_right_' + val).checked = true;
    });
    document.getElementById('rights_user_id').value = userId;
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

function resetUserRights() {
  const inputs = document.querySelectorAll('.permission');
  for(let i = 0; i < inputs.length; i++) {
    inputs[i].checked = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  /**
   * Button Password Overwrite
   * @type {jQuery|HTMLElement}
   */
  const button = $('#checkAll');
  const buttonOverwritePassword = $('.pmf-user-password-overwrite-action');

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

  buttonOverwritePassword.on('click', (event) => {
    event.preventDefault();

    // Fetch data
    $.ajax({
      url: 'index.php?action=ajax&ajax=user&ajaxaction=overwrite_password',
      type: 'POST',
      data: $('#pmf-modal-user-password-overwrite form').serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('#pmf-admin-saving-data-indicator').html(
          '<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Saving ...</span>'
        );
      },
      success: function (message) {
        $('.pmf-admin-overwrite-password').replaceWith('<p>✓ ' + message.success + '</p>');
        $('#pmf-modal-user-password-overwrite').modal('hide');
        $('#pmf-admin-saving-data-indicator').fadeOut();
      },
    });
    return false;
  });

  /**
   * User search autocomplete
   */
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
          response(
            data.map((item) => {
              return {
                user_id: item.user_id,
                name: item.name,
              };
            })
          );
        },
      });
    },
    displayText: (item) => {
      return typeof item !== 'undefined' && typeof item.name !== 'undefined' ? item.name : item;
    },
    afterSelect: (user) => {
      $('#user_list_select').val(user.user_id);
      updateUser(user.user_id);
    },
  });

  /**
   * Modal handling add user
   * @type {Element | HTMLElement}
   */
  const modal = document.getElementById('addUserModal');
  const modalBackdrop = document.getElementsByClassName('modal-backdrop fade show');
  const addUser = document.getElementById('pmf-add-user-action');
  const addUserForm = document.getElementById('pmf-add-user-form');
  const addUserError = document.getElementById('pmf-add-user-error-message');
  const addUserMessage = document.getElementById('pmf-user-message');
  const passwordToggle = document.getElementById('add_user_automatic_password');
  const passwordInputs = document.getElementById('add_user_show_password_inputs');

  if (passwordToggle) {
    passwordToggle.addEventListener('click', () => {
      passwordInputs.classList.toggle('d-none');
    });
  }

  if (addUser) {
    addUser.addEventListener('click', (event) => {
      event.preventDefault();
      const csrf = document.getElementById('add_user_csrf').value;
      const userName = document.getElementById('add_user_name').value;
      const realName = document.getElementById('add_user_realname').value;
      const email = document.getElementById('add_user_email').value;
      const password = document.getElementById('add_user_password').value;
      const passwordConfirm = document.getElementById('add_user_password_confirm').value;
      const isSuperAdmin = document.querySelector('#add_user_is_superadmin').checked;

      addUserForm.classList.add('was-validated');

      const userData = {
        userName,
        realName,
        email,
        password,
        passwordConfirm,
        isSuperAdmin,
      };

      postUserData('index.php?action=ajax&ajax=user&ajaxaction=add_user&csrf=' + csrf, userData)
        .then(async (response) => {
          if (response.status !== 201) {
            const errors = await response.json();
            let errorMessage = '';

            errors.forEach((error) => {
              errorMessage += `${error}<br>`;
            });

            addUserError.classList.remove('d-none');
            addUserError.innerHTML = errorMessage;
          } else {
            const result = await response.json();

            addUserMessage.innerHTML = `<p class="alert alert-success">${result.data}</p>`;

            modal.style.display = 'none';
            modal.classList.remove('show');
            modalBackdrop[0].parentNode.removeChild(modalBackdrop[0]);
          }
        })
        .catch((error) => {
          console.log('Final Request failure: ', error);
        });
    });
  }

  /**
   * Post user data to API
   * @param url
   * @param data
   * @returns {Promise<Response>}
   */
  async function postUserData(url = '', data = {}) {
    return await fetch(url, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
      body: JSON.stringify(data),
    });
  }

  /**
   * Export all users
   * @type {Element | HTMLElement}
   */
  const buttonExportAllUsers = document.getElementById('pmf-button-export-users');

  if (buttonExportAllUsers) {
    buttonExportAllUsers.addEventListener('click', (event) => {
      event.preventDefault();

      fetch('index.php?action=ajax&ajax=user&ajaxaction=get_all_user_data', {
        method: 'GET',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
      })
        .then(async (response) => {
          if (response.status === 200) {
            const userData = await response.json();

            const replacer = (key, value) => (value === null ? '' : value);
            const header = Object.keys(userData[0]);
            let csv = userData.map((row) =>
              header.map((fieldName) => JSON.stringify(row[fieldName], replacer)).join(',')
            );
            csv.unshift(header.join(','));
            csv = csv.join('\r\n');

            let hiddenElement = document.createElement('a');
            hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
            hiddenElement.target = '_blank';
            hiddenElement.download = 'phpmyfaq-users-' + new Date().toISOString().substring(0, 10) + '.csv';
            hiddenElement.click();
          }
        })
        .catch((error) => {
          console.log('Final Request failure: ', error);
        });
    });
  }
});
