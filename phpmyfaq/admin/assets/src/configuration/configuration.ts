/**
 * Admin configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne
 * @copyright 2022-2026 phpMyFAQ Team
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
  fetchMailProvider,
  fetchTemplates,
  fetchTranslations,
  fetchTranslationProvider,
  uploadThemeArchive,
  saveConfiguration,
} from '../api';
import { handleWebPush } from './webpush';
import { Response } from '../interfaces';

const TAB_TARGETS = [
  '#main',
  '#records',
  '#search',
  '#security',
  '#spam',
  '#seo',
  '#layout',
  '#mail',
  '#api',
  '#upgrade',
  '#translation',
  '#push',
  '#ldap',
];

let allTabsLoaded = false;

const loadTabForSearch = async (target: string): Promise<void> => {
  const pane = document.querySelector(target) as HTMLElement | null;
  if (!pane || pane.children.length > 0) {
    return;
  }

  const languageElement = document.getElementById('pmf-language') as HTMLInputElement | null;
  if (!languageElement) {
    return;
  }

  const response = await fetchConfiguration(target, languageElement.value);
  pane.innerHTML = response.toString();
};

const ensureAllTabsLoaded = async (): Promise<void> => {
  if (allTabsLoaded) {
    return;
  }

  await Promise.all(TAB_TARGETS.map((target) => loadTabForSearch(target)));
  allTabsLoaded = true;
};

const applyItemFilterToPane = (pane: HTMLElement, query: string): number => {
  const items = Array.from(pane.querySelectorAll('.pmf-config-item')) as HTMLElement[];
  let matchCount = 0;

  items.forEach((item) => {
    const text = (item.textContent || '').toLowerCase();
    const key = (item.dataset.configKey || '').toLowerCase();
    const matches = query === '' || text.includes(query) || key.includes(query);
    item.classList.toggle('d-none', !matches);
    if (matches) {
      matchCount++;
    }
  });

  return matchCount;
};

export const handleConfiguration = async (): Promise<void> => {
  const configTabList: HTMLElement[] = [].slice.call(
    document.querySelectorAll('#configuration-list .pmf-configuration-tabs a[data-bs-toggle="tab"]')
  );
  const result = document.getElementById('pmf-configuration-result') as HTMLElement;

  handleConfigurationTabFiltering();

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
            await handleThemes();
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
            await handleMailProvider();
            break;
          case '#translation':
            await handleTranslationProvider();
            break;
          case '#push':
            await handleWebPush();
            break;
        }

        tabLoaded = true;
        if (configTabTrigger instanceof Element) {
          configTabTrigger.show();
        }
        result.innerHTML = '';

        const filterInput = document.getElementById('pmf-configuration-tab-filter') as HTMLInputElement | null;
        if (filterInput && filterInput.value.trim() !== '') {
          const query = filterInput.value.trim().toLowerCase();
          const pane = document.querySelector(target) as HTMLElement | null;
          if (pane) {
            applyItemFilterToPane(pane, query);
          }
        }
      });
    });

    if (!tabLoaded) {
      await handleConfigurationTab('#main');
      await handleTranslation();
    }
  }
};

export const handleConfigurationTabFiltering = (): void => {
  const filterInput = document.getElementById('pmf-configuration-tab-filter') as HTMLInputElement | null;
  const tabList = document.querySelector('.pmf-configuration-tabs') as HTMLElement | null;

  if (!filterInput || !tabList) {
    return;
  }

  const navItems = Array.from(tabList.querySelectorAll('li.nav-item[data-config-group]')) as HTMLLIElement[];
  const groupHeaders = Array.from(
    tabList.querySelectorAll('li.pmf-configuration-group[data-config-group]')
  ) as HTMLLIElement[];

  if (!navItems.length) {
    return;
  }

  let debounceTimer: ReturnType<typeof setTimeout> | null = null;

  const updateVisibility = async (): Promise<void> => {
    const query = filterInput.value.trim().toLowerCase();

    if (query === '') {
      navItems.forEach((item) => item.classList.remove('d-none'));
      groupHeaders.forEach((header) => header.classList.remove('d-none'));

      TAB_TARGETS.forEach((target) => {
        const pane = document.querySelector(target) as HTMLElement | null;
        if (pane) {
          const items = Array.from(pane.querySelectorAll('.pmf-config-item')) as HTMLElement[];
          items.forEach((item) => item.classList.remove('d-none'));
        }
      });
      return;
    }

    await ensureAllTabsLoaded();

    const visibleGroups = new Set<string>();

    navItems.forEach((item) => {
      const link = item.querySelector('a.nav-link') as HTMLAnchorElement | null;
      const tabTarget = link?.getAttribute('href') || '';
      const tabLabel = (item.dataset.configLabel || link?.textContent || '').trim().toLowerCase();

      let hasMatchingItems = false;
      if (tabTarget) {
        const pane = document.querySelector(tabTarget) as HTMLElement | null;
        if (pane) {
          const matchCount = applyItemFilterToPane(pane, query);
          hasMatchingItems = matchCount > 0;
        }
      }

      const isVisible = tabLabel.includes(query) || hasMatchingItems;
      item.classList.toggle('d-none', !isVisible);

      if (isVisible) {
        visibleGroups.add(item.dataset.configGroup || '');
      }
    });

    groupHeaders.forEach((groupHeader) => {
      const groupName = groupHeader.dataset.configGroup || '';
      groupHeader.classList.toggle('d-none', !visibleGroups.has(groupName));
    });

    const activeLink = tabList.querySelector('a.nav-link.active') as HTMLAnchorElement | null;
    const activeItem = activeLink?.closest('li.nav-item') as HTMLLIElement | null;

    if (activeItem?.classList.contains('d-none')) {
      const firstVisibleLink = tabList.querySelector('li.nav-item:not(.d-none) a.nav-link') as HTMLAnchorElement | null;
      firstVisibleLink?.click();
    }
  };

  const debouncedUpdate = (): void => {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => {
      updateVisibility();
    }, 250);
  };

  filterInput.addEventListener('input', debouncedUpdate);
  filterInput.addEventListener('search', debouncedUpdate);
  updateVisibility();
};

export const handleSaveConfiguration = async (): Promise<void> => {
  const saveConfigurationButton = document.getElementById('save-configuration') as HTMLButtonElement;

  if (saveConfigurationButton) {
    saveConfigurationButton.addEventListener('click', async (event: Event): Promise<void> => {
      event.preventDefault();
      const form = document.getElementById('configuration-list') as HTMLFormElement;
      const formData = new FormData(form);

      // Collect all configuration field names that are currently in the form
      const availableFields: string[] = [];
      const inputs = form.querySelectorAll('input, select, textarea');
      inputs.forEach((input: Element): void => {
        const name = (input as HTMLInputElement).name;
        if (name && name.startsWith('edit[')) {
          // Extract the config key from a name like "edit[main.language]"
          const match = name.match(/edit\[([^\]]+)\]/);
          if (match) {
            availableFields.push(match[1]);
          }
        }
      });

      // Add the available fields list to the form data
      formData.append('availableFields', JSON.stringify(availableFields));

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

export const handleTranslationProvider = async (): Promise<void> => {
  const translationProviderSelectBox = document.getElementsByName(
    'edit[translation.provider]'
  ) as NodeListOf<HTMLSelectElement>;
  if (translationProviderSelectBox !== null && translationProviderSelectBox[0]) {
    const currentValue = (translationProviderSelectBox[0].dataset.pmfConfigurationCurrentValue as string) || 'none';
    const options = await fetchTranslationProvider(currentValue);
    translationProviderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleMailProvider = async (): Promise<void> => {
  const mailProviderSelectBox = document.getElementsByName('edit[mail.provider]') as NodeListOf<HTMLSelectElement>;
  if (mailProviderSelectBox !== null && mailProviderSelectBox[0]) {
    const currentValue = (mailProviderSelectBox[0].dataset.pmfConfigurationCurrentValue as string) || 'smtp';
    const options = await fetchMailProvider(currentValue);
    mailProviderSelectBox[0].insertAdjacentHTML('beforeend', options);
  }
};

export const handleThemes = async (): Promise<void> => {
  const uploadForm = document.getElementById('theme-upload-form') as HTMLFormElement | null;

  if (uploadForm) {
    uploadForm.addEventListener('submit', async (event: Event): Promise<void> => {
      event.preventDefault();

      const response = (await uploadThemeArchive(new FormData(uploadForm))) as unknown as Response;
      if (typeof response.success === 'string') {
        pushNotification(response.success);
        await handleConfigurationTab('#layout');
        await handleTemplates();
        await handleThemes();
      } else {
        pushErrorNotification(response.error || 'Theme upload failed.');
      }
    });
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
    const date: string = new Date(dateLastChecked.value).toLocaleString();
    if (date !== 'Invalid Date') {
      dateLastChecked.value = date;
    } else {
      dateLastChecked.value = 'n/a';
    }
  }
};
