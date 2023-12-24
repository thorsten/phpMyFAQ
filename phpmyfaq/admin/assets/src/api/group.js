/**
 * Fetch data for group management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-02
 */

export const fetchAllGroups = async () => {
  try {
    const response = await fetch('./api/group/groups', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error fetching groups:', error);
    throw error;
  }
};

export const fetchAllUsersForGroups = async () => {
  try {
    const response = await fetch('./api/group/users', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error fetching users for groups:', error);
    throw error;
  }
};

export const fetchAllMembers = async (groupId) => {
  try {
    const response = await fetch(`./api/group/members/${groupId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error(`Error fetching members for group ${groupId}:`, error);
    throw error;
  }
};

export const fetchGroup = async (groupId) => {
  try {
    const response = await fetch(`./api/group/data/${groupId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error(`Error fetching data for group ${groupId}:`, error);
    throw error;
  }
};

export const fetchGroupRights = async (groupId) => {
  try {
    const response = await fetch(`./api/group/permissions/${groupId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error(`Error fetching permissions for group ${groupId}:`, error);
    throw error;
  }
};
