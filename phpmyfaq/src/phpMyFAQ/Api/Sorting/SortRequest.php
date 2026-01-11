<?php

/**
 * Sort Request Parser
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

namespace phpMyFAQ\Api\Sorting;

use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SortRequest
 *
 * Parses and validates sorting query parameters from HTTP requests.
 * Provides safe SQL ORDER BY clause generation.
 */
class SortRequest
{
    private ?string $field;

    private string $order;

    private array $allowedFields;

    /**
     * Constructor
     *
     * @param string|null $field Sort field name
     * @param string $order Sort order (asc or desc)
     * @param array $allowedFields Allowed field names for sorting
     */
    private function __construct(?string $field, string $order, array $allowedFields)
    {
        $this->field = $field;
        $this->order = $order;
        $this->allowedFields = $allowedFields;
    }

    /**
     * Creates a SortRequest from a Symfony Request object
     *
     * @param Request $request The HTTP request
     * @param array $allowedFields Whitelist of allowed sort fields
     * @param string|null $defaultField Default sort field if none specified
     * @param string $defaultOrder Default sort order (asc or desc)
     * @return self
     */
    public static function fromRequest(
        Request $request,
        array $allowedFields,
        ?string $defaultField = null,
        string $defaultOrder = 'asc',
    ): self {
        // Parse sort field from a query
        $sortField = Filter::filterVar($request->query->get('sort'), FILTER_SANITIZE_SPECIAL_CHARS);

        // Validate sort field against whitelist
        if ($sortField && in_array($sortField, $allowedFields, strict: true)) {
            $field = $sortField;
        } elseif ($defaultField && in_array($defaultField, $allowedFields, strict: true)) {
            $field = $defaultField;
        } else {
            $field = null;
        }

        // Parse sort order
        $orderParam = Filter::filterVar($request->query->get('order'), FILTER_SANITIZE_SPECIAL_CHARS);
        $order = self::validateOrder($orderParam, $defaultOrder);

        return new self($field, $order, $allowedFields);
    }

    /**
     * Validates sort order value
     *
     * @param string|null $order Order value to validate
     * @param string $default Default order if invalid
     * @return string Validated order (asc or desc)
     */
    private static function validateOrder(?string $order, string $default): string
    {
        if ($order === null) {
            return $default;
        }

        $orderLower = strtolower($order);

        return match ($orderLower) {
            'asc', 'ascending' => 'asc',
            'desc', 'descending' => 'desc',
            default => $default,
        };
    }

    /**
     * Gets the sort field name
     *
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * Gets the sort order
     *
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * Gets the uppercase sort order for SQL
     *
     * @return string
     */
    public function getOrderSql(): string
    {
        return strtoupper($this->order);
    }

    /**
     * Checks if sorting is active
     *
     * @return bool
     */
    public function hasSort(): bool
    {
        return $this->field !== null;
    }

    /**
     * Generates a safe SQL ORDER BY clause
     *
     * Returns empty string if no sort field is specified.
     * Field name is validated against whitelist to prevent SQL injection.
     *
     * @return string SQL ORDER BY clause (without "ORDER BY" keyword)
     */
    public function toSqlOrderBy(): string
    {
        if ($this->field === null) {
            return '';
        }

        // Field is already validated against whitelist in constructor
        // Additional escaping for field name (backticks for MySQL, quotes for others)
        $escapedField = $this->escapeIdentifier($this->field);

        return sprintf('%s %s', $escapedField, $this->getOrderSql());
    }

    /**
     * Escapes a database identifier (table or column name)
     *
     * Uses backticks which work for MySQL/MariaDB.
     * For other databases, this may need to be adjusted.
     *
     * @param string $identifier The identifier to escape
     * @return string Escaped identifier
     */
    private function escapeIdentifier(string $identifier): string
    {
        // Remove any existing backticks
        $identifier = str_replace('`', '', $identifier);

        // Wrap in backticks
        return '`' . $identifier . '`';
    }

    /**
     * Converts sort request to array format for API response metadata
     *
     * @return array|null
     */
    public function toArray(): ?array
    {
        if ($this->field === null) {
            return null;
        }

        return [
            'field' => $this->field,
            'order' => $this->order,
        ];
    }
}
