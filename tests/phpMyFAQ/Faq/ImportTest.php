<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportTest extends TestCase
{
    private Import $faqImport;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);


        $this->faqImport = new Import($configuration);
    }
    public function testParseCSV(): void
    {
        $csvContent = "John,Doe,30\nJane,Smith,25\n";
        $csvFile = tmpfile();
        fwrite($csvFile, $csvContent);
        fseek($csvFile, 0);

        $csvData = $this->faqImport->parseCSV($csvFile);

        $expectedData = [
            ['John', 'Doe', '30'],
            ['Jane', 'Smith', '25'],
        ];

        $this->assertEquals($expectedData, $csvData);

        fclose($csvFile);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFile(): void
    {
        // Create a mock for the file object (replace with your actual File class)
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn('example.csv');

        // Call the method to test
        $result = $this->faqImport->isCSVFile($fileMock);

        // Assert the result
        $this->assertTrue($result);
    }

    public function testValidateCSV(): void
    {
        $validCSVData = [
            ['value1', 'value2', 'value3', 'value4', 'value5', 'value6', 'value7', 'true', 'false'],
        ];

        $isValid = $this->faqImport->validateCSV($validCSVData);

        $this->assertTrue($isValid);

        $invalidCSVData = [
            ['value1', 'value2', 'value3', 'value4', 'value5', 'value6', 'value7', 'true'],
        ];

        $isValid = $this->faqImport->validateCSV($invalidCSVData);

        $this->assertFalse($isValid);
    }
}
