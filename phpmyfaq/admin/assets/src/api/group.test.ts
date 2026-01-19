import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fetchAllGroups, fetchAllUsersForGroups, fetchAllMembers, fetchGroup, fetchGroupRights } from './group';
import * as fetchWrapperModule from './fetch-wrapper';

vi.mock('./fetch-wrapper', () => ({
  fetchJson: vi.fn(),
}));

describe('fetchAllGroups', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch all groups and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Groups data' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const result = await fetchAllGroups();

    expect(result).toEqual(mockResponse);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/group/groups', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(new Error('Network response was not ok.'));

    await expect(fetchAllGroups()).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchAllUsersForGroups', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch all users for groups and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Users data' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const result = await fetchAllUsersForGroups();

    expect(result).toEqual(mockResponse);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith('./api/group/users', {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(new Error('Network response was not ok.'));

    await expect(fetchAllUsersForGroups()).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchAllMembers', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch all members of a group and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Members data' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const groupId = '123';
    const result = await fetchAllMembers(groupId);

    expect(result).toEqual(mockResponse);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith(`./api/group/members/${groupId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(new Error('Network response was not ok.'));

    const groupId = '123';

    await expect(fetchAllMembers(groupId)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchGroup', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch a group and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Group data' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const groupId = '123';
    const result = await fetchGroup(groupId);

    expect(result).toEqual(mockResponse);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith(`./api/group/data/${groupId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(new Error('Network response was not ok.'));

    const groupId = '123';

    await expect(fetchGroup(groupId)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchGroupRights', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should fetch group rights and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Group rights data' };
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockResolvedValue(mockResponse);

    const groupId = '123';
    const result = await fetchGroupRights(groupId);

    expect(result).toEqual(mockResponse);
    expect(fetchWrapperModule.fetchJson).toHaveBeenCalledWith(`./api/group/permissions/${groupId}`, {
      method: 'GET',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });
  });

  it('should throw an error if the network response is not ok', async () => {
    vi.spyOn(fetchWrapperModule, 'fetchJson').mockRejectedValue(new Error('Network response was not ok.'));

    const groupId = '123';

    await expect(fetchGroupRights(groupId)).rejects.toThrow('Network response was not ok.');
  });
});
