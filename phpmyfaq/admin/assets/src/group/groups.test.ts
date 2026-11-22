import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleGroups } from './groups';
import {
  deleteGroup,
  fetchAllGroups,
  fetchAllMembers,
  fetchAllUsersForGroups,
  fetchCategoriesForRestrictions,
  fetchGroup,
  fetchGroupCategoryRestrictions,
  fetchGroupLanguageRestrictions,
  fetchGroupRights,
  fetchLanguagesForRestrictions,
  saveGroupCategoryRestrictions,
  saveGroupLanguageRestrictions,
  updateGroup,
  updateGroupMembers,
  updateGroupPermissions,
} from '../api';
import { pushErrorNotification, pushNotification } from '../../../../assets/src/utils';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', () => ({
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

const { modalShow, modalHide } = vi.hoisted(() => ({ modalShow: vi.fn(), modalHide: vi.fn() }));
vi.mock('bootstrap', () => ({
  Modal: class {
    show = modalShow;
    hide = modalHide;
    static getInstance = (): { hide: () => void } => ({ hide: modalHide });
    static getOrCreateInstance = (): { show: () => void } => ({ show: modalShow });
  },
}));

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 10));
};

const setupFullDom = (): void => {
  document.body.innerHTML = `
    <input id="pmf-group-filter" type="search" />
    <div id="pmf-group-list"></div>
    <div id="pmf-group-empty-state"></div>
    <div id="pmf-group-detail" class="d-none"
         data-csrf-update="csrf-update" data-csrf-members="csrf-members"
         data-csrf-permissions="csrf-permissions" data-csrf-delete="csrf-delete"
         data-msg-error="An error occurred.">
      <span id="pmf-selected-group-name"></span>
      <button id="pmf-delete-group-button" type="button"></button>
      <span id="pmf-member-count">0</span>
      <input id="update_group_name" type="text" />
      <textarea id="update_group_description"></textarea>
      <input id="update_group_auto_join" type="checkbox" />
      <button id="saveGroupDetails" type="button"></button>
      <input id="pmf-user-search" type="search" />
      <div id="pmf-user-search-results" class="d-none"></div>
      <ul id="pmf-member-list" data-label-remove="Remove member"></ul>
      <button id="saveMembersList" type="button"></button>
      <input id="pmf-permission-filter" type="search" />
      <button id="pmf-group-check-all" type="button"></button>
      <button id="pmf-group-uncheck-all" type="button"></button>
      <div id="pmf-permission-list">
        <div class="form-check">
          <input id="group_right_1" type="checkbox" value="1" class="form-check-input permission" />
          <label for="group_right_1">Add FAQ</label>
        </div>
        <div class="form-check">
          <input id="group_right_3" type="checkbox" value="3" class="form-check-input permission" />
          <label for="group_right_3">Edit FAQ</label>
        </div>
        <div class="form-check">
          <input id="group_right_5" type="checkbox" value="5" class="form-check-input permission" />
          <label for="group_right_5">Delete FAQ</label>
        </div>
      </div>
      <button id="saveGroupRights" type="button"></button>
      <div id="categoryRestrictionsBody" data-msg-empty="No permissions." data-msg-help="Help."
           data-msg-saved="Restrictions saved." data-csrf-token="csrf-restrictions"></div>
      <button id="saveCategoryRestrictions" type="button"></button>
      <div id="languageRestrictionsBody" data-msg-empty="No permissions." data-msg-help="Help."
           data-msg-saved="Language restrictions saved." data-csrf-token="csrf-language-restrictions"></div>
      <button id="saveLanguageRestrictions" type="button"></button>
    </div>
    <div id="pmf-group-delete-modal">
      <strong id="pmf-group-delete-name"></strong>
      <button id="pmf-confirm-group-delete" type="button"></button>
    </div>
  `;
};

const mockDefaultApis = (): void => {
  (fetchAllGroups as Mock).mockResolvedValue([
    { group_id: '1', name: 'Admins' },
    { group_id: '2', name: 'Users' },
  ]);
  (fetchAllUsersForGroups as Mock).mockResolvedValue([
    { user_id: '10', login: 'alice' },
    { user_id: '20', login: 'bob' },
  ]);
  (fetchAllMembers as Mock).mockResolvedValue([]);
  (fetchGroup as Mock).mockResolvedValue({
    group_id: '1',
    name: 'Admins',
    description: 'Admin group',
    auto_join: '1',
  });
  (fetchGroupRights as Mock).mockResolvedValue(['1', '3']);
  (fetchCategoriesForRestrictions as Mock).mockResolvedValue([]);
  (fetchGroupCategoryRestrictions as Mock).mockResolvedValue({});
  (fetchLanguagesForRestrictions as Mock).mockResolvedValue([]);
  (fetchGroupLanguageRestrictions as Mock).mockResolvedValue({});
  (saveGroupCategoryRestrictions as Mock).mockResolvedValue({ ok: true } as Response);
  (saveGroupLanguageRestrictions as Mock).mockResolvedValue({ ok: true } as Response);
  (updateGroup as Mock).mockResolvedValue({ success: 'saved' });
  (updateGroupMembers as Mock).mockResolvedValue({ success: 'saved' });
  (updateGroupPermissions as Mock).mockResolvedValue({ success: 'saved' });
  (deleteGroup as Mock).mockResolvedValue({ success: 'deleted' });
};

const selectFirstGroup = async (): Promise<void> => {
  (document.querySelector('.pmf-group-item') as HTMLButtonElement).click();
  await flushPromises();
};

const selectRestrictionValues = (containerId: string, rightId: string, values: string[]): void => {
  const select = document.querySelector<HTMLSelectElement>(`#${containerId} select[data-right-id="${rightId}"]`);
  [...(select as HTMLSelectElement).options].forEach((option: HTMLOptionElement): void => {
    option.selected = values.includes(option.value);
  });
};

// Rights 1 and 3 are ticked, right 5 is not. Right 1 carries a restriction, right 3 none.
const setupRestrictions = async (): Promise<void> => {
  setupFullDom();
  mockDefaultApis();
  (fetchCategoriesForRestrictions as Mock).mockResolvedValue([
    { id: 1, name: 'Root', parent_id: 0, level: 0 },
    { id: 2, name: 'Child', parent_id: 1, level: 1 },
  ]);
  (fetchGroupCategoryRestrictions as Mock).mockResolvedValue({ '1': [2] });
  (fetchLanguagesForRestrictions as Mock).mockResolvedValue([
    { code: 'en', label: 'English' },
    { code: 'de', label: 'Deutsch' },
  ]);
  (fetchGroupLanguageRestrictions as Mock).mockResolvedValue({ '1': ['de'] });

  await handleGroups();
  await selectFirstGroup();
};

const clickAndFlush = async (buttonId: string): Promise<void> => {
  (document.getElementById(buttonId) as HTMLButtonElement).click();
  await flushPromises();
};

describe('handleGroups', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should return early when #pmf-group-list is missing', async () => {
    document.body.innerHTML = '<div></div>';

    await handleGroups();

    expect(fetchAllUsersForGroups).not.toHaveBeenCalled();
    expect(fetchAllGroups).not.toHaveBeenCalled();
  });

  it('should render fetched groups as list items', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const items = document.querySelectorAll('.pmf-group-item');
    expect(items.length).toBe(2);
    expect(items[0].textContent).toBe('Admins');
    expect((items[0] as HTMLElement).dataset.groupId).toBe('1');
    expect(items[1].textContent).toBe('Users');
  });

  it('should filter the group list by name', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();

    const filter = document.getElementById('pmf-group-filter') as HTMLInputElement;
    filter.value = 'adm';
    filter.dispatchEvent(new Event('input'));

    const items = document.querySelectorAll<HTMLElement>('.pmf-group-item');
    expect(items[0].classList.contains('d-none')).toBe(false);
    expect(items[1].classList.contains('d-none')).toBe(true);
  });

  it('should load group details and show the detail card when a group is selected', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

    await handleGroups();
    await selectFirstGroup();

    expect(fetchGroup).toHaveBeenCalledWith('1');
    expect((document.getElementById('update_group_name') as HTMLInputElement).value).toBe('Admins');
    expect((document.getElementById('update_group_description') as HTMLTextAreaElement).value).toBe('Admin group');
    expect((document.getElementById('update_group_auto_join') as HTMLInputElement).checked).toBe(true);
    expect(document.getElementById('pmf-selected-group-name')?.textContent).toBe('Admins');

    expect((document.getElementById('group_right_1') as HTMLInputElement).checked).toBe(true);
    expect((document.getElementById('group_right_3') as HTMLInputElement).checked).toBe(true);
    expect((document.getElementById('group_right_5') as HTMLInputElement).checked).toBe(false);

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(1);
    expect(document.getElementById('pmf-member-count')?.textContent).toBe('1');

    expect(document.getElementById('pmf-group-empty-state')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-group-detail')?.classList.contains('d-none')).toBe(false);
    expect((document.querySelector('.pmf-group-item') as HTMLElement).classList.contains('active')).toBe(true);
  });

  it('should suggest only non-members in the user search and add the clicked user', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([{ user_id: '10', login: 'alice' }]);

    await handleGroups();
    await selectFirstGroup();

    const search = document.getElementById('pmf-user-search') as HTMLInputElement;
    search.value = 'b';
    search.dispatchEvent(new Event('input'));

    const results = document.getElementById('pmf-user-search-results') as HTMLElement;
    expect(results.classList.contains('d-none')).toBe(false);
    const suggestions = results.querySelectorAll('button');
    expect(suggestions.length).toBe(1);
    expect(suggestions[0].textContent).toBe('bob');

    suggestions[0].click();

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(2);
    expect(document.getElementById('pmf-member-count')?.textContent).toBe('2');
    expect(results.classList.contains('d-none')).toBe(true);
    expect(search.value).toBe('');
  });

  it('should remove a member row via its remove button', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([
      { user_id: '10', login: 'alice' },
      { user_id: '20', login: 'bob' },
    ]);

    await handleGroups();
    await selectFirstGroup();

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(2);
    (document.querySelector('#pmf-member-list li button') as HTMLButtonElement).click();

    expect(document.querySelectorAll('#pmf-member-list li').length).toBe(1);
    expect(document.getElementById('pmf-member-count')?.textContent).toBe('1');
  });

  it('should save group details and update the visible group name', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('update_group_name') as HTMLInputElement).value = 'Renamed';
    (document.getElementById('update_group_auto_join') as HTMLInputElement).checked = false;
    (document.getElementById('saveGroupDetails') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateGroup).toHaveBeenCalledWith('1', 'Renamed', 'Admin group', false, 'csrf-update');
    expect(pushNotification).toHaveBeenCalledWith('saved');
    expect(document.getElementById('pmf-selected-group-name')?.textContent).toBe('Renamed');
    expect((document.querySelector('.pmf-group-item') as HTMLElement).textContent).toBe('Renamed');
  });

  it('should push an error notification when saving details fails', async () => {
    setupFullDom();
    mockDefaultApis();
    (updateGroup as Mock).mockResolvedValue({ error: 'nope' });

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('saveGroupDetails') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('nope');
  });

  it('should push an error notification when saving details rejects with a network failure', async () => {
    setupFullDom();
    mockDefaultApis();
    (updateGroup as Mock).mockRejectedValue(new Error('network down'));

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('saveGroupDetails') as HTMLButtonElement).click();
    await flushPromises();

    expect(pushErrorNotification).toHaveBeenCalledWith('An error occurred.');
  });

  it('should save the member list as member IDs', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchAllMembers as Mock).mockResolvedValue([
      { user_id: '10', login: 'alice' },
      { user_id: '20', login: 'bob' },
    ]);

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('saveMembersList') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateGroupMembers).toHaveBeenCalledWith('1', [10, 20], 'csrf-members');
    expect(pushNotification).toHaveBeenCalledWith('saved');
  });

  it('should save checked permissions as right IDs', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    // fetchGroupRights checked rights 1 and 3; additionally check right 5
    (document.getElementById('group_right_5') as HTMLInputElement).checked = true;
    (document.getElementById('saveGroupRights') as HTMLButtonElement).click();
    await flushPromises();

    expect(updateGroupPermissions).toHaveBeenCalledWith('1', [1, 3, 5], 'csrf-permissions');
    expect(pushNotification).toHaveBeenCalledWith('saved');
  });

  it('should check and uncheck all permissions via the toggle buttons', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('pmf-group-check-all') as HTMLButtonElement).click();
    document.querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission').forEach((checkbox) => {
      expect(checkbox.checked).toBe(true);
    });

    (document.getElementById('pmf-group-uncheck-all') as HTMLButtonElement).click();
    document.querySelectorAll<HTMLInputElement>('#pmf-permission-list input.permission').forEach((checkbox) => {
      expect(checkbox.checked).toBe(false);
    });
  });

  it('should delete the group after confirmation and return to the empty state', async () => {
    setupFullDom();
    mockDefaultApis();

    await handleGroups();
    await selectFirstGroup();

    (document.getElementById('pmf-delete-group-button') as HTMLButtonElement).click();
    expect(document.getElementById('pmf-group-delete-name')?.textContent).toBe('Admins');
    expect(modalShow).toHaveBeenCalled();

    (document.getElementById('pmf-confirm-group-delete') as HTMLButtonElement).click();
    await flushPromises();

    expect(deleteGroup).toHaveBeenCalledWith('1', 'csrf-delete');
    expect(pushNotification).toHaveBeenCalledWith('deleted');
    expect(document.getElementById('pmf-group-detail')?.classList.contains('d-none')).toBe(true);
    expect(document.getElementById('pmf-group-empty-state')?.classList.contains('d-none')).toBe(false);
  });

  // Keep this test last: cachedCategories and cachedLanguages are module-level
  // and, once populated with a non-empty list, are reused by any test that
  // runs after it.
  it('should indent subcategories in the category restrictions options and render language restriction options', async () => {
    setupFullDom();
    mockDefaultApis();
    (fetchCategoriesForRestrictions as Mock).mockResolvedValue([
      { id: 1, name: 'Guides', parent_id: 0, level: 0 },
      { id: 2, name: 'Setup', parent_id: 1, level: 1 },
    ]);
    (fetchLanguagesForRestrictions as Mock).mockResolvedValue([
      { code: 'en', label: 'English' },
      { code: 'de', label: 'Deutsch' },
    ]);
    (fetchGroupLanguageRestrictions as Mock).mockResolvedValue({ '1': ['de'] });

    await handleGroups();
    await selectFirstGroup();

    // fetchGroupRights checks rights 1 and 3, so restriction selects render.
    const options = document.querySelectorAll<HTMLOptionElement>(
      '#categoryRestrictionsBody select[data-right-id="1"] option'
    );
    expect(options.length).toBe(2);
    expect(options[0].textContent).toBe('Guides');
    expect(options[1].textContent).toBe('\u00A0\u00A0\u00A0Setup');

    const languageOptions = document.querySelectorAll<HTMLOptionElement>(
      '#languageRestrictionsBody select[data-right-id="1"] option'
    );
    expect(languageOptions.length).toBe(2);
    expect(languageOptions[0].textContent).toBe('English');
    expect(languageOptions[1].selected).toBe(true);

    // Each select must be reachable from its own label for screen readers.
    const languageSelect = document.querySelector<HTMLSelectElement>(
      '#languageRestrictionsBody select[data-right-id="1"]'
    );
    const languageLabel = languageSelect?.previousElementSibling as HTMLLabelElement | null;
    expect(languageSelect?.id).toBe('group-language-restriction-1');
    expect(languageLabel?.htmlFor).toBe('group-language-restriction-1');

    selectRestrictionValues('languageRestrictionsBody', '1', ['en', 'de']);
    selectRestrictionValues('languageRestrictionsBody', '3', ['en']);
    (document.getElementById('saveLanguageRestrictions') as HTMLButtonElement).click();
    await flushPromises();

    expect(saveGroupLanguageRestrictions).toHaveBeenCalledWith('1', '1', ['en', 'de'], 'csrf-language-restrictions');
    expect(saveGroupLanguageRestrictions).toHaveBeenCalledWith('1', '3', ['en'], 'csrf-language-restrictions');
    expect(pushNotification).toHaveBeenCalledWith('Language restrictions saved.');
  });

  it('should submit only the rights whose language restriction changed', async () => {
    await setupRestrictions();

    selectRestrictionValues('languageRestrictionsBody', '1', ['en']);
    await clickAndFlush('saveLanguageRestrictions');

    // Right 3 is ticked with an untouched empty list and right 5 is not ticked: neither is submitted.
    expect(saveGroupLanguageRestrictions).toHaveBeenCalledTimes(1);
    expect(saveGroupLanguageRestrictions).toHaveBeenCalledWith('1', '1', ['en'], 'csrf-language-restrictions');
    expect(pushNotification).toHaveBeenCalledWith('Language restrictions saved.');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });

  it('should submit a language restriction the administrator emptied', async () => {
    await setupRestrictions();

    selectRestrictionValues('languageRestrictionsBody', '1', []);
    await clickAndFlush('saveLanguageRestrictions');

    expect(saveGroupLanguageRestrictions).toHaveBeenCalledTimes(1);
    expect(saveGroupLanguageRestrictions).toHaveBeenCalledWith('1', '1', [], 'csrf-language-restrictions');
  });

  it('should not call the endpoint when no language restriction changed', async () => {
    await setupRestrictions();

    await clickAndFlush('saveLanguageRestrictions');

    expect(saveGroupLanguageRestrictions).not.toHaveBeenCalled();
    expect(pushNotification).toHaveBeenCalledWith('Language restrictions saved.');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });

  it('should not resend a saved language restriction on the next save', async () => {
    await setupRestrictions();

    selectRestrictionValues('languageRestrictionsBody', '1', ['en']);
    await clickAndFlush('saveLanguageRestrictions');
    await clickAndFlush('saveLanguageRestrictions');

    expect(saveGroupLanguageRestrictions).toHaveBeenCalledTimes(1);
  });

  it('should show the reason the server gives for a refused language restriction', async () => {
    await setupRestrictions();
    (saveGroupLanguageRestrictions as Mock).mockResolvedValueOnce({
      ok: false,
      json: async () => ({ error: 'The permission "Add users" keeps its language restrictions (Deutsch).' }),
    } as Response);

    selectRestrictionValues('languageRestrictionsBody', '1', ['en']);
    await clickAndFlush('saveLanguageRestrictions');

    expect(pushErrorNotification).toHaveBeenCalledTimes(1);
    expect(pushErrorNotification).toHaveBeenCalledWith(
      'The permission "Add users" keeps its language restrictions (Deutsch).'
    );
  });

  it('should resend a language restriction whose save was refused', async () => {
    await setupRestrictions();
    (saveGroupLanguageRestrictions as Mock).mockResolvedValueOnce({ ok: false } as Response);

    selectRestrictionValues('languageRestrictionsBody', '1', ['en']);
    await clickAndFlush('saveLanguageRestrictions');
    expect(pushErrorNotification).toHaveBeenCalledTimes(1);

    await clickAndFlush('saveLanguageRestrictions');

    expect(saveGroupLanguageRestrictions).toHaveBeenCalledTimes(2);
    expect(saveGroupLanguageRestrictions).toHaveBeenLastCalledWith('1', '1', ['en'], 'csrf-language-restrictions');
  });

  it('should submit only the rights whose category restriction changed', async () => {
    await setupRestrictions();

    selectRestrictionValues('categoryRestrictionsBody', '1', ['1']);
    await clickAndFlush('saveCategoryRestrictions');

    expect(saveGroupCategoryRestrictions).toHaveBeenCalledTimes(1);
    expect(saveGroupCategoryRestrictions).toHaveBeenCalledWith('1', '1', [1], 'csrf-restrictions');
    expect(pushNotification).toHaveBeenCalledWith('Restrictions saved.');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });

  it('should submit a category restriction the administrator emptied', async () => {
    await setupRestrictions();

    selectRestrictionValues('categoryRestrictionsBody', '1', []);
    await clickAndFlush('saveCategoryRestrictions');

    expect(saveGroupCategoryRestrictions).toHaveBeenCalledTimes(1);
    expect(saveGroupCategoryRestrictions).toHaveBeenCalledWith('1', '1', [], 'csrf-restrictions');
  });

  it('should not call the endpoint when no category restriction changed', async () => {
    await setupRestrictions();

    await clickAndFlush('saveCategoryRestrictions');

    expect(saveGroupCategoryRestrictions).not.toHaveBeenCalled();
    expect(pushNotification).toHaveBeenCalledWith('Restrictions saved.');
    expect(pushErrorNotification).not.toHaveBeenCalled();
  });

  it('should not resend a saved category restriction on the next save', async () => {
    await setupRestrictions();

    selectRestrictionValues('categoryRestrictionsBody', '1', ['1']);
    await clickAndFlush('saveCategoryRestrictions');
    await clickAndFlush('saveCategoryRestrictions');

    expect(saveGroupCategoryRestrictions).toHaveBeenCalledTimes(1);
  });
});
