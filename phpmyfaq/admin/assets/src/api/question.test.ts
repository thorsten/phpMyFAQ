import { describe, it, expect, vi, afterEach } from 'vitest';
import { toggleQuestionVisibility, reopenQuestion } from './question';

describe('Question API', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('toggleQuestionVisibility', () => {
    it('should toggle question visibility and return JSON response if successful', async () => {
      const mockResponse = { success: true, message: 'Visibility toggled' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const questionId = '123';
      const visibility = true;
      const csrfToken = 'csrfToken';
      const result = await toggleQuestionVisibility(questionId, visibility, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/question/visibility/toggle', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ questionId, visibility, csrfToken }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const questionId = '123';
      const visibility = true;
      const csrfToken = 'csrfToken';

      await expect(toggleQuestionVisibility(questionId, visibility, csrfToken)).rejects.toThrow(mockError);
    });
  });

  describe('reopenQuestion', () => {
    it('should reopen a question and return JSON response if successful', async () => {
      const mockResponse = { success: 'Question reopened' };
      global.fetch = vi.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve(mockResponse),
        } as Response)
      );

      const questionId = '42';
      const csrfToken = 'token';
      const result = await reopenQuestion(questionId, csrfToken);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith('./api/question/reopen', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ questionId, csrfToken }),
      });
    });

    it('should throw an error if fetch fails', async () => {
      const mockError = new Error('Fetch failed');
      global.fetch = vi.fn(() => Promise.reject(mockError));

      const questionId = '42';
      const csrfToken = 'token';

      await expect(reopenQuestion(questionId, csrfToken)).rejects.toThrow(mockError);
    });
  });
});
