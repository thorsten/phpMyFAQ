/**
 * Version comparison utility function
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-25
 */

type VersionOperator = '<' | 'lt' | '<=' | 'le' | '>' | 'gt' | '>=' | 'ge' | '==' | '=' | 'eq' | '!=' | '<>' | 'ne';

/**
 * Special version identifiers and their order
 * The lower the value, the lower the priority
 */
const VERSION_SPECIAL: Record<string, number> = {
  dev: 0,
  alpha: 1,
  a: 1,
  beta: 2,
  b: 2,
  rc: 3,
  '#': 4,
  pl: 5,
  p: 5,
};

interface ParsedVersion {
  parts: number[];
  special: string | null;
  specialNumber: number;
}

/**
 * Parses a version string into its components
 * @param version - Version string to parse
 * @returns Parsed version object
 */
const parseVersion = (version: string): ParsedVersion => {
  // Remove leading/trailing whitespace
  version = version.trim().toLowerCase();

  // Initialize result
  const result: ParsedVersion = {
    parts: [],
    special: null,
    specialNumber: 0,
  };

  // Extract special version identifier (dev, alpha, beta, rc, pl, etc.)
  const specialMatch = version.match(/(dev|alpha|a|beta|b|rc|pl|p)(\d*)/);
  if (specialMatch) {
    result.special = specialMatch[1];
    result.specialNumber = specialMatch[2] ? parseInt(specialMatch[2], 10) : 0;
    // Remove the special part from version
    version = version.substring(0, specialMatch.index);
  }

  // Remove any non-numeric characters except dots (like #, -, _)
  version = version.replace(/[^0-9.]/g, '.');

  // Split by dots and parse numbers
  const parts = version.split('.');
  for (const part of parts) {
    if (part === '') continue;
    const num = parseInt(part, 10);
    if (!isNaN(num)) {
      result.parts.push(num);
    }
  }

  return result;
};

/**
 * Compares two parsed version objects
 * @param v1 - First parsed version
 * @param v2 - Second parsed version
 * @returns -1 if v1 < v2, 0 if equal, 1 if v1 > v2
 */
const compareVersions = (v1: ParsedVersion, v2: ParsedVersion): number => {
  // Compare numeric parts
  const maxLength = Math.max(v1.parts.length, v2.parts.length);
  for (let i = 0; i < maxLength; i++) {
    const part1 = v1.parts[i] || 0;
    const part2 = v2.parts[i] || 0;

    if (part1 < part2) return -1;
    if (part1 > part2) return 1;
  }

  // If numeric parts are equal, compare special versions
  const special1 = v1.special ? VERSION_SPECIAL[v1.special] : 4; // Default to '#' level if no special
  const special2 = v2.special ? VERSION_SPECIAL[v2.special] : 4;

  if (special1 < special2) return -1;
  if (special1 > special2) return 1;

  // If special versions are the same, compare special numbers
  if (v1.specialNumber < v2.specialNumber) return -1;
  if (v1.specialNumber > v2.specialNumber) return 1;

  return 0;
};

/**
 * Compares two version strings, mimicking PHP's version_compare() function
 *
 * @param version1 - First version string
 * @param version2 - Second version string
 * @param operator - Optional comparison operator
 * @returns boolean result
 *
 * @example
 * ```typescript
 * versionCompare('1.0.0', '1.0.1', '<'); // true
 * versionCompare('2.0.0', '1.0.0', '>='); // true
 * ```
 */
export function versionCompare(version1: string, version2: string, operator: VersionOperator): boolean {
  const v1 = parseVersion(version1);
  const v2 = parseVersion(version2);
  const result = compareVersions(v1, v2);

  // Apply operator
  switch (operator) {
    case '<':
    case 'lt':
      return result < 0;
    case '<=':
    case 'le':
      return result <= 0;
    case '>':
    case 'gt':
      return result > 0;
    case '>=':
    case 'ge':
      return result >= 0;
    case '==':
    case '=':
    case 'eq':
      return result === 0;
    case '!=':
    case '<>':
    case 'ne':
      return result !== 0;
    default:
      throw new Error(`Invalid operator: ${operator}`);
  }
}
