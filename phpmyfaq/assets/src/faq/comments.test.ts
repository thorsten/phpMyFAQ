import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock dependencies
vi.mock('bootstrap', () => ({
  Modal: {
    getInstance: vi.fn(),
  },
}));

vi.mock('../utils', () => ({
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

vi.mock('../api', () => ({
  createComment: vi.fn(),
}));

vi.mock('../comment/editor', () => ({
  renderCommentEditor: vi.fn(),
}));

import { handleSaveComment, handleComments } from './comments';
import { createComment } from '../api';
import { pushNotification, pushErrorNotification } from '../utils';
import { Modal } from 'bootstrap';
import { renderCommentEditor } from '../comment/editor';

describe('handleSaveComment', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when save button is missing', () => {
    document.body.innerHTML = '<div id="pmf-modal-add-comment"></div>';

    handleSaveComment();

    // No errors thrown
    expect(createComment).not.toHaveBeenCalled();
  });

  it('should do nothing when modal element is missing', () => {
    document.body.innerHTML = '<button id="pmf-button-save-comment"></button>';

    handleSaveComment();

    expect(createComment).not.toHaveBeenCalled();
  });

  it('should add was-validated class when form is invalid', async () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment">
        <form id="pmf-add-comment-form">
          <input type="text" required value="" />
          <textarea id="comment_text"></textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    handleSaveComment();

    const button = document.getElementById('pmf-button-save-comment') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      const form = document.getElementById('pmf-add-comment-form') as HTMLFormElement;
      expect(form.classList.contains('was-validated')).toBe(true);
    });

    expect(createComment).not.toHaveBeenCalled();
  });

  it('should submit form and show success notification', async () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment">Test comment</textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    const mockHide = vi.fn();
    vi.mocked(Modal.getInstance).mockReturnValue({ hide: mockHide } as unknown as Modal);

    vi.mocked(createComment).mockResolvedValue({
      success: 'Comment saved successfully',
    });

    handleSaveComment();

    const button = document.getElementById('pmf-button-save-comment') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createComment).toHaveBeenCalled();
    });

    expect(pushNotification).toHaveBeenCalledWith('Comment saved successfully');
  });

  it('should show error notification on error response', async () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment">Test</textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    vi.mocked(Modal.getInstance).mockReturnValue({ hide: vi.fn() } as unknown as Modal);
    vi.mocked(createComment).mockResolvedValue({
      error: 'Failed to save comment',
    });

    handleSaveComment();

    const button = document.getElementById('pmf-button-save-comment') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createComment).toHaveBeenCalled();
    });

    expect(pushErrorNotification).toHaveBeenCalledWith('Failed to save comment');
  });

  it('should add comment to DOM when commentData is returned', async () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment">New comment</textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
      <div id="comments"></div>
    `;

    vi.mocked(Modal.getInstance).mockReturnValue({ hide: vi.fn() } as unknown as Modal);
    vi.mocked(createComment).mockResolvedValue({
      success: 'Comment saved',
      commentData: {
        date: '1700000000',
        username: 'John Doe',
        email: 'john@example.com',
        gravatarUrl: 'https://gravatar.com/avatar/test',
        comment: '<p>This is a test comment</p>',
      },
    });

    handleSaveComment();

    const button = document.getElementById('pmf-button-save-comment') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(createComment).toHaveBeenCalled();
    });

    const commentsContainer = document.getElementById('comments') as HTMLElement;
    expect(commentsContainer.innerHTML).toContain('John Doe');
    expect(commentsContainer.innerHTML).toContain('john@example.com');
    expect(commentsContainer.innerHTML).toContain('This is a test comment');
    expect(commentsContainer.innerHTML).toContain('https://gravatar.com/avatar/test');
  });

  it('should reset form after successful submission', async () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment">Some text</textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    vi.mocked(Modal.getInstance).mockReturnValue({ hide: vi.fn() } as unknown as Modal);
    vi.mocked(createComment).mockResolvedValue({ success: 'Saved' });

    const form = document.getElementById('pmf-add-comment-form') as HTMLFormElement;
    const resetSpy = vi.spyOn(form, 'reset');

    handleSaveComment();

    const button = document.getElementById('pmf-button-save-comment') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(resetSpy).toHaveBeenCalled();
    });
  });

  it('should log error when createComment throws', async () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment">Test</textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.mocked(createComment).mockRejectedValue(new Error('Network error'));

    handleSaveComment();

    const button = document.getElementById('pmf-button-save-comment') as HTMLButtonElement;
    button.click();

    await vi.waitFor(() => {
      expect(consoleSpy).toHaveBeenCalled();
    });

    consoleSpy.mockRestore();
  });

  it('should initialize comment editor when modal has correct attributes', () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment" data-enable-editor="true" data-is-logged-in="true">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment"></textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    handleSaveComment();

    const modal = document.getElementById('pmf-modal-add-comment') as HTMLElement;

    // Trigger the show.bs.modal event
    modal.dispatchEvent(new Event('show.bs.modal'));

    expect(renderCommentEditor).toHaveBeenCalledWith('#comment_text');
  });

  it('should not initialize editor when data-enable-editor is not true', () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment" data-enable-editor="false" data-is-logged-in="true">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment"></textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    handleSaveComment();

    const modal = document.getElementById('pmf-modal-add-comment') as HTMLElement;
    modal.dispatchEvent(new Event('show.bs.modal'));

    expect(renderCommentEditor).not.toHaveBeenCalled();
  });

  it('should not initialize editor when user is not logged in', () => {
    document.body.innerHTML = `
      <div id="pmf-modal-add-comment" data-enable-editor="true" data-is-logged-in="false">
        <form id="pmf-add-comment-form">
          <textarea id="comment_text" name="comment"></textarea>
        </form>
      </div>
      <button id="pmf-button-save-comment">Save</button>
    `;

    handleSaveComment();

    const modal = document.getElementById('pmf-modal-add-comment') as HTMLElement;
    modal.dispatchEvent(new Event('show.bs.modal'));

    expect(renderCommentEditor).not.toHaveBeenCalled();
  });
});

describe('handleComments', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when no show-more elements exist', () => {
    document.body.innerHTML = '<div></div>';

    handleComments();

    // No errors thrown
  });

  it('should show long comment when show-more is clicked', () => {
    document.body.innerHTML = `
      <a href="#" class="pmf-comments-show-more" data-comment-id="42">Show more</a>
      <span class="comment-more-42 d-none">Full comment text here</span>
      <span class="comment-dots-42">...</span>
      <span class="comment-show-more-42">Show more link</span>
    `;

    handleComments();

    const showMore = document.querySelector('.pmf-comments-show-more') as HTMLElement;
    showMore.click();

    expect(document.querySelector('.comment-more-42')?.classList.contains('d-none')).toBe(false);
    expect(document.querySelector('.comment-dots-42')?.classList.contains('d-none')).toBe(true);
    expect(document.querySelector('.comment-show-more-42')?.classList.contains('d-none')).toBe(true);
  });

  it('should handle multiple show-more elements', () => {
    document.body.innerHTML = `
      <a href="#" class="pmf-comments-show-more" data-comment-id="1">Show more</a>
      <span class="comment-more-1 d-none">Comment 1 full</span>
      <span class="comment-dots-1">...</span>
      <span class="comment-show-more-1">Show more</span>

      <a href="#" class="pmf-comments-show-more" data-comment-id="2">Show more</a>
      <span class="comment-more-2 d-none">Comment 2 full</span>
      <span class="comment-dots-2">...</span>
      <span class="comment-show-more-2">Show more</span>
    `;

    handleComments();

    // Click only the first one
    const showMoreElements = document.querySelectorAll('.pmf-comments-show-more');
    (showMoreElements[0] as HTMLElement).click();

    // First comment should be expanded
    expect(document.querySelector('.comment-more-1')?.classList.contains('d-none')).toBe(false);
    // Second comment should still be hidden
    expect(document.querySelector('.comment-more-2')?.classList.contains('d-none')).toBe(true);
  });
});
