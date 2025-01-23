/**
 * Fetch data for configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-21
 */

import { Response } from '../interfaces';

export const fetchConfiguration = async (target: string, language: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/list/${target.substring(1)}`, {
      headers: {
        'Accept-Language': language,
      },
    });

    if (!response.ok) {
      throw new Error('Network response was not ok.');
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchFaqsSortingKeys = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-key/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchFaqsSortingOrder = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-order/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchFaqsSortingPopular = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/faqs-sorting-popular/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchPermLevel = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/perm-level/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchReleaseEnvironment = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/release-environment/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchSearchRelevance = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/search-relevance/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchSeoMetaTags = async (currentValue: string): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/seo-metatags/${currentValue}`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchTemplates = async (): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/templates`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const fetchTranslations = async (): Promise<string> => {
  try {
    const response = await fetch(`./api/configuration/translations`);

    if (!response.ok) {
      return '';
    }

    return await response.text();
  } catch (error) {
    throw error;
  }
};

export const saveConfiguration = async (data: FormData): Promise<void> => {
  try {
    const response = (await fetch('api/configuration', {
      method: 'POST',
      body: data,
    })) as unknown as Response;

    if (response.success) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    throw error;
  }
};
