<?php

/**
 * Filter Request Parser
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Api\Filtering;

use DateTime;
use Exception;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FilterRequest
 *
 * Parses and validates filtering query parameters from HTTP requests.
 * Supports multiple filter value types (string, int, bool, date).
 */
class FilterRequest
{
    private array $filters = [] {
        get {
            return $this->filters;
        }
    }

    private array $allowedFilters;

    /**
     * Constructor
     *
     * @param array $filters Parsed and validated filters
     * @param array $allowedFilters Configuration of allowed filters
     */
    private function __construct(array $filters, array $allowedFilters)
    {
        $this->filters = $filters;
        $this->allowedFilters = $allowedFilters;
    }

    /**
     * Creates a FilterRequest from a Symfony Request object
     *
     * @param Request $request The HTTP request
     * @param array $allowedFilters Configuration of allowed filters with their types
     * @return self
     *
     * Example $allowedFilters format:
     * [
     *     'active' => 'bool',
     *     'language' => 'string',
     *     'category_id' => 'int',
     *     'created_from' => 'date',
     *     'author' => 'string',
     * ]
     */
    public static function fromRequest(Request $request, array $allowedFilters): self
    {
        $filters = [];
        $queryParams = $request->query->all();

        foreach ($allowedFilters as $filterName => $filterType) {
            $value = null;

            // Check filter array parameter first (e.g., ?filter[category_id]=5)
            if (isset($queryParams['filter'][$filterName])) {
                $value = self::parseFilterValue($queryParams['filter'][$filterName], $filterType);
            }

            // Check direct parameter - this takes precedence (e.g., ?active=true)
            if (isset($queryParams[$filterName])) {
                $directValue = self::parseFilterValue($queryParams[$filterName], $filterType);
                if ($directValue !== null) {
                    $value = $directValue;
                }
            }

            if ($value !== null) {
                $filters[$filterName] = $value;
            }
        }

        return new self($filters, $allowedFilters);
    }

    /**
     * Parses and validates a filter value based on its type
     *
     * @param mixed $value The raw value from query parameters
     * @param string $type The expected type (bool, int, string, date)
     * @return mixed|null The parsed value or null if invalid
     */
    private static function parseFilterValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool', 'boolean' => self::parseBoolValue($value),
            'int', 'integer' => Filter::filterVar($value, FILTER_VALIDATE_INT),
            'float', 'double' => Filter::filterVar($value, FILTER_VALIDATE_FLOAT),
            'email' => Filter::filterVar($value, FILTER_VALIDATE_EMAIL),
            'date' => self::parseDateValue($value),
            'datetime' => self::parseDateTimeValue($value),
            default => Filter::filterVar($value, FILTER_SANITIZE_SPECIAL_CHARS),
        };
    }

    /**
     * Parses a boolean value from various string representations
     *
     * @param mixed $value The value to parse
     * @return bool|null
     */
    private static function parseBoolValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $valueLower = strtolower(trim($value));
            return match ($valueLower) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off', '' => false,
                default => null,
            };
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return null;
    }

    /**
     * Parses a date value (YYYY-MM-DD format)
     *
     * @param mixed $value The value to parse
     * @return string|null Date in YYYY-MM-DD format or null if invalid
     */
    private static function parseDateValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = Filter::filterVar($value, FILTER_SANITIZE_SPECIAL_CHARS);

        // Validate date format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            // Verify it's a valid date
            $parts = explode('-', $value);
            if (checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Parses a datetime value (ISO 8601 format)
     *
     * @param mixed $value The value to parse
     * @return string|null Datetime in ISO 8601 format or null if invalid
     */
    private static function parseDateTimeValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = Filter::filterVar($value, FILTER_SANITIZE_SPECIAL_CHARS);

        // Try to parse as datetime
        try {
            $dateTime = new DateTime($value);
            return $dateTime->format('Y-m-d H:i:s');
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Checks if a specific filter is set
     *
     * @param string $filterName The filter name
     * @return bool
     */
    public function has(string $filterName): bool
    {
        return isset($this->filters[$filterName]);
    }

    /**
     * Gets a specific filter value
     *
     * @param string $filterName The filter name
     * @param mixed $default Default value if filter is not set
     * @return mixed
     */
    public function get(string $filterName, mixed $default = null): mixed
    {
        return $this->filters[$filterName] ?? $default;
    }

    /**
     * Gets all filters
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Checks if any filters are active
     *
     * @return bool
     */
    public function hasFilters(): bool
    {
        return count($this->filters) > 0;
    }

    /**
     * Converts filter request to array format for API response metadata
     *
     * @return array|null
     */
    public function toArray(): ?array
    {
        return $this->hasFilters() ? $this->filters : null;
    }
}
