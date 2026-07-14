import { describe, it, expect, beforeEach } from 'vitest';
import { handleCharacterCounters } from './faqs.editor.counters';

describe('faqs.editor.counters', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('should render the initial count and update on input', () => {
    document.body.innerHTML = `
      <input id="serpTitle" value="Hello" />
      <small data-pmf-counter-for="serpTitle" data-pmf-counter-max="60"></small>
    `;

    handleCharacterCounters();

    const counter = document.querySelector('[data-pmf-counter-for]') as HTMLElement;
    expect(counter.textContent).toBe('5 / 60');
    expect(counter.classList.contains('text-muted')).toBe(true);

    const input = document.getElementById('serpTitle') as HTMLInputElement;
    input.value = 'A longer SERP title for testing';
    input.dispatchEvent(new Event('input'));

    expect(counter.textContent).toBe('31 / 60');
  });

  it('should switch to warning above 90% and danger above the maximum', () => {
    document.body.innerHTML = `
      <input id="serpTitle" value="" />
      <small data-pmf-counter-for="serpTitle" data-pmf-counter-max="10"></small>
    `;

    handleCharacterCounters();

    const counter = document.querySelector('[data-pmf-counter-for]') as HTMLElement;
    const input = document.getElementById('serpTitle') as HTMLInputElement;

    input.value = '1234567890';
    input.dispatchEvent(new Event('input'));
    expect(counter.classList.contains('text-warning')).toBe(true);

    input.value = '12345678901';
    input.dispatchEvent(new Event('input'));
    expect(counter.classList.contains('text-danger')).toBe(true);
  });

  it('should ignore counters without a matching field or valid maximum', () => {
    document.body.innerHTML = `
      <small data-pmf-counter-for="missing" data-pmf-counter-max="60"></small>
      <input id="present" value="text" />
      <small data-pmf-counter-for="present" data-pmf-counter-max="not-a-number"></small>
    `;

    handleCharacterCounters();

    const counters = document.querySelectorAll<HTMLElement>('[data-pmf-counter-for]');
    expect(counters[0].textContent).toBe('');
    expect(counters[1].textContent).toBe('');
  });
});
