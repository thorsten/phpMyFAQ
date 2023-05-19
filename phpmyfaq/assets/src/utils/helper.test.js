import { addElement, capitalize, escape, sort, serialize } from './helper';

describe('addElement', () => {
  test('should add element with tag, properties, and children', () => {
    const properties = {
      id: 'myElement',
      className: 'myClass',
    };

    const childElement = document.createElement('span');
    childElement.textContent = 'Child Element';

    const result = addElement('div', properties, [childElement]);

    // Assert the tag, properties, and children are applied correctly
    expect(result.tagName.toLowerCase()).toBe('div');
    expect(result.id).toBe('myElement');
    expect(result.classList.contains('myClass')).toBe(true);

    expect(result.childNodes.length).toBe(1);
    expect(result.childNodes[0]).toEqual(childElement);
  });

  test('should add element without properties and children', () => {
    const result = addElement('span');

    // Assert the tag, properties, and children are applied correctly
    expect(result.tagName.toLowerCase()).toBe('span');
    expect(result.getAttribute('id')).toBe(null);
    expect(result.getAttribute('class')).toBe(null);

    expect(result.childNodes.length).toBe(0);
  });
});

describe('escape', () => {
  test('should escape special characters in text', () => {
    const text = '<script>alert("Hello, World!");</script>';
    const escapedText = escape(text);

    expect(escapedText).toBe('&lt;script&gt;alert(&quot;Hello, World!&quot;);&lt;/script&gt;');
  });

  test('should escape multiple occurrences of special characters', () => {
    const text = 'This & that < these > those " and \'';
    const escapedText = escape(text);

    expect(escapedText).toBe('This &amp; that &lt; these &gt; those &quot; and &#039;');
  });

  test('should return the same text if there are no special characters', () => {
    const text = 'This is a normal text.';
    const escapedText = escape(text);

    expect(escapedText).toBe(text);
  });
});

describe('sort', () => {
  test('should sort the array in ascending order', () => {
    const arr = [4, 2, 7, 1, 5];
    const sortedArr = sort(arr);

    expect(sortedArr).toEqual([1, 2, 4, 5, 7]);
  });

  test('should sort the array with negative numbers', () => {
    const arr = [-10, 5, -3, 0, -7];
    const sortedArr = sort(arr);

    expect(sortedArr).toEqual([-10, -7, -3, 0, 5]);
  });

  test('should sort the array with duplicate values', () => {
    const arr = [2, 7, 1, 4, 2, 6];
    const sortedArr = sort(arr);

    expect(sortedArr).toEqual([1, 2, 2, 4, 6, 7]);
  });
});

describe('serialize', () => {
  test('should serialize key-value pairs into an object', () => {
    const data = new Map([
      ['name', 'John'],
      ['age', 30],
      ['interests', ['music', 'sports']],
    ]);

    const result = serialize(data);

    expect(result).toEqual({
      name: 'John',
      age: 30,
      interests: ['music', 'sports'],
    });
  });

  test('should handle duplicate keys by creating an array', () => {
    const data = new Map([
      ['name', 'John'],
      ['name', 'Doe'],
      ['age', 30],
    ]);

    const result = serialize(data);

    expect(result).toEqual({
      name: 'Doe',
      age: 30,
    });
  });

  test('should handle single key-value pair', () => {
    const data = new Map([['name', 'John']]);

    const result = serialize(data);

    expect(result).toEqual({
      name: 'John',
    });
  });

  test('should return an empty object for empty data', () => {
    const data = new Map();

    const result = serialize(data);

    expect(result).toEqual({});
  });
});

describe('capitalize', () => {
  test('should capitalize the first letter of a string', () => {
    const input = 'hello';
    const expectedOutput = 'Hello';

    const result = capitalize(input);
    expect(result).toEqual(expectedOutput);
  });

  test('should not change the capitalization of already capitalized words', () => {
    const input = 'World';
    const expectedOutput = 'World';

    const result = capitalize(input);
    expect(result).toEqual(expectedOutput);
  });

  test('should return an empty string if the input is an empty string', () => {
    const input = '';
    const expectedOutput = '';

    const result = capitalize(input);
    expect(result).toEqual(expectedOutput);
  });
});
