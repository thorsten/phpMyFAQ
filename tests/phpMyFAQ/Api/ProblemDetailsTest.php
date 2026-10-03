<?php

declare(strict_types=1);

namespace phpMyFAQ\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProblemDetails::class)]
final class ProblemDetailsTest extends TestCase
{
    public function testToArrayContainsOnlyRequiredFieldsByDefault(): void
    {
        $problem = new ProblemDetails(
            type: 'https://www.phpmyfaq.de/problems/not-found',
            title: 'Not Found',
            status: 404,
            detail: 'FAQ 42 does not exist.',
            instance: '/api/v3.1/faq/42',
        );

        $this->assertSame(
            [
                'type' => 'https://www.phpmyfaq.de/problems/not-found',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'FAQ 42 does not exist.',
                'instance' => '/api/v3.1/faq/42',
            ],
            $problem->toArray(),
        );
    }

    public function testToArrayIncludesOptionalFieldsWhenSet(): void
    {
        $errors = ['question' => ['must not be blank']];

        $problem = new ProblemDetails(
            type: 'about:blank',
            title: 'Unprocessable Content',
            status: 422,
            detail: 'Validation failed.',
            instance: '/api/v3.1/faq',
            code: 'validation_failed',
            errors: $errors,
            traceId: 'abc-123',
        );

        $data = $problem->toArray();

        $this->assertSame(
            ['type', 'title', 'status', 'detail', 'instance', 'code', 'errors', 'traceId'],
            array_keys($data),
        );
        $this->assertSame('validation_failed', $data['code']);
        $this->assertSame($errors, $data['errors']);
        $this->assertSame('abc-123', $data['traceId']);
    }

    public function testEmptyErrorsArrayIsStillIncluded(): void
    {
        $problem = new ProblemDetails('about:blank', 'Bad Request', 400, 'Invalid.', '/api', errors: []);

        $this->assertArrayHasKey('errors', $problem->toArray());
        $this->assertSame([], $problem->toArray()['errors']);
    }

    public function testPropertiesAreExposedReadOnly(): void
    {
        $problem = new ProblemDetails('about:blank', 'Forbidden', 403, 'No access.', '/api/x', code: 'forbidden');

        $this->assertSame('about:blank', $problem->type);
        $this->assertSame('Forbidden', $problem->title);
        $this->assertSame(403, $problem->status);
        $this->assertSame('No access.', $problem->detail);
        $this->assertSame('/api/x', $problem->instance);
        $this->assertSame('forbidden', $problem->code);
        $this->assertNull($problem->errors);
        $this->assertNull($problem->traceId);
    }
}
