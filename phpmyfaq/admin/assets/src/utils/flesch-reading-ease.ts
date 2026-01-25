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

/**
 * Supported languages for Flesch Reading Ease calculation
 * Each language has its own adapted formula
 */
export type SupportedLanguage =
  | 'de' // German
  | 'en' // English
  | 'es' // Spanish
  | 'fr' // French
  | 'it' // Italian
  | 'nl' // Dutch
  | 'pt' // Portuguese
  | 'pl' // Polish
  | 'ru' // Russian
  | 'cs' // Czech
  | 'tr' // Turkish
  | 'sv' // Swedish
  | 'da' // Danish
  | 'no' // Norwegian
  | 'fi'; // Finnish

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
 * Counts syllables for Romance languages (Spanish, French, Italian, Portuguese)
 * These languages have similar vowel patterns
 */
export const countSyllablesRomance = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // Romance language vowels including accented characters
  const syllables = word.match(/[aeiouáéíóúàèìòùâêîôûäëïöüãõ]{1,2}/g);
  return syllables ? Math.max(syllables.length, 1) : 1;
};

/**
 * Counts syllables for Dutch
 */
export const countSyllablesDutch = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // Dutch vowels including common digraphs
  const syllables = word.match(/[aeiouéèëïöü]{1,2}/g);
  return syllables ? Math.max(syllables.length, 1) : 1;
};

/**
 * Counts syllables for Slavic languages (Polish, Czech, Russian)
 */
export const countSyllablesSlavic = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // Slavic vowels: Polish (a,e,i,o,u,y,ą,ę,ó), Russian (а,е,ё,и,о,у,ы,э,ю,я), Czech (a,e,i,o,u,y,á,é,í,ó,ú,ů,ý)
  const syllables = word.match(/[aeiouyąęóáéíúůýаеёиоуыэюя]{1,2}/gi);
  return syllables ? Math.max(syllables.length, 1) : 1;
};

/**
 * Counts syllables for Nordic languages (Swedish, Danish, Norwegian, Finnish)
 */
export const countSyllablesNordic = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // Nordic vowels including special characters
  const syllables = word.match(/[aeiouäöåæø]{1,2}/g);
  return syllables ? Math.max(syllables.length, 1) : 1;
};

/**
 * Counts syllables for Turkish
 */
export const countSyllablesTurkish = (word: string): number => {
  word = word.toLowerCase().trim();
  if (word.length <= 3) {
    return 1;
  }

  // Turkish vowels including special characters
  const syllables = word.match(/[aeiouöüıİ]{1,2}/gi);
  return syllables ? Math.max(syllables.length, 1) : 1;
};

/**
 * Gets the appropriate syllable counter for a language
 */
export const getSyllableCounter = (language: SupportedLanguage): ((word: string) => number) => {
  switch (language) {
    case 'de':
      return countSyllablesGerman;
    case 'es':
    case 'fr':
    case 'it':
    case 'pt':
      return countSyllablesRomance;
    case 'nl':
      return countSyllablesDutch;
    case 'pl':
    case 'cs':
    case 'ru':
      return countSyllablesSlavic;
    case 'sv':
    case 'da':
    case 'no':
    case 'fi':
      return countSyllablesNordic;
    case 'tr':
      return countSyllablesTurkish;
    case 'en':
    default:
      return countSyllablesEnglish;
  }
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
 * Extracts words from text, supporting multiple character sets
 */
export const getWords = (text: string): string[] => {
  return text
    .replace(
      /[^a-zA-ZäöüÄÖÜßáéíóúàèìòùâêîôûãõçñąęółńśźżćčďěňřšťůýžæøåаеёиоуыэюяабвгдежзийклмнопрстуфхцчшщъьıİğş\s]/gi,
      ' '
    )
    .split(/\s+/)
    .filter((word) => word.length > 0);
};

/**
 * Language-specific Flesch formulas
 *
 * Sources:
 * - English (Flesch): 206.835 - (1.015 × ASL) - (84.6 × ASW)
 * - German (Amstad): 180 - ASL - (58.5 × ASW)
 * - Spanish (Fernández-Huerta): 206.84 - (1.02 × ASL) - (60 × ASW)
 * - French: 207 - (1.015 × ASL) - (73.6 × ASW)
 * - Italian (Franchina-Vacca): 217 - (1.3 × ASL) - (60 × ASW)
 * - Dutch (Douma): 206.84 - (0.93 × ASL) - (77 × ASW)
 * - Portuguese: 248.835 - (1.015 × ASL) - (84.6 × ASW)
 * - Polish: 206.835 - (1.015 × ASL) - (84.6 × ASW) (adapted)
 * - Russian: 206.835 - (1.3 × ASL) - (60.1 × ASW) (adapted)
 * - Czech: 206.835 - (1.015 × ASL) - (84.6 × ASW) (adapted)
 * - Turkish: 198.825 - (1.015 × ASL) - (84.6 × ASW) (adapted)
 * - Swedish (LIX-based approximation): 200 - (ASL) - (68 × ASW)
 * - Danish: 206.835 - (1.015 × ASL) - (84.6 × ASW) (adapted)
 * - Norwegian: 206.835 - (1.015 × ASL) - (84.6 × ASW) (adapted)
 * - Finnish: 206.835 - (1.015 × ASL) - (84.6 × ASW) (adapted)
 */
interface FleschFormula {
  base: number;
  aslCoefficient: number;
  aswCoefficient: number;
}

const fleschFormulas: Record<SupportedLanguage, FleschFormula> = {
  en: { base: 206.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  de: { base: 180, aslCoefficient: 1, aswCoefficient: 58.5 },
  es: { base: 206.84, aslCoefficient: 1.02, aswCoefficient: 60 },
  fr: { base: 207, aslCoefficient: 1.015, aswCoefficient: 73.6 },
  it: { base: 217, aslCoefficient: 1.3, aswCoefficient: 60 },
  nl: { base: 206.84, aslCoefficient: 0.93, aswCoefficient: 77 },
  pt: { base: 248.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  pl: { base: 206.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  ru: { base: 206.835, aslCoefficient: 1.3, aswCoefficient: 60.1 },
  cs: { base: 206.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  tr: { base: 198.825, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  sv: { base: 200, aslCoefficient: 1, aswCoefficient: 68 },
  da: { base: 206.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  no: { base: 206.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
  fi: { base: 206.835, aslCoefficient: 1.015, aswCoefficient: 84.6 },
};

/**
 * Calculates Flesch Reading Ease score using language-specific formulas
 *
 * Formula: base - (aslCoefficient × ASL) - (aswCoefficient × ASW)
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
  const syllableCounter = getSyllableCounter(language);
  const totalSyllables = words.reduce((sum, word) => sum + syllableCounter(word), 0);

  const averageSentenceLength = wordCount / sentenceCount;
  const averageSyllablesPerWord = totalSyllables / wordCount;

  const formula = fleschFormulas[language] || fleschFormulas.en;
  const score =
    formula.base - formula.aslCoefficient * averageSentenceLength - formula.aswCoefficient * averageSyllablesPerWord;

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
