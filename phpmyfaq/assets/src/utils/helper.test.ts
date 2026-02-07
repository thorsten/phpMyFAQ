import { describe, expect, test } from 'vitest';
import { addElement, capitalize, escape, sort, serialize } from './helper';

describe('addElement', () => {
  test('should add element with tag, properties, and children', () => {
    const properties = {
      id: 'myElement',
      className: 'myClass',
    };

    const childElement: HTMLSpanElement = document.createElement('span');
    childElement.textContent = 'Child Element';

    const result: HTMLElement = addElement('div', properties, [childElement]);

    // Assert the tag, properties, and children are applied correctly
    expect(result.tagName.toLowerCase()).toBe('div');
    expect(result.id).toBe('myElement');
    expect(result.classList.contains('myClass')).toBe(true);

    expect(result.childNodes.length).toBe(1);
    expect(result.childNodes[0]).toEqual(childElement);
  });

  test('should add element without properties and children', () => {
    const result: HTMLSpanElement = addElement('span');

    // Assert the tag, properties, and children are applied correctly
    expect(result.tagName.toLowerCase()).toBe('span');
    expect(result.getAttribute('id')).toBe(null);
    expect(result.getAttribute('class')).toBe(null);

    expect(result.childNodes.length).toBe(0);
  });

  test('should handle data attributes with kebab-case', () => {
    const properties = {
      'data-pmf-id': '123',
      'data-pmf-category-id-sticky': 'true',
      'data-pmf-faq-id': '456',
    };

    const result: HTMLElement = addElement('button', properties);

    expect(result.getAttribute('data-pmf-id')).toBe('123');
    expect(result.getAttribute('data-pmf-category-id-sticky')).toBe('true');
    expect(result.getAttribute('data-pmf-faq-id')).toBe('456');
  });

  test('should handle aria attributes', () => {
    const properties = {
      'aria-hidden': 'true',
      'aria-label': 'Close',
    };

    const result: HTMLElement = addElement('button', properties);

    expect(result.getAttribute('aria-hidden')).toBe('true');
    expect(result.getAttribute('aria-label')).toBe('Close');
  });

  test('should handle classList property', () => {
    const properties = {
      classList: 'btn btn-primary btn-lg',
    };

    const result: HTMLElement = addElement('button', properties);

    expect(result.classList.contains('btn')).toBe(true);
    expect(result.classList.contains('btn-primary')).toBe(true);
    expect(result.classList.contains('btn-lg')).toBe(true);
  });

  test('should handle boolean attributes', () => {
    const properties = {
      checked: true,
      disabled: false,
    };

    const result: HTMLElement = addElement('input', properties);

    expect(result.hasAttribute('checked')).toBe(true);
    expect(result.hasAttribute('disabled')).toBe(false);
  });
});

describe('escape', () => {
  test('should escape special characters in text', () => {
    const text = '<script>alert("Hello, World!");</script>';
    const escapedText: string = escape(text);

    expect(escapedText).toBe('&lt;script&gt;alert(&quot;Hello, World!&quot;);&lt;/script&gt;');
  });

  test('should escape multiple occurrences of special characters', () => {
    const text = 'This & that < these > those " and \'';
    const escapedText: string = escape(text);

    expect(escapedText).toBe('This &amp; that &lt; these &gt; those &quot; and &#039;');
  });

  test('should return the same text if there are no special characters', () => {
    const text = 'This is a normal text.';
    const escapedText: string = escape(text);

    expect(escapedText).toBe(text);
  });
});

describe('sort', () => {
  test('should sort the array in ascending order', () => {
    const arr: number[] = [4, 2, 7, 1, 5];
    const sortedArr: number[] = sort(arr);

    expect(sortedArr).toEqual([1, 2, 4, 5, 7]);
  });

  test('should sort the array with negative numbers', () => {
    const arr: number[] = [-10, 5, -3, 0, -7];
    const sortedArr: number[] = sort(arr);

    expect(sortedArr).toEqual([-10, -7, -3, 0, 5]);
  });

  test('should sort the array with duplicate values', () => {
    const arr: number[] = [2, 7, 1, 4, 2, 6];
    const sortedArr: number[] = sort(arr);

    expect(sortedArr).toEqual([1, 2, 2, 4, 6, 7]);
  });
});

describe('serialize', () => {
  test('should serialize key-value pairs into an object', () => {
    const data = new FormData();
    data.append('name', 'John');
    data.append('age', '30');
    data.append('interests', JSON.stringify(['music', 'sports']));

    const result = serialize(data);

    expect(result).toEqual({
      name: 'John',
      age: '30',
      interests: '["music","sports"]',
    });
  });

  test('should handle duplicate keys by creating an array', () => {
    const data = new FormData();
    data.append('name', 'John');
    data.append('name', 'Doe');
    data.append('age', '30');

    const result = serialize(data);

    expect(result).toEqual({
      name: ['John', 'Doe'],
      age: '30',
    });
  });

  test('should handle single key-value pair', () => {
    const data = new FormData();
    data.append('name', 'John');

    const result = serialize(data);

    expect(result).toEqual({
      name: 'John',
    });
  });

  test('should return an empty object for empty data', () => {
    const data = new FormData();

    const result = serialize(data);

    expect(result).toEqual({});
  });
});

describe('capitalize', () => {
  test('should capitalize the first letter of a string', () => {
    const input: string = 'hello';
    const expectedOutput: string = 'Hello';

    const result: string = capitalize(input);
    expect(result).toEqual(expectedOutput);
  });

  test('should not change the capitalization of already capitalized words', () => {
    const input: string = 'World';
    const expectedOutput: string = 'World';

    const result: string = capitalize(input);
    expect(result).toEqual(expectedOutput);
  });

  test('should return an empty string if the input is an empty string', () => {
    const input: string = '';
    const expectedOutput: string = '';

    const result: string = capitalize(input);
    expect(result).toEqual(expectedOutput);
  });
});
