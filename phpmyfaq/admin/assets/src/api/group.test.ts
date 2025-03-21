import { describe, it, expect, vi } from 'vitest';
import { fetchAllGroups, fetchAllUsersForGroups, fetchAllMembers, fetchGroup, fetchGroupRights } from './group';

describe('fetchAllGroups', () => {
  it('should fetch all groups and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Groups data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const result = await fetchAllGroups();

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/group/groups', {
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
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    await expect(fetchAllGroups()).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchAllUsersForGroups', () => {
  it('should fetch all users for groups and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Users data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const result = await fetchAllUsersForGroups();

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith('./api/group/users', {
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
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    await expect(fetchAllUsersForGroups()).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchAllMembers', () => {
  it('should fetch all members of a group and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Members data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const groupId = '123';
    const result = await fetchAllMembers(groupId);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/group/members/${groupId}`, {
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
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const groupId = '123';

    await expect(fetchAllMembers(groupId)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchGroup', () => {
  it('should fetch a group and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Group data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const groupId = '123';
    const result = await fetchGroup(groupId);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/group/data/${groupId}`, {
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
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const groupId = '123';

    await expect(fetchGroup(groupId)).rejects.toThrow('Network response was not ok.');
  });
});

describe('fetchGroupRights', () => {
  it('should fetch group rights and return JSON response if successful', async () => {
    const mockResponse = { success: true, data: 'Group rights data' };
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 200,
        json: () => Promise.resolve(mockResponse),
      } as Response)
    );

    const groupId = '123';
    const result = await fetchGroupRights(groupId);

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(`./api/group/permissions/${groupId}`, {
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
    global.fetch = vi.fn(() =>
      Promise.resolve({
        status: 500,
      } as Response)
    );

    const groupId = '123';

    await expect(fetchGroupRights(groupId)).rejects.toThrow('Network response was not ok.');
  });
});
