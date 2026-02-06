import { describe, it, expect, vi, beforeEach } from 'vitest';
import { handleDeleteComments } from './comment';

vi.mock('../../../../assets/src/utils', async (importOriginal) => {
  const actual = (await importOriginal()) as Record<string, unknown>;
  return { ...actual };
});

const mockFetch = vi.fn();
global.fetch = mockFetch;

const flushPromises = async (): Promise<void> => {
  await new Promise((resolve) => setTimeout(resolve, 50));
};

describe('handleDeleteComments', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('when buttons do not exist', () => {
    it('should do nothing when neither button exists', () => {
      document.body.innerHTML = '<div></div>';

      handleDeleteComments();

      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('should not throw when buttons are absent', () => {
      document.body.innerHTML = '<div></div>';

      expect(() => handleDeleteComments()).not.toThrow();
    });
  });

  describe('FAQ comment deletion', () => {
    const setupFaqDom = () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="pmf-comments-selected-faq">
          <table>
            <tbody>
              <tr>
                <td>
                  <label>
                    <input type="checkbox" name="faq_comments[]" value="1" checked />
                  </label>
                </td>
                <td>Comment 1</td>
              </tr>
              <tr>
                <td>
                  <label>
                    <input type="checkbox" name="faq_comments[]" value="2" />
                  </label>
                </td>
                <td>Comment 2</td>
              </tr>
            </tbody>
          </table>
        </form>
        <button id="pmf-button-delete-faq-comments">Delete FAQ Comments</button>
      `;
    };

    it('should remove checked rows on successful deletion', async () => {
      setupFaqDom();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const checkedInputs = document.querySelectorAll('tr td input:checked');
      expect(checkedInputs.length).toBe(0);

      // The unchecked row should still exist
      const remainingRows = document.querySelectorAll('tr');
      expect(remainingRows.length).toBe(1);
    });

    it('should show alert on error response from server', async () => {
      setupFaqDom();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: false, error: 'Deletion failed' }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const alertDiv = responseMessage.querySelector('.alert.alert-danger') as HTMLElement;
      expect(alertDiv).not.toBeNull();
      expect(alertDiv.innerText).toBe('Deletion failed');
    });

    it('should show alert on network error (non-ok response)', async () => {
      setupFaqDom();

      mockFetch.mockResolvedValue({
        ok: false,
        status: 500,
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const alertDiv = responseMessage.querySelector('.alert.alert-danger') as HTMLElement;
      expect(alertDiv).not.toBeNull();
      expect(alertDiv.innerText).toBe('Network response was not ok.');
    });

    it('should show alert when fetch rejects', async () => {
      setupFaqDom();

      mockFetch.mockRejectedValue(new Error('Failed to fetch'));

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const alertDiv = responseMessage.querySelector('.alert.alert-danger') as HTMLElement;
      expect(alertDiv).not.toBeNull();
      expect(alertDiv.innerText).toBe('Failed to fetch');
    });

    it('should call fetch with correct method, headers, and body', async () => {
      setupFaqDom();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(mockFetch).toHaveBeenCalledTimes(1);

      const [url, options] = mockFetch.mock.calls[0];
      expect(url).toContain('api/content/comments');
      expect(options.method).toBe('DELETE');
      expect(options.headers).toEqual({
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      });

      const body = JSON.parse(options.body);
      expect(body.type).toBe('faq');
      expect(body.data).toBeDefined();
    });
  });

  describe('News comment deletion', () => {
    const setupNewsDom = () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="pmf-comments-selected-news">
          <table>
            <tbody>
              <tr>
                <td>
                  <label>
                    <input type="checkbox" name="news_comments[]" value="10" checked />
                  </label>
                </td>
                <td>News Comment 1</td>
              </tr>
              <tr>
                <td>
                  <label>
                    <input type="checkbox" name="news_comments[]" value="20" checked />
                  </label>
                </td>
                <td>News Comment 2</td>
              </tr>
            </tbody>
          </table>
        </form>
        <button id="pmf-button-delete-news-comments">Delete News Comments</button>
      `;
    };

    it('should remove checked rows on successful deletion', async () => {
      setupNewsDom();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-news-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const checkedInputs = document.querySelectorAll('tr td input:checked');
      expect(checkedInputs.length).toBe(0);

      // Both rows were checked, so both should be removed
      const remainingRows = document.querySelectorAll('tr');
      expect(remainingRows.length).toBe(0);
    });

    it('should call fetch with type "news" in the body', async () => {
      setupNewsDom();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-news-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      expect(mockFetch).toHaveBeenCalledTimes(1);

      const [, options] = mockFetch.mock.calls[0];
      const body = JSON.parse(options.body);
      expect(body.type).toBe('news');
      expect(body.data).toBeDefined();
    });

    it('should show alert on error response from server', async () => {
      setupNewsDom();

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: false, error: 'News deletion failed' }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-news-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const alertDiv = responseMessage.querySelector('.alert.alert-danger') as HTMLElement;
      expect(alertDiv).not.toBeNull();
      expect(alertDiv.innerText).toBe('News deletion failed');
    });

    it('should show alert on network error', async () => {
      setupNewsDom();

      mockFetch.mockRejectedValue(new Error('Network failure'));

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-news-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const responseMessage = document.getElementById('returnMessage') as HTMLElement;
      const alertDiv = responseMessage.querySelector('.alert.alert-danger') as HTMLElement;
      expect(alertDiv).not.toBeNull();
      expect(alertDiv.innerText).toBe('Network failure');
    });
  });

  describe('fetch call details', () => {
    it('should serialize form data and include it in the request body', async () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="pmf-comments-selected-faq">
          <input type="checkbox" name="faq_comments[]" value="5" checked />
          <input type="checkbox" name="faq_comments[]" value="10" checked />
        </form>
        <button id="pmf-button-delete-faq-comments">Delete</button>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const [, options] = mockFetch.mock.calls[0];
      const body = JSON.parse(options.body);
      expect(body.type).toBe('faq');
      expect(body.data).toBeDefined();
      expect(body.data['faq_comments[]']).toBeDefined();
    });

    it('should use DELETE method for the fetch call', async () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="pmf-comments-selected-faq">
          <input type="checkbox" name="faq_comments[]" value="1" checked />
        </form>
        <button id="pmf-button-delete-faq-comments">Delete</button>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-faq-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const [, options] = mockFetch.mock.calls[0];
      expect(options.method).toBe('DELETE');
    });

    it('should include correct Accept and Content-Type headers', async () => {
      document.body.innerHTML = `
        <div id="returnMessage"></div>
        <form id="pmf-comments-selected-news">
          <input type="checkbox" name="news_comments[]" value="1" checked />
        </form>
        <button id="pmf-button-delete-news-comments">Delete</button>
      `;

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      });

      handleDeleteComments();

      const button = document.getElementById('pmf-button-delete-news-comments') as HTMLButtonElement;
      button.click();

      await flushPromises();

      const [, options] = mockFetch.mock.calls[0];
      expect(options.headers).toEqual({
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      });
    });
  });
});
