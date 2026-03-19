<?php

/**
 * phpMyFAQ MCP Tool Definition
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
 * @since     2026-03-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Service\McpServer;

readonly class McpToolDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param array<string, mixed>|null $outputSchema
     * @param array<string, mixed> $annotations
     */
    public function __construct(
        public string $name,
        public string $description,
        public ?string $title,
        public array $inputSchema,
        public ?array $outputSchema = null,
        public array $annotations = [],
    ) {
    }
}
