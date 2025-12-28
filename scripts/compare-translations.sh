#!/bin/bash
#
# This script compares a language translation file with the default English translation
# and reports missing and obsolete translation keys.
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2025 phpMyFAQ Team
# @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @since     2025-12-25

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to display usage
usage() {
    echo "Usage: $0 <language_code>"
    echo ""
    echo "Examples:"
    echo "  $0 pt_br    # Compare Portuguese (Brazil) with English"
    echo "  $0 de       # Compare German with English"
    echo "  $0 fr       # Compare French with English"
    echo ""
    echo "The script will compare the translation file language_<code>.php"
    echo "with the default English translation (language_en.php)"
    exit 1
}

# Check if language code is provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Language code is required${NC}"
    echo ""
    usage
fi

LANG_CODE="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TRANSLATIONS_DIR="${SCRIPT_DIR}/../phpmyfaq/translations"
ENGLISH_FILE="${TRANSLATIONS_DIR}/language_en.php"
LANGUAGE_FILE="${TRANSLATIONS_DIR}/language_${LANG_CODE}.php"

# Check if English file exists
if [ ! -f "$ENGLISH_FILE" ]; then
    echo -e "${RED}Error: English translation file not found at: ${ENGLISH_FILE}${NC}"
    exit 1
fi

# Check if target language file exists
if [ ! -f "$LANGUAGE_FILE" ]; then
    echo -e "${RED}Error: Translation file for '${LANG_CODE}' not found at: ${LANGUAGE_FILE}${NC}"
    exit 1
fi

# Create temporary files for storing keys
TEMP_DIR=$(mktemp -d)
EN_KEYS="${TEMP_DIR}/en_keys.txt"
LANG_KEYS="${TEMP_DIR}/lang_keys.txt"
MISSING_KEYS="${TEMP_DIR}/missing_keys.txt"
OBSOLETE_KEYS="${TEMP_DIR}/obsolete_keys.txt"

# Extract keys from English file (handle both single and double quotes, trim whitespace)
grep -E "\\\$PMF_LANG\[" "$ENGLISH_FILE" | sed -E "s/.*\\\$PMF_LANG\[['\"]([^'\"]+)['\"]\].*/\1/" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | sort -u > "$EN_KEYS"

# Extract keys from target language file (handle both single and double quotes, trim whitespace)
grep -E "\\\$PMF_LANG\[" "$LANGUAGE_FILE" | sed -E "s/.*\\\$PMF_LANG\[['\"]([^'\"]+)['\"]\].*/\1/" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | sort -u > "$LANG_KEYS"

# Find missing keys (in English but not in target language)
comm -23 "$EN_KEYS" "$LANG_KEYS" > "$MISSING_KEYS"

# Find obsolete keys (in target language but not in English)
comm -13 "$EN_KEYS" "$LANG_KEYS" > "$OBSOLETE_KEYS"

# Count keys
EN_COUNT=$(wc -l < "$EN_KEYS" | tr -d ' ')
LANG_COUNT=$(wc -l < "$LANG_KEYS" | tr -d ' ')
MISSING_COUNT=$(wc -l < "$MISSING_KEYS" | tr -d ' ')
OBSOLETE_COUNT=$(wc -l < "$OBSOLETE_KEYS" | tr -d ' ')

# Display header
echo ""
echo -e "${BLUE}=================================${NC}"
echo -e "${BLUE}Translation Comparison Report${NC}"
echo -e "${BLUE}=================================${NC}"
echo ""
echo -e "Language: ${YELLOW}${LANG_CODE}${NC}"
echo -e "English file: ${ENGLISH_FILE}"
echo -e "Language file: ${LANGUAGE_FILE}"
echo ""

# Display statistics
echo -e "${BLUE}Statistics:${NC}"
echo -e "  English keys:     ${EN_COUNT}"
echo -e "  Language keys:    ${LANG_COUNT}"
echo -e "  ${GREEN}Missing keys:     ${MISSING_COUNT}${NC}"
echo -e "  ${YELLOW}Obsolete keys:    ${OBSOLETE_COUNT}${NC}"
echo ""

# Calculate completion percentage
if [ "$EN_COUNT" -gt 0 ]; then
    COMPLETION=$((($EN_COUNT - $MISSING_COUNT) * 100 / $EN_COUNT))
    echo -e "Translation completion: ${GREEN}${COMPLETION}%${NC}"
    echo ""
fi

# Display missing keys
if [ "$MISSING_COUNT" -gt 0 ]; then
    echo -e "${GREEN}Missing Keys (in English but not in ${LANG_CODE}):${NC}"
    echo -e "${GREEN}=================================================${NC}"
    cat "$MISSING_KEYS" | while read key; do
        echo -e "  ${RED}✗${NC} $key"
    done
    echo ""
else
    echo -e "${GREEN}✓ No missing keys! All English keys are translated.${NC}"
    echo ""
fi

# Display obsolete keys
if [ "$OBSOLETE_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}Obsolete Keys (in ${LANG_CODE} but not in English):${NC}"
    echo -e "${YELLOW}==================================================${NC}"
    cat "$OBSOLETE_KEYS" | while read key; do
        echo -e "  ${YELLOW}⚠${NC} $key"
    done
    echo ""
    echo -e "${YELLOW}Note: These keys may be deprecated and can be removed.${NC}"
    echo ""
else
    echo -e "${GREEN}✓ No obsolete keys! The translation is clean.${NC}"
    echo ""
fi

# Display summary
echo -e "${BLUE}=================================${NC}"
if [ "$MISSING_COUNT" -eq 0 ] && [ "$OBSOLETE_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✓ Translation is perfectly synchronized with English!${NC}"
elif [ "$MISSING_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}⚠ Translation is complete but has ${OBSOLETE_COUNT} obsolete keys.${NC}"
elif [ "$OBSOLETE_COUNT" -eq 0 ]; then
    echo -e "${RED}✗ Translation is missing ${MISSING_COUNT} keys.${NC}"
else
    echo -e "${RED}✗ Translation needs attention: ${MISSING_COUNT} missing, ${OBSOLETE_COUNT} obsolete.${NC}"
fi
echo -e "${BLUE}=================================${NC}"
echo ""

# Cleanup temporary files
rm -rf "$TEMP_DIR"

# Exit with appropriate code
if [ "$MISSING_COUNT" -gt 0 ] || [ "$OBSOLETE_COUNT" -gt 0 ]; then
    exit 1
else
    exit 0
fi
