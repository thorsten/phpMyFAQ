<?php

/**
 *  The ProblemDetails class for API error responses
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public License,
 *  v. 2.0. If a copy of the MPL was not distributed with this file, You can
 *  obtain one at https://mozilla.org/MPL/2.0/.
 *
 *  @package   phpMyFAQ
 *  @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 *  @copyright 2026 phpMyFAQ Team
 *  @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *  @link      https://www.phpmyfaq.de
 *  @since     2026-01-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Api;

final readonly class ProblemDetails
{
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        public string $instance,
        public ?string $code = null,
        public ?array $errors = null, // field-level errors, optional
        public ?string $traceId = null, // correlation id, optional
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
            'instance' => $this->instance,
        ];

        if ($this->code !== null) {
            $data['code'] = $this->code;
        }
        if ($this->errors !== null) {
            $data['errors'] = $this->errors;
        }
        if ($this->traceId !== null) {
            $data['traceId'] = $this->traceId;
        }

        return $data;
    }
}
