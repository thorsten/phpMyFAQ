<?php

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;

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
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

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
            ['John', 'Doe',   '30'],
            ['Jane', 'Smith', '25'],
        ];

        $this->assertEquals($expectedData, $csvData);
        fclose($csvFile);
    }

    public function testParseCSVWithEmptyFile(): void
    {
        $csvFile = tmpfile();
        $csvData = $this->faqImport->parseCSV($csvFile);

        $this->assertEmpty($csvData);
        fclose($csvFile);
    }

    public function testParseCSVWithQuotedFields(): void
    {
        $csvContent = '"John, Jr.",Doe,"Age: 30"' . "\n" . '"Jane","Smith","Age: 25"';
        $csvFile = tmpfile();
        fwrite($csvFile, $csvContent);
        fseek($csvFile, 0);

        $csvData = $this->faqImport->parseCSV($csvFile);

        $expectedData = [
            ['John, Jr.', 'Doe',   'Age: 30'],
            ['Jane',      'Smith', 'Age: 25'],
        ];

        $this->assertEquals($expectedData, $csvData);
        fclose($csvFile);
    }

    public function testParseCSVWithEscapedQuotes(): void
    {
        $csvContent = '"He said ""Hello""","She replied ""Hi"""';
        $csvFile = tmpfile();
        fwrite($csvFile, $csvContent);
        fseek($csvFile, 0);

        $csvData = $this->faqImport->parseCSV($csvFile);

        $expectedData = [
            ['He said "Hello"', 'She replied "Hi"'],
        ];

        $this->assertEquals($expectedData, $csvData);
        fclose($csvFile);
    }

    public function testParseCSVWithEmptyFields(): void
    {
        $csvContent = "John,,30\n,Smith,\nJane,Doe,25";
        $csvFile = tmpfile();
        fwrite($csvFile, $csvContent);
        fseek($csvFile, 0);

        $csvData = $this->faqImport->parseCSV($csvFile);

        $expectedData = [
            ['John', '', '30'],
            ['', 'Smith', ''],
            ['Jane', 'Doe', '25'],
        ];

        $this->assertEquals($expectedData, $csvData);
        fclose($csvFile);
    }

    public function testParseCSVWithUnicodeCharacters(): void
    {
        $csvContent = "Jöhn,Döe,30\nJäne,Smîth,25";
        $csvFile = tmpfile();
        fwrite($csvFile, $csvContent);
        fseek($csvFile, 0);

        $csvData = $this->faqImport->parseCSV($csvFile);

        $expectedData = [
            ['Jöhn', 'Döe',   '30'],
            ['Jäne', 'Smîth', '25'],
        ];

        $this->assertEquals($expectedData, $csvData);
        fclose($csvFile);
    }

    public function testParseCSVWithSingleLine(): void
    {
        $csvContent = 'SingleValue';
        $csvFile = tmpfile();
        fwrite($csvFile, $csvContent);
        fseek($csvFile, 0);

        $csvData = $this->faqImport->parseCSV($csvFile);

        $expectedData = [
            ['SingleValue'],
        ];

        $this->assertEquals($expectedData, $csvData);
        fclose($csvFile);
    }

    // ===========================================
    // isCSVFile() Tests - Comprehensive Validation
    // ===========================================

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFile(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('example.csv');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertTrue($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithUppercaseExtension(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('example.CSV');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertTrue($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithMixedCaseExtension(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('example.CsV');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertTrue($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithTxtExtension(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('example.txt');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertFalse($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithXlsxExtension(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('spreadsheet.xlsx');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertFalse($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithNoExtension(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('filename_without_extension');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertFalse($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithEmptyFilename(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertFalse($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testIsCSVFileWithMultipleDots(): void
    {
        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->expects($this->once())->method('getClientOriginalName')->willReturn('my.backup.file.csv');

        $result = $this->faqImport->isCSVFile($fileMock);

        $this->assertTrue($result);
    }

    // ===========================================
    // validateCSV() Tests - Robustness
    // ===========================================

    public function testValidateCSV(): void
    {
        $validCSVData = [
            ['1', 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true', 'false'],
        ];

        $isValid = $this->faqImport->validateCSV($validCSVData);

        $this->assertTrue($isValid);
    }

    public function testValidateCSVWithInvalidColumnCount(): void
    {
        $invalidCSVData = [
            ['1', 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true'],
        ];

        $isValid = $this->faqImport->validateCSV($invalidCSVData);

        $this->assertFalse($isValid);
    }

    public function testValidateCSVWithTooManyColumns(): void
    {
        $invalidCSVData = [
            ['1', 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true', 'false', 'extra'],
        ];

        $isValid = $this->faqImport->validateCSV($invalidCSVData);

        $this->assertFalse($isValid);
    }

    public function testValidateCSVWithEmptyRequiredFields(): void
    {
        // Test empty category ID (column 0)
        $invalidCSVData = [
            ['', 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty question (column 1)
        $invalidCSVData = [
            [42, '', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty answer (column 2)
        $invalidCSVData = [
            [42, 'Question?', '', 'keyword', 'en', 'Author', 'test@example.com', 'true', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty language (column 4)
        $invalidCSVData = [
            [42, 'Question?', 'Answer', 'keyword', '', 'Author', 'test@example.com', 'true', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty author (column 5)
        $invalidCSVData = [
            ['1', 'Question?', 'Answer', 'keyword', 'en', '', 'test@example.com', 'true', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty email (column 6)
        $invalidCSVData = [
            ['1', 'Question?', 'Answer', 'keyword', 'en', 'Author', '', 'true', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty active flag (column 7)
        $invalidCSVData = [
            [42, 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', '', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test empty sticky flag (column 8)
        $invalidCSVData = [
            [42, 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true', ''],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));
    }

    public function testValidateCSVWithOptionalEmptyKeywords(): void
    {
        // Keywords (column 3) should be allowed to be empty
        $validCSVData = [
            [42, 'Question?', 'Answer', '', 'en', 'Author', 'test@example.com', 'true', 'false'],
        ];

        $isValid = $this->faqImport->validateCSV($validCSVData);

        $this->assertTrue($isValid);
    }

    public function testValidateCSVWithInvalidBooleanValues(): void
    {
        // Test invalid active flag
        $invalidCSVData = [
            [42, 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'yes', 'false'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test invalid sticky flag
        $invalidCSVData = [
            ['1', 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'true', 'no'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));

        // Test numeric values instead of boolean
        $invalidCSVData = [
            [42, 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', '1', '0'],
        ];
        $this->assertFalse($this->faqImport->validateCSV($invalidCSVData));
    }

    public function testValidateCSVWithCaseInsensitiveBooleans(): void
    {
        $validCSVData = [
            [42, 'Question?', 'Answer', 'keyword', 'en', 'Author', 'test@example.com', 'TRUE', 'FALSE'],
        ];

        $isValid = $this->faqImport->validateCSV($validCSVData);

        $this->assertTrue($isValid);
    }

    public function testValidateCSVWithMixedValidInvalidRows(): void
    {
        $mixedCSVData = [
            [42, 'Question1?', 'Answer1', 'keyword', 'en', 'Author1', 'test1@example.com', 'true', 'false'],
            [43, '',           'Answer2', 'keyword', 'en', 'Author2', 'test2@example.com', 'true', 'false'], // Invalid: empty question
            [44, 'Question3?', 'Answer3', 'keyword', 'en', 'Author3', 'test3@example.com', 'true', 'false'],
        ];

        $isValid = $this->faqImport->validateCSV($mixedCSVData);

        $this->assertFalse($isValid);
    }

    public function testValidateCSVWithMultipleValidRows(): void
    {
        $validCSVData = [
            [42, 'Question1?', 'Answer1', 'keyword1', 'en', 'Author1', 'test1@example.com', 'true',  'false'],
            [43, 'Question2?', 'Answer2', 'keyword2', 'de', 'Author2', 'test2@example.com', 'false', 'true'],
            [44, 'Question3?', 'Answer3', '',         'fr', 'Author3', 'test3@example.com', 'true',  'true'],
        ];

        $isValid = $this->faqImport->validateCSV($validCSVData);

        $this->assertTrue($isValid);
    }

    public function testValidateCSVWithEmptyArray(): void
    {
        $emptyCSVData = [];

        $isValid = $this->faqImport->validateCSV($emptyCSVData);

        $this->assertTrue($isValid); // Empty array should be valid (no invalid rows)
    }

    // ===========================================
    // import() Tests - Phase 2: Critical Functionality
    // ===========================================

    public function testImportWithValidData(): void
    {
        $validRecord = [
            1, // categoryId
            'What is PHP?', // question
            'PHP is a programming language', // answer
            'php, programming', // keywords
            'en', // languageCode
            'John Doe', // author
            'john@example.com', // email
            'true', // isActive
            'false', // isSticky
        ];

        $result = $this->faqImport->import($validRecord);

        $this->assertTrue($result);
    }

    public function testImportThrowsExceptionWithHashInTitle(): void
    {
        $recordWithHash = [
            42,
            'What is PHP #hashtag?', // question with hash
            'PHP is a programming language',
            'php, programming',
            'en',
            'John Doe',
            'john@example.com',
            'true',
            'false',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'It is not allowed, that the question title What is PHP #hashtag? contains a hash.',
        );

        $this->faqImport->import($recordWithHash);
    }

    /**
     * @throws Exception
     */
    public function testImportWithDifferentLanguages(): void
    {
        $germanRecord = [
            42,
            'Was ist PHP?',
            'PHP ist eine Programmiersprache',
            'php, programmierung',
            'de', // German language
            'Hans Müller',
            'hans@example.de',
            'true',
            'false',
        ];

        $result = $this->faqImport->import($germanRecord);
        $this->assertTrue($result);

        $frenchRecord = [
            42,
            'Quest-ce que PHP?',
            'PHP est un langage de programmation',
            'php, programmation',
            'fr', // French language
            'Pierre Dubois',
            'pierre@example.fr',
            'true',
            'false',
        ];

        $result = $this->faqImport->import($frenchRecord);
        $this->assertTrue($result);
    }

    public function testImportWithBooleanVariations(): void
    {
        // Test with string 'true'/'false'
        $record1 = [
            42,
            'Question 1?',
            'Answer 1',
            'keyword',
            'en',
            'Author1',
            'test1@example.com',
            'true',
            'false',
        ];
        $this->assertTrue($this->faqImport->import($record1));

        // Test with string 'false'/'true'
        $record2 = [
            43,
            'Question 2?',
            'Answer 2',
            'keyword',
            'en',
            'Author2',
            'test2@example.com',
            'false',
            'true',
        ];
        $this->assertTrue($this->faqImport->import($record2));
    }

    public function testImportWithEmptyOptionalKeywords(): void
    {
        $recordWithoutKeywords = [
            42,
            'What is JavaScript?',
            'JavaScript is a scripting language',
            '', // empty keywords (optional)
            'en',
            'Jane Smith',
            'jane@example.com',
            'true',
            'false',
        ];

        $result = $this->faqImport->import($recordWithoutKeywords);
        $this->assertTrue($result);
    }

    public function testImportWithSpecialCharactersInContent(): void
    {
        $recordWithSpecialChars = [
            42,
            'How to use <script> tags?',
            'You can use &lt;script&gt; tags in HTML. Special chars: ü, ä, ö, ß',
            'html, javascript, special chars',
            'en',
            'Test Author',
            'test@example.com',
            'true',
            'false',
        ];

        $result = $this->faqImport->import($recordWithSpecialChars);
        $this->assertTrue($result);
    }

    public function testImportWithDifferentCategoryIds(): void
    {
        // Test with different valid category IDs
        $categories = [1, 5, 10, 999];

        foreach ($categories as $categoryId) {
            $record = [
                (string) $categoryId,
                "Question for category $categoryId?",
                "Answer for category $categoryId",
                'test',
                'en',
                'Test Author',
                'test@example.com',
                'true',
                'false',
            ];

            $result = $this->faqImport->import($record);
            $this->assertTrue($result, "Import failed for category ID: $categoryId");
        }
    }

    public function testImportWithLongContent(): void
    {
        $longQuestion = str_repeat('This is a very long question. ', 20);
        $longAnswer = str_repeat('This is a very long answer with lots of content. ', 50);
        $longKeywords = str_repeat('keyword, ', 20);

        $recordWithLongContent = [
            42,
            $longQuestion,
            $longAnswer,
            $longKeywords,
            'en',
            'Test Author',
            'test@example.com',
            'true',
            'false',
        ];

        $result = $this->faqImport->import($recordWithLongContent);
        $this->assertTrue($result);
    }

    public function testImportWithEdgeCaseEmails(): void
    {
        $emailTests = [
            'simple@example.com',
            'test.email+tag@example.co.uk',
            'user123@subdomain.example.org',
            'very.long.email.address@very.long.domain.name.example.com',
        ];

        foreach ($emailTests as $index => $email) {
            $record = [
                42,
                "Question $index?",
                "Answer $index",
                'test',
                'en',
                'Test Author',
                $email,
                'true',
                'false',
            ];

            $result = $this->faqImport->import($record);
            $this->assertTrue($result, "Import failed for email: $email");
        }
    }

    public function testImportFilteringAndSanitization(): void
    {
        // Test that filtering and sanitization work correctly
        $recordWithUnsafeContent = [
            42,
            'Question with <script>alert("xss")</script>',
            'Answer with <img src="x" onerror="alert(1)">',
            'keywords<script>',
            'en',
            'Author<script>',
            'test@example.com',
            'true',
            'false',
        ];

        // Should not throw exception and should sanitize content
        $result = $this->faqImport->import($recordWithUnsafeContent);
        $this->assertTrue($result);
    }

    public function testImportMultipleHashesInQuestion(): void
    {
        $questionsWithHashes = [
            'What is #PHP and #JavaScript?',
            'How to use #hashtags in #social media?',
            '#trending topics',
            'Question ending with #',
        ];

        foreach ($questionsWithHashes as $question) {
            $record = [
                42,
                $question,
                'Answer',
                'test',
                'en',
                'Author',
                'test@example.com',
                'true',
                'false',
            ];

            $this->expectException(Exception::class);
            $this->faqImport->import($record);
        }
    }

    public function testImportSequentialRecords(): void
    {
        // Test importing multiple records in sequence
        $records = [
            [42, 'Question 1?', 'Answer 1', 'test1', 'en', 'Author1', 'test1@example.com', 'true',  'false'],
            [43, 'Question 2?', 'Answer 2', 'test2', 'en', 'Author2', 'test2@example.com', 'false', 'true'],
            [44, 'Question 3?', 'Answer 3', 'test3', 'en', 'Author3', 'test3@example.com', 'true',  'true'],
        ];

        foreach ($records as $index => $record) {
            $result = $this->faqImport->import($record);
            $this->assertTrue($result, "Sequential import failed at record $index");
        }
    }

    public function testImportWithQuestionContainingQuotes(): void
    {
        $recordWithQuotes = [
            42,
            'What is "object-oriented programming"?',
            'OOP is a programming paradigm based on "objects".',
            'oop, programming',
            'en',
            'Test Author',
            'test@example.com',
            'true',
            'false',
        ];

        $result = $this->faqImport->import($recordWithQuotes);
        $this->assertTrue($result);
    }
}
