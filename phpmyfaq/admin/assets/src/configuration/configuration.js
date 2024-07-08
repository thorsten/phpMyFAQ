/**
 * Admin configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-29
 */

import { Tab } from 'bootstrap';
import { pushErrorNotification, pushNotification } from '../utils';

export const handleConfiguration = async () => {
  const configTabList = [].slice.call(document.querySelectorAll('#configuration-list a'));
  const result = document.getElementById('pmf-configuration-result');
  const language = document.getElementById('pmf-language');
  if (configTabList.length) {
    let tabLoaded = false;
    configTabList.forEach((element) => {
      const configTabTrigger = new Tab(element);
      element.addEventListener('shown.bs.tab', async (event) => {
        event.preventDefault();
        let target = event.target.getAttribute('href');
        await fetchConfiguration(target);

        switch (target) {
          case '#main':
            await handleTranslation();
            break;
          case '#layout':
            await handleTemplates();
            break;
          case '#records':
            await handleFaqsSortingKeys();
            await handleFaqsSortingOrder();
            await handleFaqsSortingPopular();
            break;
          case '#search':
            await handleSearchRelevance();
            break;
          case '#security':
            await handlePermLevel();
            break;
          case '#seo':
            await handleSeoMetaTags();
            break;
          case '#upgrade':
            await handleReleaseEnvironment();
            break;
          case '#mail':
            await handleSMTPPasswordToggle();
            break;
        }

        tabLoaded = true;
        configTabTrigger.show();
        result.innerHTML = '';
      });
    });

    if (!tabLoaded) {
      await fetchConfiguration('#main');
      await handleTranslation();
    }
  }
};

export const handleSaveConfiguration = async () => {
  const saveConfigurationButton = document.getElementById('save-configuration');

  if (saveConfigurationButton) {
    saveConfigurationButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const form = document.getElementById('configuration-list');
      const formData = new FormData(form);

      const response = await fetch('./api/configuration', {
        method: 'POST',
        body: formData,
      });

      if (!response.ok) {
        console.error('Request failed!');
        return;
      }

      const json = await response.json();

      if (json.success) {
        pushNotification(json.success);
      } else {
        pushErrorNotification(json.error);
      }
    });
  }
};

const handleSMTPPasswordToggle = async () => {
  const passwordField = document.getElementsByName('edit[mail.remoteSMTPPassword]');
  const toggleHTML =
    '<span class="input-group-text" id="SMTPtogglePassword"><i class="bi bi-eye-slash" id="SMTPtogglePassword_icon"></i></span>';
  const containerDiv = document.createElement('div');
  containerDiv.classList.add('input-group');
  containerDiv.innerHTML = `
        <input class="form-control" type="password" autocomplete="off" name="edit[mail.remoteSMTPPassword]" value="" data-pmf-toggle="SMTPtogglePassword">
        ${toggleHTML}
    `;
  passwordField[0].insertAdjacentElement('afterend', containerDiv);
  passwordField[0].remove();
  var toggle = document.getElementById('SMTPtogglePassword');
  toggle.addEventListener('click', () => {
    var type = passwordField[0].getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField[0].setAttribute('type', type);
    var icon = document.getElementById('SMTPtogglePassword_icon');
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
  });
};

const handleTranslation = async () => {
  const translationSelectBox = document.getElementsByName('edit[main.language]');

  if (translationSelectBox !== null) {
    const options = await fetchTranslations();
    translationSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleTemplates = async () => {
  const templateSelectBox = document.getElementsByName('edit[layout.templateSet]');
  if (templateSelectBox !== null) {
    const options = await fetchTemplates();
    templateSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleFaqsSortingKeys = async () => {
  const faqsOrderSelectBox = document.getElementsByName('edit[records.orderby]');
  if (faqsOrderSelectBox !== null) {
    const currentValue = faqsOrderSelectBox[0].dataset.pmfConfigurationCurrentValue;
    const options = await fetchFaqsSortingKeys(currentValue);
    faqsOrderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleFaqsSortingOrder = async () => {
  const faqsOrderSelectBox = document.getElementsByName('edit[records.sortby]');
  if (faqsOrderSelectBox !== null) {
    const currentValue = faqsOrderSelectBox[0].dataset.pmfConfigurationCurrentValue;
    const options = await fetchFaqsSortingOrder(currentValue);
    faqsOrderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleFaqsSortingPopular = async () => {
  const faqsPopularSelectBox = document.getElementsByName('edit[records.orderingPopularFaqs]');
  if (faqsPopularSelectBox !== null) {
    const currentValue = faqsPopularSelectBox[0].dataset.pmfConfigurationCurrentValue;
    const options = await fetchFaqsSortingPopular(currentValue);
    faqsPopularSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handlePermLevel = async () => {
  const permLevelSelectBox = document.getElementsByName('edit[security.permLevel]');
  if (permLevelSelectBox !== null) {
    const currentValue = permLevelSelectBox[0].dataset.pmfConfigurationCurrentValue;
    const options = await fetchPermLevel(currentValue);
    permLevelSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleReleaseEnvironment = async () => {
  const releaseEnvironmentSelectBox = document.getElementsByName('edit[upgrade.releaseEnvironment]');
  if (releaseEnvironmentSelectBox !== null) {
    const currentValue = releaseEnvironmentSelectBox[0].dataset.pmfConfigurationCurrentValue;
    const options = await fetchReleaseEnvironment(currentValue);
    releaseEnvironmentSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleSearchRelevance = async () => {
  const searchRelevanceSelectBox = document.getElementsByName('edit[search.relevance]');
  if (searchRelevanceSelectBox !== null) {
    const currentValue = searchRelevanceSelectBox[0].dataset.pmfConfigurationCurrentValue;
    const options = await fetchSearchRelevance(currentValue);
    searchRelevanceSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleSeoMetaTags = async () => {
  const seoMetaTagsSelectBoxes = document.querySelectorAll('select[name^="edit[seo.metaTags"]');

  if (seoMetaTagsSelectBoxes) {
    for (const seoMetaTagsSelectBox of seoMetaTagsSelectBoxes) {
      const currentValue = seoMetaTagsSelectBox.dataset.pmfConfigurationCurrentValue;
      const options = await fetchSeoMetaTags(currentValue);
      seoMetaTagsSelectBox.insertAdjacentHTML('beforeend', options);
    }
  }
};

const fetchConfiguration = async (target) => {
  try {
    const response = await fetch(`./api/configuration/list/${target.substring(1)}`, {
      headers: {
        'Accept-Language': language.value,
      },
    });

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    const html = await response.text();
    const tabContent = document.querySelector(target);

    while (tabContent.firstChild) {
      tabContent.removeChild(tabContent.firstChild);
    }

    tabContent.innerHTML = html.toString();

    // Special cases
    const dateLastChecked = tabContent.querySelector('input[name="edit[upgrade.dateLastChecked]"]');
    if (dateLastChecked) {
      dateLastChecked.value = new Date(dateLastChecked.value).toLocaleString();
    }
  } catch (error) {
    console.error(error.message);
  }
};

const fetchTranslations = async () => {
  try {
    const response = await fetch(`./api/configuration/translations`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchTemplates = async () => {
  try {
    const response = await fetch(`./api/configuration/templates`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchFaqsSortingKeys = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-key/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchFaqsSortingOrder = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-order/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchFaqsSortingPopular = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-popular/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchPermLevel = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/perm-level/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchReleaseEnvironment = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/release-environment/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchSearchRelevance = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/search-relevance/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};

const fetchSeoMetaTags = async (currentValue) => {
  try {
    const response = await fetch(`./api/configuration/seo-metatags/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
  }
};
