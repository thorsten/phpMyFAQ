/**
 * Admin configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-01-29
 */

import { Tab } from 'bootstrap';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';
import { saveConfiguration } from '../api';
import { Response } from '../interfaces';

export const handleConfiguration = async (): Promise<void> => {
  const configTabList: HTMLElement[] = [].slice.call(document.querySelectorAll('#configuration-list a'));
  const result = document.getElementById('pmf-configuration-result') as HTMLElement;
  if (configTabList.length) {
    let tabLoaded = false;
    configTabList.forEach((element) => {
      const configTabTrigger = new Tab(element);
      element.addEventListener('shown.bs.tab', async (event) => {
        event.preventDefault();
        const target = (event.target as HTMLAnchorElement).getAttribute('href') as string;
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

export const handleSaveConfiguration = async (): Promise<void> => {
  const saveConfigurationButton = document.getElementById('save-configuration');

  if (saveConfigurationButton) {
    saveConfigurationButton.addEventListener('click', async (event) => {
      event.preventDefault();
      const form = document.getElementById('configuration-list') as HTMLFormElement;
      const formData = new FormData(form);

      const response = (await saveConfiguration(formData)) as unknown as Response;

      if (typeof response.success === 'string') {
        pushNotification(response.success);
      } else {
        pushErrorNotification(response.error as string);
      }
    });
  }
};

const handleSMTPPasswordToggle = async (): Promise<void> => {
  const passwordField = document.getElementsByName('edit[mail.remoteSMTPPassword]') as NodeListOf<HTMLInputElement>;
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
  const toggle = document.getElementById('SMTPtogglePassword') as HTMLElement;
  toggle.addEventListener('click', () => {
    const type = passwordField[0].getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField[0].setAttribute('type', type);
    const icon = document.getElementById('SMTPtogglePassword_icon') as HTMLElement;
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
  });
};

const handleTranslation = async (): Promise<void> => {
  const translationSelectBox = document.getElementsByName('edit[main.language]') as NodeListOf<HTMLSelectElement>;

  if (translationSelectBox !== null) {
    const options = await fetchTranslations();
    translationSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleTemplates = async (): Promise<void> => {
  const templateSelectBox = document.getElementsByName('edit[layout.templateSet]') as NodeListOf<HTMLSelectElement>;
  if (templateSelectBox !== null) {
    const options = await fetchTemplates();
    templateSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleFaqsSortingKeys = async (): Promise<void> => {
  const faqsOrderSelectBox = document.getElementsByName('edit[records.orderby]') as NodeListOf<HTMLSelectElement>;
  if (faqsOrderSelectBox !== null) {
    const currentValue = faqsOrderSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchFaqsSortingKeys(currentValue);
    faqsOrderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleFaqsSortingOrder = async (): Promise<void> => {
  const faqsOrderSelectBox = document.getElementsByName('edit[records.sortby]') as NodeListOf<HTMLSelectElement>;
  if (faqsOrderSelectBox !== null) {
    const currentValue = faqsOrderSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchFaqsSortingOrder(currentValue);
    faqsOrderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleFaqsSortingPopular = async (): Promise<void> => {
  const faqsPopularSelectBox = document.getElementsByName(
    'edit[records.orderingPopularFaqs]'
  ) as NodeListOf<HTMLSelectElement>;
  if (faqsPopularSelectBox !== null) {
    const currentValue = faqsPopularSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchFaqsSortingPopular(currentValue);
    faqsPopularSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handlePermLevel = async (): Promise<void> => {
  const permLevelSelectBox = document.getElementsByName('edit[security.permLevel]') as NodeListOf<HTMLSelectElement>;
  if (permLevelSelectBox !== null) {
    const currentValue = permLevelSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchPermLevel(currentValue);
    permLevelSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleReleaseEnvironment = async (): Promise<void> => {
  const releaseEnvironmentSelectBox = document.getElementsByName(
    'edit[upgrade.releaseEnvironment]'
  ) as NodeListOf<HTMLSelectElement>;
  if (releaseEnvironmentSelectBox !== null) {
    const currentValue = releaseEnvironmentSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchReleaseEnvironment(currentValue);
    releaseEnvironmentSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleSearchRelevance = async (): Promise<void> => {
  const searchRelevanceSelectBox = document.getElementsByName(
    'edit[search.relevance]'
  ) as NodeListOf<HTMLSelectElement>;
  if (searchRelevanceSelectBox !== null) {
    const currentValue = searchRelevanceSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchSearchRelevance(currentValue);
    searchRelevanceSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

const handleSeoMetaTags = async (): Promise<void> => {
  const seoMetaTagsSelectBoxes = document.querySelectorAll(
    'select[name^="edit[seo.metaTags"]'
  ) as NodeListOf<HTMLSelectElement>;

  if (seoMetaTagsSelectBoxes) {
    for (const seoMetaTagsSelectBox of seoMetaTagsSelectBoxes) {
      const currentValue = seoMetaTagsSelectBox.dataset.pmfConfigurationCurrentValue as string;
      const options = await fetchSeoMetaTags(currentValue);
      seoMetaTagsSelectBox.insertAdjacentHTML('beforeend', options);
    }
  }
};

const fetchConfiguration = async (target: string): Promise<void> => {
  try {
    const response = await fetch(`./api/configuration/list/${target.substring(1)}`, {
      headers: {
        'Accept-Language': (document.getElementById('pmf-language') as HTMLInputElement).value,
      },
    });

    if (!response.ok) {
      console.error('Request failed!');
      return;
    }

    const html = await response.text();
    const tabContent = document.querySelector(target) as HTMLElement;

    while (tabContent.firstChild) {
      tabContent.removeChild(tabContent.firstChild);
    }

    tabContent.innerHTML = html.toString();

    // Special cases
    const dateLastChecked = tabContent.querySelector('input[name="edit[upgrade.dateLastChecked]"]') as HTMLInputElement;
    if (dateLastChecked) {
      dateLastChecked.value = new Date(dateLastChecked.value).toLocaleString();
    }
  } catch (error) {
    console.error(error.message);
  }
};

const fetchTranslations = async (): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/translations`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchTemplates = async (): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/templates`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchFaqsSortingKeys = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-key/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchFaqsSortingOrder = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-order/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchFaqsSortingPopular = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-popular/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchPermLevel = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/perm-level/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchReleaseEnvironment = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/release-environment/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchSearchRelevance = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/search-relevance/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};

const fetchSeoMetaTags = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/seo-metatags/${currentValue}`);

    if (!response.ok) {
      console.error('Request failed!');
      return '';
    }

    return await response.text();
  } catch (error) {
    console.error(error.message);
    return '';
  }
};
