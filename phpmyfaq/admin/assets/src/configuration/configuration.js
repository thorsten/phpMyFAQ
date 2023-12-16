/**
 * Admin configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-29
 */

import { Tab } from 'bootstrap';

export const handleConfiguration = async () => {
  const configTabList = [].slice.call(document.querySelectorAll('#configuration-list a'));
  if (configTabList.length) {
    let tabLoaded = false;
    configTabList.forEach((element) => {
      const configTabTrigger = new Tab(element);
      element.addEventListener('shown.bs.tab', async (event) => {
        event.preventDefault();
        let target = event.target.getAttribute('href');
        fetchConfiguration(target);
        switch (target) {
          case '#main':
            await handleTranslation();
            await handleTemplates();
            break;
        }
        tabLoaded = true;
        configTabTrigger.show();
      });
    });

    if (!tabLoaded) {
      fetchConfiguration('#main');
    }
  }
};

export const handleTranslation = async () => {
  const translationSelectBox = document.getElementsByName('edit[main.language]');
  if (translationSelectBox) {
    const options = await fetchTranslations();
    translationSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleTemplates = async () => {
  const translationSelectBox = document.getElementsByName('edit[main.templateSet]');
  if (translationSelectBox) {
    const options = await fetchTemplates();
    translationSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const fetchConfiguration = (target) => {
  //fetch(`index.php?action=ajax&ajax=configuration-list&conf=${target.substr(1)}`)
  fetch(`./api/configuration/list/${target.substr(1)}`)
    .then(
      (response) => {
        if (response.ok) {
          return response.text();
        }
        throw new Error('Request failed!');
      },
      (networkError) => {
        console.log(networkError.message);
      }
    )
    .then((html) => {
      const tabContent = document.querySelector(target);
      while (tabContent.firstChild) {
        tabContent.removeChild(tabContent.firstChild);
      }
      tabContent.innerHTML = html.toString();
    })
    .catch((error) => {
      console.error(error);
    });
};

const fetchTranslations = async () => {
  return await fetch(`./api/configuration/translations`)
    .then(
      (response) => {
        if (response.ok) {
          return response.text();
        }
        throw new Error('Request failed!');
      },
      (networkError) => {
        console.log(networkError.message);
      }
    )
    .then((html) => {
      return html;
    })
    .catch((error) => {
      console.error(error);
    });
};

const fetchTemplates = async () => {
  return await fetch(`./api/configuration/templates`)
    .then(
      (response) => {
        if (response.ok) {
          return response.text();
        }
        throw new Error('Request failed!');
      },
      (networkError) => {
        console.log(networkError.message);
      }
    )
    .then((html) => {
      return html;
    })
    .catch((error) => {
      console.error(error);
    });
};
