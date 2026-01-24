/**
 * Unit tests for the Flesch Reading Ease Index Calculator
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-24
 */

import { describe, it, expect, beforeEach } from 'vitest';
import {
  stripHtml,
  countSyllablesEnglish,
  countSyllablesGerman,
  countSentences,
  getWords,
  calculateFleschScore,
  getFleschLabel,
  analyzeReadability,
} from './flesch-reading-ease';

describe('Flesch Reading Ease', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  describe('stripHtml', () => {
    it('should remove HTML tags', () => {
      expect(stripHtml('<p>Hello <strong>World</strong></p>')).toBe('Hello World');
    });

    it('should handle empty content', () => {
      expect(stripHtml('')).toBe('');
    });

    it('should handle nested HTML tags', () => {
      expect(stripHtml('<div><p>Test <span>content</span></p></div>')).toBe('Test content');
    });

    it('should handle HTML entities', () => {
      expect(stripHtml('<p>Hello &amp; World</p>')).toBe('Hello & World');
    });
  });

  describe('countSyllablesEnglish', () => {
    it('should count single syllable words', () => {
      expect(countSyllablesEnglish('the')).toBe(1);
      expect(countSyllablesEnglish('cat')).toBe(1);
      expect(countSyllablesEnglish('dog')).toBe(1);
    });

    it('should count two syllable words', () => {
      expect(countSyllablesEnglish('hello')).toBe(2);
      expect(countSyllablesEnglish('water')).toBe(2);
    });

    it('should count multi-syllable words', () => {
      // Note: syllable counting is an approximation
      expect(countSyllablesEnglish('beautiful')).toBeGreaterThanOrEqual(3);
      expect(countSyllablesEnglish('computer')).toBeGreaterThanOrEqual(2);
    });

    it('should return 1 for very short words', () => {
      expect(countSyllablesEnglish('a')).toBe(1);
      expect(countSyllablesEnglish('an')).toBe(1);
      expect(countSyllablesEnglish('the')).toBe(1);
    });

    it('should handle empty string', () => {
      expect(countSyllablesEnglish('')).toBe(1);
    });
  });

  describe('countSyllablesGerman', () => {
    it('should count German syllables with umlauts', () => {
      expect(countSyllablesGerman('Übung')).toBe(2);
      expect(countSyllablesGerman('Häuser')).toBe(2);
      expect(countSyllablesGerman('schön')).toBe(1);
    });

    it('should count simple German words', () => {
      expect(countSyllablesGerman('Hund')).toBe(1);
      expect(countSyllablesGerman('Katze')).toBe(2);
    });

    it('should return 1 for very short words', () => {
      expect(countSyllablesGerman('am')).toBe(1);
      expect(countSyllablesGerman('im')).toBe(1);
    });
  });

  describe('countSentences', () => {
    it('should count sentences ending with period', () => {
      expect(countSentences('Hello. World.')).toBe(2);
      expect(countSentences('One sentence.')).toBe(1);
    });

    it('should count sentences ending with different punctuation', () => {
      expect(countSentences('Hello! World?')).toBe(2);
      expect(countSentences('Really?! Yes.')).toBe(2);
    });

    it('should return 1 for text without punctuation', () => {
      expect(countSentences('No punctuation here')).toBe(1);
    });

    it('should handle multiple consecutive punctuation marks', () => {
      expect(countSentences('What?! Yes...')).toBe(2);
    });
  });

  describe('getWords', () => {
    it('should extract words from text', () => {
      const words = getWords('Hello World');
      expect(words).toEqual(['Hello', 'World']);
    });

    it('should remove punctuation', () => {
      const words = getWords('Hello, World!');
      expect(words).toEqual(['Hello', 'World']);
    });

    it('should handle German umlauts', () => {
      const words = getWords('Häuser und Übungen');
      expect(words).toEqual(['Häuser', 'und', 'Übungen']);
    });

    it('should return empty array for empty text', () => {
      expect(getWords('')).toEqual([]);
    });

    it('should handle multiple spaces', () => {
      const words = getWords('Hello    World');
      expect(words).toEqual(['Hello', 'World']);
    });
  });

  describe('calculateFleschScore', () => {
    it('should return 0 for empty text', () => {
      expect(calculateFleschScore('')).toBe(0);
    });

    it('should return 0 for text with only punctuation', () => {
      expect(calculateFleschScore('...')).toBe(0);
    });

    it('should calculate English score for simple text', () => {
      const text = 'The cat sat on the mat. It was a nice day.';
      const score = calculateFleschScore(text, 'en');
      expect(score).toBeGreaterThan(60);
      expect(score).toBeLessThanOrEqual(100);
    });

    it('should calculate German score', () => {
      const text = 'Die Katze sitzt auf der Matte. Es war ein schöner Tag.';
      const score = calculateFleschScore(text, 'de');
      expect(score).toBeGreaterThan(0);
      expect(score).toBeLessThanOrEqual(100);
    });

    it('should clamp scores to 0-100 range', () => {
      // Very simple text that might exceed 100
      const simpleText = 'Go. Run. Jump.';
      const score = calculateFleschScore(simpleText, 'en');
      expect(score).toBeLessThanOrEqual(100);
      expect(score).toBeGreaterThanOrEqual(0);
    });

    it('should return lower score for complex text', () => {
      const complexText =
        'The implementation of sophisticated algorithmic methodologies necessitates comprehensive understanding of computational paradigms.';
      const simpleText = 'The cat sat on the mat.';

      const complexScore = calculateFleschScore(complexText, 'en');
      const simpleScore = calculateFleschScore(simpleText, 'en');

      expect(complexScore).toBeLessThan(simpleScore);
    });

    it('should default to English formula', () => {
      const text = 'Hello world.';
      const defaultScore = calculateFleschScore(text);
      const englishScore = calculateFleschScore(text, 'en');
      expect(defaultScore).toBe(englishScore);
    });
  });

  describe('getFleschLabel', () => {
    it('should return Very Easy for scores 90-100', () => {
      expect(getFleschLabel(95).label).toBe('Very Easy');
      expect(getFleschLabel(90).label).toBe('Very Easy');
      expect(getFleschLabel(100).label).toBe('Very Easy');
    });

    it('should return Easy for scores 80-89', () => {
      expect(getFleschLabel(85).label).toBe('Easy');
      expect(getFleschLabel(80).label).toBe('Easy');
    });

    it('should return Fairly Easy for scores 70-79', () => {
      expect(getFleschLabel(75).label).toBe('Fairly Easy');
      expect(getFleschLabel(70).label).toBe('Fairly Easy');
    });

    it('should return Standard for scores 60-69', () => {
      expect(getFleschLabel(65).label).toBe('Standard');
      expect(getFleschLabel(60).label).toBe('Standard');
    });

    it('should return Fairly Difficult for scores 50-59', () => {
      expect(getFleschLabel(55).label).toBe('Fairly Difficult');
      expect(getFleschLabel(50).label).toBe('Fairly Difficult');
    });

    it('should return Difficult for scores 30-49', () => {
      expect(getFleschLabel(40).label).toBe('Difficult');
      expect(getFleschLabel(30).label).toBe('Difficult');
    });

    it('should return Very Difficult for scores 0-29', () => {
      expect(getFleschLabel(20).label).toBe('Very Difficult');
      expect(getFleschLabel(0).label).toBe('Very Difficult');
    });

    it('should return correct color classes', () => {
      expect(getFleschLabel(90).colorClass).toBe('success');
      expect(getFleschLabel(80).colorClass).toBe('success');
      expect(getFleschLabel(70).colorClass).toBe('info');
      expect(getFleschLabel(60).colorClass).toBe('primary');
      expect(getFleschLabel(50).colorClass).toBe('warning');
      expect(getFleschLabel(30).colorClass).toBe('warning');
      expect(getFleschLabel(20).colorClass).toBe('danger');
    });
  });

  describe('analyzeReadability', () => {
    it('should return complete result object', () => {
      const result = analyzeReadability('Simple text. Easy to read.', 'en');
      expect(result).toHaveProperty('score');
      expect(result).toHaveProperty('label');
      expect(result).toHaveProperty('colorClass');
    });

    it('should return numeric score', () => {
      const result = analyzeReadability('Hello world.', 'en');
      expect(typeof result.score).toBe('number');
    });

    it('should return string label', () => {
      const result = analyzeReadability('Hello world.', 'en');
      expect(typeof result.label).toBe('string');
    });

    it('should return valid color class', () => {
      const result = analyzeReadability('Hello world.', 'en');
      const validClasses = ['success', 'info', 'primary', 'warning', 'danger'];
      expect(validClasses).toContain(result.colorClass);
    });

    it('should handle German text', () => {
      const result = analyzeReadability('Hallo Welt.', 'de');
      expect(result.score).toBeGreaterThanOrEqual(0);
    });

    it('should handle empty text', () => {
      const result = analyzeReadability('', 'en');
      expect(result.score).toBe(0);
    });
  });
});
