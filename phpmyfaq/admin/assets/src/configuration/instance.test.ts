/**
 * Tests for instance configuration
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-18
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { handleInstances } from './instance';
import * as api from '../api/instance';

interface MockModal {
  element: HTMLElement;
  hide: ReturnType<typeof vi.fn>;
  show: ReturnType<typeof vi.fn>;
}

// Mock Bootstrap Modal globally
vi.mock('bootstrap', () => ({
  Modal: vi.fn(function (this: MockModal, element: HTMLElement) {
    this.element = element;
    this.hide = vi.fn();
    this.show = vi.fn();
  }),
}));

describe('handleInstances - Initialization', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should not throw error when no elements exist', () => {
    expect(() => handleInstances()).not.toThrow();
  });

  it('should not throw error when only add button exists', () => {
    const addButton = document.createElement('button');
    addButton.classList.add('pmf-instance-add');
    document.body.appendChild(addButton);

    expect(() => handleInstances()).not.toThrow();
  });

  it('should not throw error when only delete buttons exist', () => {
    const deleteButton = document.createElement('button');
    deleteButton.classList.add('pmf-instance-delete');
    deleteButton.setAttribute('data-delete-instance-id', '123');
    deleteButton.setAttribute('data-csrf-token', 'test-token');
    document.body.appendChild(deleteButton);

    expect(() => handleInstances()).not.toThrow();
  });
});

describe('handleInstances - Add Instance API Calls', () => {
  beforeEach(() => {
    document.body.innerHTML = '';

    // Create modal container
    const modalContainer = document.createElement('div');
    modalContainer.id = 'pmf-modal-add-instance';
    document.body.appendChild(modalContainer);

    // Create form inputs
    ['pmf-csrf-token', 'url', 'instance', 'comment', 'email', 'admin', 'password'].forEach((id) => {
      const input = document.createElement('input');
      input.id = id;
      input.value = id === 'pmf-csrf-token' ? 'test-token' : `test-${id}`;
      document.body.appendChild(input);
    });

    // Create add button
    const addButton = document.createElement('button');
    addButton.classList.add('pmf-instance-add');
    document.body.appendChild(addButton);

    // Create table
    const table = document.createElement('table');
    table.classList.add('table');
    const tbody = document.createElement('tbody');
    table.appendChild(tbody);
    document.body.appendChild(table);

    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should call addInstance API with correct parameters', async () => {
    const mockResponse = {
      added: '123',
      url: 'test-url',
      deleted: '',
    };

    vi.spyOn(api, 'addInstance').mockResolvedValue(mockResponse);

    handleInstances();

    const addButton = document.querySelector('.pmf-instance-add') as HTMLButtonElement;
    addButton.click();

    await vi.waitFor(() => {
      expect(api.addInstance).toHaveBeenCalledWith(
        'test-token',
        'test-url',
        'test-instance',
        'test-comment',
        'test-email',
        'test-admin',
        'test-password'
      );
    });
  });

  it('should call addInstance API even with empty values', async () => {
    // Update form inputs to empty values
    ['pmf-csrf-token', 'url', 'instance', 'comment', 'email', 'admin', 'password'].forEach((id) => {
      const input = document.getElementById(id) as HTMLInputElement;
      input.value = '';
    });

    const mockResponse = {
      added: '123',
      url: '',
      deleted: '',
    };

    vi.spyOn(api, 'addInstance').mockResolvedValue(mockResponse);

    handleInstances();

    const addButton = document.querySelector('.pmf-instance-add') as HTMLButtonElement;
    addButton.click();

    await vi.waitFor(() => {
      expect(api.addInstance).toHaveBeenCalledWith('', '', '', '', '', '', '');
    });
  });
});

describe('handleInstances - Delete Instance API Calls', () => {
  let confirmSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    document.body.innerHTML = '';

    // Mock window.confirm
    confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

    // Create table with row
    const table = document.createElement('table');
    table.classList.add('table');
    const tbody = document.createElement('tbody');
    const row = document.createElement('tr');
    row.id = 'row-instance-123';
    row.innerHTML = '<td>123</td>';
    tbody.appendChild(row);
    table.appendChild(tbody);
    document.body.appendChild(table);

    // Create delete button
    const deleteButton = document.createElement('button');
    deleteButton.classList.add('pmf-instance-delete');
    deleteButton.setAttribute('data-delete-instance-id', '123');
    deleteButton.setAttribute('data-csrf-token', 'test-token');
    document.body.appendChild(deleteButton);

    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should call deleteInstance API with correct parameters', async () => {
    const mockResponse = {
      added: '',
      url: '',
      deleted: '123',
    };

    vi.spyOn(api, 'deleteInstance').mockResolvedValue(mockResponse);

    handleInstances();

    const deleteButton = document.querySelector('.pmf-instance-delete') as HTMLButtonElement;
    deleteButton.click();

    await vi.waitFor(() => {
      expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
      expect(api.deleteInstance).toHaveBeenCalledWith('test-token', '123');
    });
  });

  it('should not call deleteInstance API when user cancels', async () => {
    confirmSpy.mockReturnValue(false);

    vi.spyOn(api, 'deleteInstance').mockResolvedValue({
      added: '',
      url: '',
      deleted: '123',
    });

    handleInstances();

    const deleteButton = document.querySelector('.pmf-instance-delete') as HTMLButtonElement;
    deleteButton.click();

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
    expect(api.deleteInstance).not.toHaveBeenCalled();
  });

  it('should handle multiple delete buttons', async () => {
    // Add second delete button
    const deleteButton2 = document.createElement('button');
    deleteButton2.classList.add('pmf-instance-delete');
    deleteButton2.setAttribute('data-delete-instance-id', '456');
    deleteButton2.setAttribute('data-csrf-token', 'token-456');
    document.body.appendChild(deleteButton2);

    // Add corresponding row
    const table = document.querySelector('.table tbody') as HTMLElement;
    const row2 = document.createElement('tr');
    row2.id = 'row-instance-456';
    row2.innerHTML = '<td>456</td>';
    table.appendChild(row2);

    const mockResponse = {
      added: '',
      url: '',
      deleted: '456',
    };

    vi.spyOn(api, 'deleteInstance').mockResolvedValue(mockResponse);

    handleInstances();

    const deleteButtons = document.querySelectorAll('.pmf-instance-delete');
    expect(deleteButtons.length).toBe(2);

    // Click second button
    (deleteButtons[1] as HTMLButtonElement).click();

    await vi.waitFor(() => {
      expect(api.deleteInstance).toHaveBeenCalledWith('token-456', '456');
    });
  });
});

describe('handleInstances - Modal Interactions', () => {
  beforeEach(() => {
    document.body.innerHTML = '';

    // Create modal container
    const modalContainer = document.createElement('div');
    modalContainer.id = 'pmf-modal-add-instance';
    document.body.appendChild(modalContainer);

    // Create form inputs
    ['pmf-csrf-token', 'url', 'instance', 'comment', 'email', 'admin', 'password'].forEach((id) => {
      const input = document.createElement('input');
      input.id = id;
      input.value = 'test';
      document.body.appendChild(input);
    });

    // Create add button
    const addButton = document.createElement('button');
    addButton.classList.add('pmf-instance-add');
    document.body.appendChild(addButton);

    // Create table
    const table = document.createElement('table');
    table.classList.add('table');
    table.innerHTML = '<tbody></tbody>';
    document.body.appendChild(table);

    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should initialize Bootstrap Modal', async () => {
    const mockResponse = {
      added: '123',
      url: 'test',
      deleted: '',
    };

    vi.spyOn(api, 'addInstance').mockResolvedValue(mockResponse);

    handleInstances();

    const addButton = document.querySelector('.pmf-instance-add') as HTMLButtonElement;
    addButton.click();

    await new Promise((resolve) => setTimeout(resolve, 50));

    const { Modal } = await import('bootstrap');
    expect(Modal).toHaveBeenCalled();
  });
});
