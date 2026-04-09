import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('../utils', () => ({
  pushNotification: vi.fn(),
  pushErrorNotification: vi.fn(),
}));

vi.mock('../api', () => ({
  saveVoting: vi.fn(),
}));

import { handleUserVoting } from './voting';
import { saveVoting } from '../api';
import { pushNotification, pushErrorNotification } from '../utils';

describe('handleUserVoting', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('should do nothing when voting form is missing', () => {
    document.body.innerHTML = '<div></div>';

    handleUserVoting();

    expect(saveVoting).not.toHaveBeenCalled();
  });

  it('should highlight stars based on existing rating', () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star" data-star="1">★</button>
        <button class="pmf-voting-star" data-star="2">★</button>
        <button class="pmf-voting-star" data-star="3">★</button>
        <button class="pmf-voting-star" data-star="4">★</button>
        <button class="pmf-voting-star" data-star="5">★</button>
      </form>
      <div id="rating"><span data-rating="3"></span></div>
    `;

    handleUserVoting();

    const stars = document.querySelectorAll('.pmf-voting-star');
    expect(stars[0].classList.contains('selected')).toBe(true);
    expect(stars[1].classList.contains('selected')).toBe(true);
    expect(stars[2].classList.contains('selected')).toBe(true);
    expect(stars[3].classList.contains('selected')).toBe(false);
    expect(stars[4].classList.contains('selected')).toBe(false);
  });

  it('should not highlight any stars when rating is 0', () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star" data-star="1">★</button>
        <button class="pmf-voting-star" data-star="2">★</button>
        <button class="pmf-voting-star" data-star="3">★</button>
      </form>
      <div id="rating"><span data-rating="0"></span></div>
    `;

    handleUserVoting();

    const stars = document.querySelectorAll('.pmf-voting-star');
    stars.forEach((star) => {
      expect(star.classList.contains('selected')).toBe(false);
    });
  });

  it('should not throw when rating span is missing', () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star" data-star="1">★</button>
      </form>
    `;

    // Should not throw
    handleUserVoting();
  });

  it('should save voting and show success notification on form submit', async () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star star" data-star="1" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="2" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="3" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="4" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="5" type="submit">★</button>
        <input type="hidden" id="voting-id" value="42" />
        <input type="hidden" id="voting-language" value="en" />
        <input type="hidden" id="csrf-token-voting" value="test-csrf-token" />
      </form>
    `;

    vi.mocked(saveVoting).mockResolvedValue({ success: 'Thanks for your vote!' });

    handleUserVoting();

    // Focus the 4th star button so it becomes activeElement
    const star4 = document.querySelectorAll('.pmf-voting-star')[3] as HTMLButtonElement;
    star4.focus();

    const form = document.querySelector('.pmf-voting-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(saveVoting).toHaveBeenCalledWith('42', 'en', 4, 'test-csrf-token');
    });

    expect(pushNotification).toHaveBeenCalledWith('Thanks for your vote!');
  });

  it('should show error notification on error response', async () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star star" data-star="1" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="2" type="submit">★</button>
        <input type="hidden" id="voting-id" value="42" />
        <input type="hidden" id="voting-language" value="en" />
        <input type="hidden" id="csrf-token-voting" value="test-csrf-token" />
      </form>
    `;

    vi.mocked(saveVoting).mockResolvedValue({ error: 'You already voted' });

    handleUserVoting();

    const star1 = document.querySelectorAll('.pmf-voting-star')[0] as HTMLButtonElement;
    star1.focus();

    const form = document.querySelector('.pmf-voting-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(saveVoting).toHaveBeenCalledWith('42', 'en', 1, 'test-csrf-token');
    });

    expect(pushErrorNotification).toHaveBeenCalledWith('You already voted');
  });

  it('should update star selection on submit', async () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star star" data-star="1" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="2" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="3" type="submit">★</button>
        <input type="hidden" id="voting-id" value="42" />
        <input type="hidden" id="voting-language" value="en" />
        <input type="hidden" id="csrf-token-voting" value="test-csrf-token" />
      </form>
    `;

    vi.mocked(saveVoting).mockResolvedValue({ success: 'Voted' });

    handleUserVoting();

    const star2 = document.querySelectorAll('.pmf-voting-star')[1] as HTMLButtonElement;
    star2.focus();

    const form = document.querySelector('.pmf-voting-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(saveVoting).toHaveBeenCalled();
    });

    const stars = document.querySelectorAll('.pmf-voting-star');
    expect(stars[0].classList.contains('selected')).toBe(true);
    expect(stars[1].classList.contains('selected')).toBe(true);
    expect(stars[2].classList.contains('selected')).toBe(false);
  });

  it('should set aria-pressed on selected button and remove from previous', async () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star star" data-star="1" type="submit" aria-pressed="true">★</button>
        <button class="pmf-voting-star star" data-star="2" type="submit">★</button>
        <button class="pmf-voting-star star" data-star="3" type="submit">★</button>
        <input type="hidden" id="voting-id" value="42" />
        <input type="hidden" id="voting-language" value="en" />
        <input type="hidden" id="csrf-token-voting" value="test-csrf-token" />
      </form>
    `;

    vi.mocked(saveVoting).mockResolvedValue({ success: 'Voted' });

    handleUserVoting();

    const star3 = document.querySelectorAll('.pmf-voting-star')[2] as HTMLButtonElement;
    star3.focus();

    const form = document.querySelector('.pmf-voting-form') as HTMLFormElement;
    form.dispatchEvent(new Event('submit'));

    await vi.waitFor(() => {
      expect(saveVoting).toHaveBeenCalled();
    });

    const stars = document.querySelectorAll('.pmf-voting-star');
    expect(stars[0].getAttribute('aria-pressed')).toBeNull();
    expect(stars[2].getAttribute('aria-pressed')).toBe('true');
  });
});

describe('highlightStars (mouseover)', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('should highlight stars up to hovered star on mouseover', () => {
    document.body.innerHTML = `
      <form class="pmf-voting-form">
        <button class="pmf-voting-star" data-star="1">★</button>
        <button class="pmf-voting-star" data-star="2">★</button>
        <button class="pmf-voting-star" data-star="3">★</button>
      </form>
    `;

    // Dispatch from the star element itself so event.target.closest() works
    const star2 = document.querySelectorAll('.pmf-voting-star')[1] as HTMLElement;
    star2.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));

    const stars = document.querySelectorAll('.pmf-voting-star');
    expect(stars[0].classList.contains('selected')).toBe(true);
    expect(stars[1].classList.contains('selected')).toBe(true);
    expect(stars[2].classList.contains('selected')).toBe(false);
  });
});

describe('resetSelected (mouseleave)', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('should reset stars to clicked state on mouseleave', () => {
    document.body.innerHTML = `
      <div class="rating">
        <form class="pmf-voting-form">
          <button class="pmf-voting-star star" data-star="1" aria-pressed="true">★</button>
          <button class="pmf-voting-star star selected" data-star="2">★</button>
          <button class="pmf-voting-star star selected" data-star="3">★</button>
        </form>
      </div>
    `;

    // Star 1 has aria-pressed, so only star 0 should remain selected after reset
    const star3 = document.querySelectorAll('.pmf-voting-star')[2] as HTMLElement;
    star3.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true }));

    const stars = document.querySelectorAll('.pmf-voting-star');
    expect(stars[0].classList.contains('selected')).toBe(true);
    expect(stars[1].classList.contains('selected')).toBe(false);
    expect(stars[2].classList.contains('selected')).toBe(false);
  });

  it('should clear all stars when no star has aria-pressed', () => {
    document.body.innerHTML = `
      <div class="rating">
        <form class="pmf-voting-form">
          <button class="pmf-voting-star star selected" data-star="1">★</button>
          <button class="pmf-voting-star star selected" data-star="2">★</button>
        </form>
      </div>
    `;

    const star1 = document.querySelectorAll('.pmf-voting-star')[0] as HTMLElement;
    star1.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true }));

    const stars = document.querySelectorAll('.pmf-voting-star');
    stars.forEach((star) => {
      expect(star.classList.contains('selected')).toBe(false);
    });
  });
});
