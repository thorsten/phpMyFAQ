<?php

/**
 * Operation for inserting form inputs during installation.
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
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Forms;
use Throwable;

readonly class FormInputInsertOperation implements OperationInterface
{
    /**
     * @param array<string, int|string> $formInput
     */
    public function __construct(
        private Configuration $configuration,
        private array $formInput,
    ) {
    }

    public function getType(): string
    {
        return 'form_input_insert';
    }

    public function getDescription(): string
    {
        return sprintf(
            'Insert form input: form=%d, input=%d, label=%s',
            $this->formInput['form_id'],
            $this->formInput['input_id'],
            $this->formInput['input_label'],
        );
    }

    public function execute(): bool
    {
        try {
            $forms = new Forms($this->configuration);
            return $forms->insertInputIntoDatabase($this->formInput);
        } catch (Throwable) {
            return false;
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'form_input' => $this->formInput,
        ];
    }
}
