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
import {
  fetchConfiguration,
  fetchFaqsSortingKeys,
  fetchFaqsSortingOrder,
  fetchFaqsSortingPopular,
  fetchPermLevel,
  fetchReleaseEnvironment,
  fetchSearchRelevance,
  fetchSeoMetaTags,
  fetchTemplates,
  fetchTranslations,
  saveConfiguration,
} from '../api';
import { Response } from '../interfaces';

export const handleConfiguration = async (): Promise<void> => {
  const configTabList: HTMLElement[] = [].slice.call(document.querySelectorAll('#configuration-list a'));
  const result = document.getElementById('pmf-configuration-result') as HTMLElement;
  if (configTabList.length) {
    let tabLoaded: boolean = false;
    configTabList.forEach((element: HTMLElement): void => {
      const configTabTrigger = new Tab(element);
      element.addEventListener('shown.bs.tab', async (event: Event): Promise<void> => {
        event.preventDefault();
        const target = (event.target as HTMLAnchorElement).getAttribute('href') as string;
        await handleConfigurationTab(target);

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
        if (configTabTrigger instanceof Element) {
          configTabTrigger.show();
        }
        result.innerHTML = '';
      });
    });

    if (!tabLoaded) {
      await handleConfigurationTab('#main');
      await handleTranslation();
    }
  }
};

export const handleSaveConfiguration = async (): Promise<void> => {
  const saveConfigurationButton = document.getElementById('save-configuration') as HTMLButtonElement;

  if (saveConfigurationButton) {
    saveConfigurationButton.addEventListener('click', async (event: Event): Promise<void> => {
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

export const handleSMTPPasswordToggle = async (): Promise<void> => {
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

export const handleTranslation = async (): Promise<void> => {
  const translationSelectBox = document.getElementsByName('edit[main.language]') as NodeListOf<HTMLSelectElement>;

  if (translationSelectBox !== null) {
    const options = await fetchTranslations();
    if (translationSelectBox[0]) {
      translationSelectBox[0].insertAdjacentHTML('beforeend', options);
    }
  }
};

export const handleTemplates = async (): Promise<void> => {
  const templateSelectBox = document.getElementsByName('edit[layout.templateSet]') as NodeListOf<HTMLSelectElement>;
  if (templateSelectBox !== null) {
    const options = await fetchTemplates();
    templateSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleFaqsSortingKeys = async (): Promise<void> => {
  const faqsOrderSelectBox = document.getElementsByName('edit[records.orderby]') as NodeListOf<HTMLSelectElement>;
  if (faqsOrderSelectBox !== null) {
    const currentValue = faqsOrderSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchFaqsSortingKeys(currentValue);
    if (faqsOrderSelectBox[0]) {
      faqsOrderSelectBox[0].insertAdjacentHTML('beforeend', options);
    }
  }
};

export const handleFaqsSortingOrder = async (): Promise<void> => {
  const faqsOrderSelectBox = document.getElementsByName('edit[records.sortby]') as NodeListOf<HTMLSelectElement>;
  if (faqsOrderSelectBox !== null) {
    const currentValue = faqsOrderSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchFaqsSortingOrder(currentValue);
    faqsOrderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleFaqsSortingPopular = async (): Promise<void> => {
  const faqsPopularSelectBox = document.getElementsByName(
    'edit[records.orderingPopularFaqs]'
  ) as NodeListOf<HTMLSelectElement>;
  if (faqsPopularSelectBox !== null) {
    const currentValue = faqsPopularSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchFaqsSortingPopular(currentValue);
    faqsPopularSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handlePermLevel = async (): Promise<void> => {
  const permLevelSelectBox = document.getElementsByName('edit[security.permLevel]') as NodeListOf<HTMLSelectElement>;
  if (permLevelSelectBox !== null) {
    const currentValue = permLevelSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchPermLevel(currentValue);
    permLevelSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleReleaseEnvironment = async (): Promise<void> => {
  const releaseEnvironmentSelectBox = document.getElementsByName(
    'edit[upgrade.releaseEnvironment]'
  ) as NodeListOf<HTMLSelectElement>;
  if (releaseEnvironmentSelectBox !== null) {
    const currentValue = releaseEnvironmentSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchReleaseEnvironment(currentValue);
    releaseEnvironmentSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleSearchRelevance = async (): Promise<void> => {
  const searchRelevanceSelectBox = document.getElementsByName(
    'edit[search.relevance]'
  ) as NodeListOf<HTMLSelectElement>;
  if (searchRelevanceSelectBox !== null) {
    const currentValue = searchRelevanceSelectBox[0].dataset.pmfConfigurationCurrentValue as string;
    const options = await fetchSearchRelevance(currentValue);
    searchRelevanceSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleSeoMetaTags = async (): Promise<void> => {
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

export const handleConfigurationTab = async (target: string): Promise<void> => {
  const languageElement = document.getElementById('pmf-language') as HTMLInputElement;
  if (!languageElement) {
    throw new Error('Language element not found');
  }
  const language: string = languageElement.value;
  const response: string = await fetchConfiguration(target, language);

  const tabContent = document.querySelector(target) as HTMLElement;
  if (!tabContent) {
    throw new Error(`Tab content for target ${target} not found`);
  }

  while (tabContent.firstChild) {
    tabContent.removeChild(tabContent.firstChild);
  }

  tabContent.innerHTML = response.toString();

  // Special cases
  const dateLastChecked = tabContent.querySelector('input[name="edit[upgrade.dateLastChecked]"]') as HTMLInputElement;
  if (dateLastChecked) {
    dateLastChecked.value = new Date(dateLastChecked.value).toLocaleString();
  }
};
