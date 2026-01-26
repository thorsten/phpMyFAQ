<?php

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Setup\Migration\Operations\OperationInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MigrationResultTest extends TestCase
{
    public function testConstructorSetsVersionAndDescription(): void
    {
        $result = new MigrationResult('4.0.0', 'Test migration');

        $this->assertEquals('4.0.0', $result->getVersion());
        $this->assertEquals('Test migration', $result->getDescription());
    }

    public function testDefaultsToSuccess(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertTrue($result->isSuccess());
    }

    public function testDefaultsToNotDryRun(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertFalse($result->isDryRun());
    }

    public function testSetSuccess(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $result->setSuccess(false);

        $this->assertFalse($result->isSuccess());
    }

    public function testSetSuccessReturnsSelf(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertSame($result, $result->setSuccess(true));
    }

    public function testSetDryRun(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $result->setDryRun(true);

        $this->assertTrue($result->isDryRun());
    }

    public function testSetDryRunReturnsSelf(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertSame($result, $result->setDryRun(true));
    }

    public function testSetErrorMessage(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $result->setErrorMessage('Something went wrong');

        $this->assertEquals('Something went wrong', $result->getErrorMessage());
    }

    public function testSetErrorMessageReturnsSelf(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertSame($result, $result->setErrorMessage('Error'));
    }

    public function testErrorMessageDefaultsToNull(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertNull($result->getErrorMessage());
    }

    public function testSetExecutionTimeMs(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $result->setExecutionTimeMs(123.45);

        $this->assertEquals(123.45, $result->getExecutionTimeMs());
    }

    public function testSetExecutionTimeMsReturnsSelf(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertSame($result, $result->setExecutionTimeMs(100));
    }

    public function testExecutionTimeMsDefaultsToZero(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');

        $this->assertEquals(0, $result->getExecutionTimeMs());
    }

    public function testAddOperationResult(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $operation = $this->createMock(OperationInterface::class);

        $result->addOperationResult($operation, true);

        $this->assertCount(1, $result->getOperationResults());
    }

    public function testAddOperationResultReturnsSelf(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $operation = $this->createMock(OperationInterface::class);

        $this->assertSame($result, $result->addOperationResult($operation, true));
    }

    public function testAddFailedOperationSetsSuccessToFalse(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $operation = $this->createMock(OperationInterface::class);

        $this->assertTrue($result->isSuccess());
        $result->addOperationResult($operation, false, 'Failed');

        $this->assertFalse($result->isSuccess());
    }

    public function testGetSuccessCount(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $op1 = $this->createMock(OperationInterface::class);
        $op2 = $this->createMock(OperationInterface::class);
        $op3 = $this->createMock(OperationInterface::class);

        $result->addOperationResult($op1, true);
        $result->addOperationResult($op2, false, 'Error');
        $result->addOperationResult($op3, true);

        $this->assertEquals(2, $result->getSuccessCount());
    }

    public function testGetFailureCount(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $op1 = $this->createMock(OperationInterface::class);
        $op2 = $this->createMock(OperationInterface::class);
        $op3 = $this->createMock(OperationInterface::class);

        $result->addOperationResult($op1, true);
        $result->addOperationResult($op2, false, 'Error 1');
        $result->addOperationResult($op3, false, 'Error 2');

        $this->assertEquals(2, $result->getFailureCount());
    }

    public function testGetOperationCount(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $op1 = $this->createMock(OperationInterface::class);
        $op2 = $this->createMock(OperationInterface::class);

        $result->addOperationResult($op1, true);
        $result->addOperationResult($op2, true);

        $this->assertEquals(2, $result->getOperationCount());
    }

    public function testOperationResultsContainExpectedData(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $operation = $this->createMock(OperationInterface::class);

        $result->addOperationResult($operation, false, 'Test error');

        $results = $result->getOperationResults();
        $this->assertCount(1, $results);
        $this->assertSame($operation, $results[0]['operation']);
        $this->assertFalse($results[0]['success']);
        $this->assertEquals('Test error', $results[0]['error']);
    }

    public function testToArray(): void
    {
        $result = new MigrationResult('4.0.0', 'Test migration');
        $result->setSuccess(true)->setDryRun(true)->setExecutionTimeMs(100.5);

        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getType')->willReturn('sql');
        $operation->method('getDescription')->willReturn('Execute SQL');

        $result->addOperationResult($operation, true);

        $array = $result->toArray();

        $this->assertEquals('4.0.0', $array['version']);
        $this->assertEquals('Test migration', $array['description']);
        $this->assertTrue($array['success']);
        $this->assertTrue($array['dryRun']);
        $this->assertNull($array['errorMessage']);
        $this->assertEquals(100.5, $array['executionTimeMs']);
        $this->assertEquals(1, $array['operationCount']);
        $this->assertEquals(1, $array['successCount']);
        $this->assertEquals(0, $array['failureCount']);
        $this->assertCount(1, $array['operations']);
        $this->assertEquals('sql', $array['operations'][0]['type']);
        $this->assertEquals('Execute SQL', $array['operations'][0]['description']);
        $this->assertTrue($array['operations'][0]['success']);
        $this->assertNull($array['operations'][0]['error']);
    }

    public function testToArrayWithError(): void
    {
        $result = new MigrationResult('4.0.0', 'Test');
        $result->setSuccess(false)->setErrorMessage('Migration failed');

        $array = $result->toArray();

        $this->assertFalse($array['success']);
        $this->assertEquals('Migration failed', $array['errorMessage']);
    }
}
