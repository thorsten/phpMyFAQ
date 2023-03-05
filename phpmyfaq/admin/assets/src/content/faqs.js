/**
 * FAQ administration stuff
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-07-22
 */

const showHelp = (option) => {
  const optionHelp = document.getElementById(`${option}Help`);
  optionHelp.classList.remove('visually-hidden');
  optionHelp.addEventListener('click', () => (optionHelp.style.opacity = '0'));
  optionHelp.addEventListener('transitionend', () => optionHelp.remove());
};

export const handleFaqForm = () => {
  const inputTags = document.getElementById('tags');
  const inputSearchKeywords = document.getElementById('keywords');

  if (inputTags) {
    inputTags.addEventListener('focus', () => showHelp('tags'));
  }
  if (inputSearchKeywords) {
    inputSearchKeywords.addEventListener('focus', () => showHelp('keywords'));
  }

  const categoryOptions = document.querySelector('#phpmyfaq-categories');

  if (categoryOptions) {
    let categories = Array.from(categoryOptions.selectedOptions).map(({ value }) => value);
    getCategoryPermissions(categories);

    // Override FAQ permissions with Category permission to avoid confused users
    categoryOptions.addEventListener('click', (event) => {
      event.preventDefault();
      let categories = Array.from(categoryOptions.selectedOptions).map(({ value }) => value);
      getCategoryPermissions(categories);
    });
  }

  const faqId = document.getElementById('record_id');
  if (faqId && faqId > 0) {
    getFaqPermissions(faqId.value);
  }
};

const getCategoryPermissions = (categories) => {
  fetch(`index.php?action=ajax&ajax=categories&ajaxaction=getpermissions&categories=${categories}`)
    .then((response) => {
      return response.json();
    })
    .then((permissions) => {
      setPermissions(permissions);
    });
};

const setPermissions = (permissions) => {
  const perms = permissions;

  // Users
  if (-1 === parseInt(perms.user[0])) {
    document.getElementById('restrictedusers').checked = false;
    document.getElementById('allusers').checked = true;
  } else {
    document.getElementById('allusers').checked = false;
    document.getElementById('restrictedusers').checked = true;
    perms.user.forEach((value) => {
      document.querySelector(`#selected-user option[value='${value}']`).selected = true;
    });
  }

  // Groups
  if (-1 === parseInt(perms.group[0])) {
    document.getElementById('restrictedgroups').checked = false;
    document.getElementById('restrictedgroups').disabled = false;
    document.getElementById('allgroups').checked = true;
    document.getElementById('allgroups').disabled = false;
  } else {
    document.getElementById('allgroups').checked = false;
    document.getElementById('allgroups').disabled = true;
    document.getElementById('restrictedgroups').checked = true;
    document.getElementById('restrictedgroups').disabled = false;
    perms.group.forEach((value) => {
      document.querySelector(`#selected-groups option[value='${value}']`).selected = true;
    });
  }
};

const getFaqPermissions = (faqId) => {
  const csrfToken = document.getElementById('csrf').value;
  fetch(`index.php?action=ajax&ajax=records&ajaxaction=permissions&faq-id=${faqId}&csrf=${csrfToken}`)
    .then((response) => {
      return response.json();
    })
    .then((permissions) => {
      setPermissions(permissions);
    });
};
