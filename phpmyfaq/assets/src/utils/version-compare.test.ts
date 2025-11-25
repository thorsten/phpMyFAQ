import { describe, expect, test } from 'vitest';
import { versionCompare } from './version-compare';

describe('versionCompare - operator comparisons', () => {
  test('should handle < operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', '<')).toBe(true);
    expect(versionCompare('1.0.1', '1.0.0', '<')).toBe(false);
    expect(versionCompare('1.0.0', '1.0.0', '<')).toBe(false);
  });

  test('should handle lt operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', 'lt')).toBe(true);
    expect(versionCompare('1.0.1', '1.0.0', 'lt')).toBe(false);
  });

  test('should handle <= operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', '<=')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', '<=')).toBe(true);
    expect(versionCompare('1.0.1', '1.0.0', '<=')).toBe(false);
  });

  test('should handle le operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', 'le')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', 'le')).toBe(true);
  });

  test('should handle > operator', () => {
    expect(versionCompare('1.0.1', '1.0.0', '>')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.1', '>')).toBe(false);
    expect(versionCompare('1.0.0', '1.0.0', '>')).toBe(false);
  });

  test('should handle gt operator', () => {
    expect(versionCompare('1.0.1', '1.0.0', 'gt')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.1', 'gt')).toBe(false);
  });

  test('should handle >= operator', () => {
    expect(versionCompare('1.0.1', '1.0.0', '>=')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', '>=')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.1', '>=')).toBe(false);
  });

  test('should handle ge operator', () => {
    expect(versionCompare('1.0.1', '1.0.0', 'ge')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', 'ge')).toBe(true);
  });

  test('should handle == operator', () => {
    expect(versionCompare('1.0.0', '1.0.0', '==')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.1', '==')).toBe(false);
  });

  test('should handle = operator', () => {
    expect(versionCompare('1.0.0', '1.0.0', '=')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.1', '=')).toBe(false);
  });

  test('should handle eq operator', () => {
    expect(versionCompare('1.0.0', '1.0.0', 'eq')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.1', 'eq')).toBe(false);
  });

  test('should handle != operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', '!=')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', '!=')).toBe(false);
  });

  test('should handle <> operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', '<>')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', '<>')).toBe(false);
  });

  test('should handle ne operator', () => {
    expect(versionCompare('1.0.0', '1.0.1', 'ne')).toBe(true);
    expect(versionCompare('1.0.0', '1.0.0', 'ne')).toBe(false);
  });
});

describe('versionCompare - complex scenarios', () => {
  test('should handle PHP-like version comparisons', () => {
    // Test cases similar to PHP documentation
    expect(versionCompare('1.2', '1.2.0', '=')).toBe(true);
    expect(versionCompare('1.2', '1.2', '=')).toBe(true);
    expect(versionCompare('1.2.3', '1.2.3.0', '=')).toBe(true);
    expect(versionCompare('5.4.0', '5.4.0-alpha', '>')).toBe(true);
    expect(versionCompare('5.4.0-alpha', '5.4.0', '<')).toBe(true);
  });
});
