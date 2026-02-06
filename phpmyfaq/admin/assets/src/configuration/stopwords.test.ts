import { describe, it, expect, vi, beforeEach, Mock } from 'vitest';
import { handleStopWords } from './stopwords';
import { fetchByLanguage, postStopWord, removeStopWord } from '../api';

vi.mock('../api');
vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return {
    ...actual,
    pushErrorNotification: vi.fn(),
    pushNotification: vi.fn(),
  };
});

const setupBasicDom = (): void => {
  document.body.innerHTML = `
    <select id="pmf-stop-words-language-selector">
      <option value="none">Select language</option>
      <option value="en">English</option>
      <option value="de">German</option>
    </select>
    <button id="pmf-stop-words-add-input" disabled>Add</button>
    <div id="pmf-stop-words-loading-indicator"></div>
    <div id="pmf-stopwords-content"></div>
    <input type="hidden" id="pmf-csrf-token" value="test-csrf-token" />
  `;
};

describe('StopWords Functions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('handleStopWords', () => {
    it('should do nothing when language selector is missing', () => {
      document.body.innerHTML = '<div></div>';

      handleStopWords();

      expect(fetchByLanguage).not.toHaveBeenCalled();
    });

    it('should fetch stop words when language is changed', async () => {
      setupBasicDom();

      const stopWords = [
        { id: 1, lang: 'en', stopword: 'the' },
        { id: 2, lang: 'en', stopword: 'and' },
      ];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchByLanguage).toHaveBeenCalledWith('en');
    });

    it('should not fetch stop words when "none" is selected', async () => {
      setupBasicDom();

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'none';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(fetchByLanguage).not.toHaveBeenCalled();
    });

    it('should enable add button after fetching stop words', async () => {
      setupBasicDom();

      (fetchByLanguage as Mock).mockResolvedValue([]);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      const addButton = document.getElementById('pmf-stop-words-add-input') as HTMLButtonElement;

      expect(addButton.hasAttribute('disabled')).toBe(true);

      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(addButton.hasAttribute('disabled')).toBe(false);
    });

    it('should populate stop words content after fetching', async () => {
      setupBasicDom();

      const stopWords = [
        { id: 1, lang: 'en', stopword: 'the' },
        { id: 2, lang: 'en', stopword: 'and' },
      ];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const content = document.getElementById('pmf-stopwords-content') as HTMLElement;
      expect(content.innerHTML).toContain('table');
      expect(content.innerHTML).toContain('stopword_1_en');
      expect(content.innerHTML).toContain('stopword_2_en');
    });

    it('should show loading indicator while fetching and hide it after', async () => {
      setupBasicDom();

      (fetchByLanguage as Mock).mockResolvedValue([]);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const loadingIndicator = document.getElementById('pmf-stop-words-loading-indicator') as HTMLElement;
      expect(loadingIndicator.innerHTML).toBe('');
    });

    it('should add a new empty stop word input when add button is clicked', async () => {
      setupBasicDom();

      (fetchByLanguage as Mock).mockResolvedValue([]);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const addButton = document.getElementById('pmf-stop-words-add-input') as HTMLButtonElement;
      addButton.click();

      const content = document.getElementById('pmf-stopwords-content') as HTMLElement;
      expect(content.innerHTML).toContain('stopword_-1_en');
    });

    it('should save stop word on blur when value changes', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);
      (postStopWord as Mock).mockResolvedValue({ success: true });

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;

      // Focus to save old value
      input.dispatchEvent(new Event('focus'));
      expect(input.getAttribute('data-old-value')).toBe('the');

      // Change value and blur to trigger save
      input.value = 'updated';
      input.dispatchEvent(new Event('blur'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(postStopWord).toHaveBeenCalledWith('test-csrf-token', 'updated', 1, 'en');
    });

    it('should show success styling after saving stop word', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);
      (postStopWord as Mock).mockResolvedValue({ success: true });

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;
      input.dispatchEvent(new Event('focus'));
      input.value = 'updated';
      input.dispatchEvent(new Event('blur'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(input.style.borderColor).toBe('rgb(25, 135, 84)');
    });

    it('should show error alert when saving stop word fails', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);
      (postStopWord as Mock).mockRejectedValue(new Error('Save failed'));

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;
      input.dispatchEvent(new Event('focus'));
      input.value = 'updated';
      input.dispatchEvent(new Event('blur'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const errorAlert = document.querySelector('.alert-danger') as HTMLElement;
      expect(errorAlert).not.toBeNull();
      expect(errorAlert.innerText).toBe('Save failed');
    });

    it('should not save when value has not changed', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;
      input.dispatchEvent(new Event('focus'));
      // Do not change the value
      input.dispatchEvent(new Event('blur'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(postStopWord).not.toHaveBeenCalled();
    });

    it('should remove empty new input on blur when value is unchanged', async () => {
      setupBasicDom();

      (fetchByLanguage as Mock).mockResolvedValue([]);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      // Click the add button to create a new empty input (id=-1)
      const addButton = document.getElementById('pmf-stop-words-add-input') as HTMLButtonElement;
      addButton.click();

      const input = document.getElementById('stopword_-1_en') as HTMLInputElement;
      expect(input).not.toBeNull();

      // Focus then blur without changing value
      input.dispatchEvent(new Event('focus'));
      input.dispatchEvent(new Event('blur'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      // Element should be removed
      expect(document.getElementById('stopword_-1_en')).toBeNull();
    });

    it('should delete stop word on Enter when input is empty', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);
      (removeStopWord as Mock).mockResolvedValue({ success: true });

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;
      input.value = '';
      input.dispatchEvent(new KeyboardEvent('keydown', { keyCode: 13 }));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(removeStopWord).toHaveBeenCalledWith('test-csrf-token', 1, 'en');
    });

    it('should blur input on Enter when input has a value', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);
      (postStopWord as Mock).mockResolvedValue({ success: true });

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;
      const blurSpy = vi.spyOn(input, 'blur');

      input.dispatchEvent(new Event('focus'));
      input.value = 'updated';
      input.dispatchEvent(new KeyboardEvent('keydown', { keyCode: 13 }));

      expect(blurSpy).toHaveBeenCalled();
    });

    it('should show error alert when deleting stop word fails', async () => {
      setupBasicDom();

      const stopWords = [{ id: 1, lang: 'en', stopword: 'the' }];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);
      (removeStopWord as Mock).mockRejectedValue(new Error('Delete failed'));

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const input = document.getElementById('stopword_1_en') as HTMLInputElement;
      input.value = '';
      input.dispatchEvent(new KeyboardEvent('keydown', { keyCode: 13 }));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const errorAlert = document.querySelector('.alert-danger') as HTMLElement;
      expect(errorAlert).not.toBeNull();
      expect(errorAlert.innerText).toBe('Delete failed');
    });

    it('should handle fetch error gracefully', async () => {
      setupBasicDom();

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      (fetchByLanguage as Mock).mockRejectedValue(new Error('Network error'));

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      expect(consoleSpy).toHaveBeenCalledWith('Error fetching stop words:', 'Network error');
      consoleSpy.mockRestore();
    });

    it('should build table with correct number of rows based on maxCols', async () => {
      setupBasicDom();

      // 5 stop words with maxCols=4 should create 2 rows
      const stopWords = [
        { id: 1, lang: 'en', stopword: 'the' },
        { id: 2, lang: 'en', stopword: 'and' },
        { id: 3, lang: 'en', stopword: 'but' },
        { id: 4, lang: 'en', stopword: 'for' },
        { id: 5, lang: 'en', stopword: 'not' },
      ];
      (fetchByLanguage as Mock).mockResolvedValue(stopWords);

      handleStopWords();

      const selector = document.getElementById('pmf-stop-words-language-selector') as HTMLSelectElement;
      selector.value = 'en';
      selector.dispatchEvent(new Event('change'));

      await new Promise((resolve) => setTimeout(resolve, 10));

      const content = document.getElementById('pmf-stopwords-content') as HTMLElement;
      const rows = content.querySelectorAll('tr');
      expect(rows.length).toBe(2);

      // The first row should have 4 inputs, the second row should have 1
      expect(rows[0].querySelectorAll('td').length).toBe(4);
      expect(rows[1].querySelectorAll('td').length).toBe(1);
    });
  });
});
