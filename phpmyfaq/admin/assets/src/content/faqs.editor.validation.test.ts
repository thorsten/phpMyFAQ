import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  applyValidationFeedback,
  clearValidationFeedback,
  getAnswerContent,
  showFirstValidationError,
  updateTabErrorBadges,
  validateFaqEditor,
} from './faqs.editor.validation';
import { getJoditEditor } from './editor';

const tabShow = vi.fn();

vi.mock('./editor', () => ({
  getJoditEditor: vi.fn(() => null),
}));
vi.mock('bootstrap', () => ({
  Tab: {
    getOrCreateInstance: vi.fn(() => ({ show: tabShow })),
  },
}));

describe('faqs.editor.validation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('getAnswerContent', () => {
    it('should prefer the Jodit editor content', () => {
      (getJoditEditor as ReturnType<typeof vi.fn>).mockReturnValueOnce({ value: '<p>Jodit content</p>' });
      document.body.innerHTML = '<textarea id="answer-markdown">Markdown</textarea>';

      expect(getAnswerContent()).toBe('<p>Jodit content</p>');
    });

    it('should fall back to the markdown editor, then the plain editor', () => {
      document.body.innerHTML = '<textarea id="answer-markdown">Markdown</textarea>';
      expect(getAnswerContent()).toBe('Markdown');

      document.body.innerHTML = '<textarea id="editor">Plain</textarea>';
      expect(getAnswerContent()).toBe('Plain');

      document.body.innerHTML = '';
      expect(getAnswerContent()).toBe('');
    });
  });

  describe('validateFaqEditor', () => {
    it('should report an empty question and empty answer', () => {
      document.body.innerHTML = `
        <input id="question" value="   " />
        <textarea id="editor"></textarea>
      `;

      const errors = validateFaqEditor();

      expect(errors).toContainEqual({
        fieldId: 'question',
        tabHref: '#tab-question-answer',
        feedbackId: 'question-required-feedback',
      });
      expect(errors).toContainEqual({
        fieldId: 'answer',
        tabHref: '#tab-question-answer',
        feedbackId: 'answer-invalid-feedback',
      });
    });

    it('should report a question containing a hash with the hash-specific feedback', () => {
      document.body.innerHTML = `
        <input id="question" value="What is #phpMyFAQ?" />
        <textarea id="editor">Answer</textarea>
      `;

      expect(validateFaqEditor()).toContainEqual({
        fieldId: 'question',
        tabHref: '#tab-question-answer',
        feedbackId: 'question-hash-feedback',
      });
    });

    it('should treat markup-only answers as empty', () => {
      document.body.innerHTML = `
        <input id="question" value="Question" />
        <textarea id="editor"><p>  </p><br></textarea>
      `;

      expect(validateFaqEditor()).toContainEqual({
        fieldId: 'answer',
        tabHref: '#tab-question-answer',
        feedbackId: 'answer-invalid-feedback',
      });
    });

    it('should accept an image-only answer as content', () => {
      document.body.innerHTML = `
        <input id="question" value="Question" />
        <textarea id="editor"><p><img src="screenshot.png" alt=""></p></textarea>
      `;

      expect(validateFaqEditor()).toEqual([]);
    });

    it('should report an invalid email address', () => {
      document.body.innerHTML = `
        <input id="question" value="Question" />
        <textarea id="editor">Answer</textarea>
        <input type="email" id="email" />
      `;

      const email = document.getElementById('email') as HTMLInputElement;
      email.value = 'not-an-email';

      expect(validateFaqEditor()).toContainEqual({
        fieldId: 'email',
        tabHref: '#tab-meta-data',
        feedbackId: 'email-invalid-feedback',
      });
    });

    it('should report missing category selection when the tree is present', () => {
      document.body.innerHTML = `
        <input id="question" value="Question" />
        <textarea id="editor">Answer</textarea>
        <div id="pmf-faq-category-tree">
          <input type="checkbox" name="categories[]" value="1" />
          <input type="checkbox" name="categories[]" value="2" />
        </div>
      `;

      expect(validateFaqEditor()).toContainEqual({
        fieldId: 'pmf-faq-category-tree',
        tabHref: '#tab-meta-data',
        feedbackId: 'categories-invalid-feedback',
      });

      (document.querySelector('input[name="categories[]"]') as HTMLInputElement).checked = true;
      expect(validateFaqEditor()).toEqual([]);
    });

    it('should return no errors for a valid form', () => {
      document.body.innerHTML = `
        <input id="question" value="What is phpMyFAQ?" />
        <textarea id="editor">An open source FAQ system.</textarea>
        <input type="email" id="email" value="user@example.org" />
      `;

      expect(validateFaqEditor()).toEqual([]);
    });
  });

  describe('applyValidationFeedback / clearValidationFeedback', () => {
    it('should mark fields invalid, reveal feedback elements, and clear again', () => {
      document.body.innerHTML = `
        <input id="question" value="" />
        <div id="question-required-feedback" class="invalid-feedback d-none" data-pmf-validation-feedback></div>
        <div id="answer-invalid-feedback" class="invalid-feedback d-none" data-pmf-validation-feedback></div>
      `;

      applyValidationFeedback([
        { fieldId: 'question', tabHref: '#tab-question-answer', feedbackId: 'question-required-feedback' },
        { fieldId: 'answer', tabHref: '#tab-question-answer', feedbackId: 'answer-invalid-feedback' },
      ]);

      const question = document.getElementById('question') as HTMLInputElement;
      const questionFeedback = document.getElementById('question-required-feedback') as HTMLElement;
      const answerFeedback = document.getElementById('answer-invalid-feedback') as HTMLElement;
      expect(question.classList.contains('is-invalid')).toBe(true);
      expect(questionFeedback.classList.contains('d-block')).toBe(true);
      expect(answerFeedback.classList.contains('d-none')).toBe(false);

      question.dispatchEvent(new Event('input'));
      expect(question.classList.contains('is-invalid')).toBe(false);

      clearValidationFeedback();
      expect(questionFeedback.classList.contains('d-none')).toBe(true);
      expect(answerFeedback.classList.contains('d-none')).toBe(true);
      expect(answerFeedback.classList.contains('d-block')).toBe(false);
    });
  });

  describe('updateTabErrorBadges', () => {
    it('should show per-tab error counts and hide empty badges', () => {
      document.body.innerHTML = `
        <span data-pmf-tab-error-badge="#tab-question-answer" class="badge d-none"></span>
        <span data-pmf-tab-error-badge="#tab-meta-data" class="badge d-none"></span>
      `;

      updateTabErrorBadges([
        { fieldId: 'question', tabHref: '#tab-question-answer', feedbackId: 'question-required-feedback' },
        { fieldId: 'answer', tabHref: '#tab-question-answer', feedbackId: 'answer-invalid-feedback' },
        { fieldId: 'email', tabHref: '#tab-meta-data', feedbackId: 'email-invalid-feedback' },
      ]);

      const badges = document.querySelectorAll<HTMLElement>('[data-pmf-tab-error-badge]');
      expect(badges[0].textContent).toBe('2');
      expect(badges[0].classList.contains('d-none')).toBe(false);
      expect(badges[1].textContent).toBe('1');

      updateTabErrorBadges([]);
      expect(badges[0].classList.contains('d-none')).toBe(true);
      expect(badges[0].textContent).toBe('');
    });
  });

  describe('showFirstValidationError', () => {
    it('should activate the tab of the first error and focus the field', () => {
      document.body.innerHTML = `
        <a data-bs-toggle="tab" href="#tab-meta-data"></a>
        <input id="email" />
      `;

      showFirstValidationError([{ fieldId: 'email', tabHref: '#tab-meta-data', feedbackId: 'email-invalid-feedback' }]);

      expect(tabShow).toHaveBeenCalled();
      expect(document.activeElement?.id).toBe('email');
    });

    it('should focus the feedback element when the field is not focusable', () => {
      document.body.innerHTML = `
        <a data-bs-toggle="tab" href="#tab-question-answer"></a>
        <div id="answer-invalid-feedback" tabindex="-1" data-pmf-validation-feedback></div>
      `;

      showFirstValidationError([
        { fieldId: 'answer', tabHref: '#tab-question-answer', feedbackId: 'answer-invalid-feedback' },
      ]);

      expect(document.activeElement?.id).toBe('answer-invalid-feedback');
    });

    it('should do nothing without errors', () => {
      showFirstValidationError([]);

      expect(tabShow).not.toHaveBeenCalled();
    });
  });
});
