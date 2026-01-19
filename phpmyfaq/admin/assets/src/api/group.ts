/**
 * Fetch data for group management
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-01-02
 */

import { Group, Member, User } from '../interfaces';
import { fetchJson } from './fetch-wrapper';

export const fetchAllGroups = async (): Promise<Group[]> => {
  return (await fetchJson('./api/group/groups', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as Group[];
};

export const fetchAllUsersForGroups = async (): Promise<User[]> => {
  return (await fetchJson('./api/group/users', {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as User[];
};

export const fetchAllMembers = async (groupId: string): Promise<Member[]> => {
  return (await fetchJson(`./api/group/members/${groupId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as Member[];
};

export const fetchGroup = async (groupId: string): Promise<Group> => {
  return (await fetchJson(`./api/group/data/${groupId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as Group;
};

export const fetchGroupRights = async (groupId: string): Promise<string[]> => {
  return (await fetchJson(`./api/group/permissions/${groupId}`, {
    method: 'GET',
    cache: 'no-cache',
    headers: {
      'Content-Type': 'application/json',
    },
    redirect: 'follow',
    referrerPolicy: 'no-referrer',
  })) as string[];
};
