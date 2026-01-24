/**
 * Flesch Reading Ease Index Calculator
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

export interface FleschResult {
  score: number;
  label: string;
  colorClass: string;
}

export type SupportedLanguage = 'de' | 'en';

/**
 * Strips HTML tags from content to get plain text
 */
export const stripHtml = (html: string): string => {
  const temp = document.createElement('div');
  temp.innerHTML = html;
  return temp.textContent || temp.innerText || '';
};

/**
 * Counts syllables in a word (English approximation)
 * Uses vowel group counting with common adjustments
 */
export const countSyllablesEnglish = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // Remove trailing silent 'e'
  word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '');
  word = word.replace(/^y/, '');

  const syllables = word.match(/[aeiouy]{1,2}/g);
  return syllables ? syllables.length : 1;
};

/**
 * Counts syllables in a word (German approximation)
 * German syllables are based on vowel patterns including umlauts
 */
export const countSyllablesGerman = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // German vowels including umlauts
  const syllables = word.match(/[aeiouäöü]{1,2}/g);
  return syllables ? Math.max(syllables.length, 1) : 1;
};

/**
 * Counts sentences in text
 * Handles multiple punctuation marks
 */
export const countSentences = (text: string): number => {
  const sentences = text.split(/[.!?]+/).filter((s) => s.trim().length > 0);
  return Math.max(sentences.length, 1);
};

/**
 * Extracts words from text
 */
export const getWords = (text: string): string[] => {
  return text
    .replace(/[^a-zA-ZäöüÄÖÜß\s]/g, ' ')
    .split(/\s+/)
    .filter((word) => word.length > 0);
};

/**
 * Calculates Flesch Reading Ease score
 *
 * English formula: 206.835 - (1.015 × ASL) - (84.6 × ASW)
 * German formula: 180 - ASL - (58.5 × ASW)
 *
 * Where:
 * - ASL = Average Sentence Length (words per sentence)
 * - ASW = Average Syllables per Word
 */
export const calculateFleschScore = (text: string, language: SupportedLanguage = 'en'): number => {
  const plainText = stripHtml(text);
  const words = getWords(plainText);
  const wordCount = words.length;

  if (wordCount === 0) {
    return 0;
  }

  const sentenceCount = countSentences(plainText);
  const syllableCounter = language === 'de' ? countSyllablesGerman : countSyllablesEnglish;
  const totalSyllables = words.reduce((sum, word) => sum + syllableCounter(word), 0);

  const averageSentenceLength = wordCount / sentenceCount;
  const averageSyllablesPerWord = totalSyllables / wordCount;

  let score: number;
  if (language === 'de') {
    // German Flesch formula
    score = 180 - averageSentenceLength - 58.5 * averageSyllablesPerWord;
  } else {
    // English Flesch formula (default)
    score = 206.835 - 1.015 * averageSentenceLength - 84.6 * averageSyllablesPerWord;
  }

  // Clamp score to 0-100 range
  return Math.max(0, Math.min(100, Math.round(score * 10) / 10));
};

/**
 * Gets human-readable label and color class for a Flesch score
 */
export const getFleschLabel = (score: number): { label: string; colorClass: string } => {
  if (score >= 90) {
    return { label: 'Very Easy', colorClass: 'success' };
  }
  if (score >= 80) {
    return { label: 'Easy', colorClass: 'success' };
  }
  if (score >= 70) {
    return { label: 'Fairly Easy', colorClass: 'info' };
  }
  if (score >= 60) {
    return { label: 'Standard', colorClass: 'primary' };
  }
  if (score >= 50) {
    return { label: 'Fairly Difficult', colorClass: 'warning' };
  }
  if (score >= 30) {
    return { label: 'Difficult', colorClass: 'warning' };
  }
  return { label: 'Very Difficult', colorClass: 'danger' };
};

/**
 * Main function to calculate Flesch Reading Ease with a full result
 */
export const analyzeReadability = (text: string, language: SupportedLanguage = 'en'): FleschResult => {
  const score = calculateFleschScore(text, language);
  const { label, colorClass } = getFleschLabel(score);
  return { score, label, colorClass };
};
