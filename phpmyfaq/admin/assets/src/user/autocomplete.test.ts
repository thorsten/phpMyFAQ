import { describe, expect, test, vi, beforeEach } from 'vitest';
import autocomplete from 'autocompleter';
import { updateUser } from './users';
import { fetchUsers } from '../api';
import { addElement } from '../../../../assets/src/utils';
import './autocomplete'; // Ensure the event listener is registered

vi.mock('autocompleter', () => ({
  __esModule: true,
  default: vi.fn(),
}));

vi.mock('./users', () => ({
  updateUser: vi.fn(),
}));

vi.mock('../api', () => ({
  fetchUsers: vi.fn(),
}));

vi.mock('../../../../assets/src/utils', () => ({
  addElement: vi.fn(() => document.createElement('div')),
}));

describe('User Autocomplete', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <input id="pmf-user-list-autocomplete" />
    `;
  });

  test('should initialize autocomplete on DOMContentLoaded', () => {
    const mockAutocomplete = vi.fn();
    (autocomplete as unknown as vi.Mock).mockImplementation(mockAutocomplete);

    document.dispatchEvent(new Event('DOMContentLoaded'));

    expect(mockAutocomplete).toHaveBeenCalled();
  });

  test('should call updateUser on item select', async () => {
    const mockItem = { label: 'John Doe', value: '1' };
    const input = document.getElementById('pmf-user-list-autocomplete') as HTMLInputElement;

    const onSelect = (autocomplete as unknown as vi.Mock).mock.calls[0][0].onSelect;
    await onSelect(mockItem, input);

    expect(updateUser).toHaveBeenCalledWith('1');
  });

  test('should fetch and filter users', async () => {
    const mockUsers = [{ label: 'John Doe', value: '1' }];
    (fetchUsers as unknown as vi.Mock).mockResolvedValue(mockUsers);

    const fetch = (autocomplete as unknown as vi.Mock).mock.calls[0][0].fetch;
    const callback = vi.fn();
    await fetch('john', callback);

    expect(fetchUsers).toHaveBeenCalledWith('john');
    expect(callback).toHaveBeenCalledWith(mockUsers);
  });

  test('should render user suggestions', () => {
    const mockItem = { label: 'John Doe', value: '1' };
    const render = (autocomplete as unknown as vi.Mock).mock.calls[0][0].render;
    const result = render(mockItem, 'john');

    expect(addElement).toHaveBeenCalledWith('div', {
      classList: 'pmf-user-list-result border',
      innerHTML: '<strong>John</strong> Doe',
    });
    expect(result).toBeInstanceOf(HTMLDivElement);
  });
});
