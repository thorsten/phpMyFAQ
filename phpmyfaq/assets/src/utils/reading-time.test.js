import '@testing-library/jest-dom/extend-expect';
import { calculateReadingTime } from './reading-time';

describe('calculateReadingTime', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div class="pmf-faq-body">
        This is a sample faq body text.
      </div>
      <div id="pmf-reading-time-minutes"></div>
    `;
  });

  test('should calculate and update reading time', () => {
    calculateReadingTime();

    const readingTimeElement = document.getElementById('pmf-reading-time-minutes');
    expect(readingTimeElement.innerText).toMatch(/^~\d+ min$/);
  });

  test('should display 0 min for an empty faq body', () => {
    document.getElementsByClassName('pmf-faq-body')[0].innerHTML = '';

    calculateReadingTime();

    const readingTimeElement = document.getElementById('pmf-reading-time-minutes');
    expect(readingTimeElement.innerText).toBe('0 min');
  });
});
