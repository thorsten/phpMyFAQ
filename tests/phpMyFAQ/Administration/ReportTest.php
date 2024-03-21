<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{

    public function testSanitize(): void
    {
        $data = [
            ['John Doe', 'john.doe@example.com', '12345'],
            ['Jane Smith', 'jane.smith@example.com', '=SUM(A1:A10)'],
        ];

        $actual = [];

        $expected = [
            'John Doe,"john.doe@example.com",12345',
            'Jane Smith,"jane.smith@example.com","=SUM(A1:A10)"'
        ];

        foreach ($data as $row) {
            $csvRow = array_map(['phpMyFAQ\Administration\Report', 'sanitize'], $row);
            $actual[] = implode(',', $csvRow);
        }

        $this->assertEquals($expected, $actual);
    }

    public function testConvertEncodingWithHtmlEntitiesAndCommas(): void
    {
        $inputString = '&lt;p&gt;This is a test, &amp;sample string.&lt;/p&gt;';
        $expectedOutput = '<p>This is a test  &sample string.</p>';
        $report = new Report(Configuration::getConfigurationInstance());
        $actualOutput = $report->convertEncoding($inputString);

        $this->assertEquals($expectedOutput, $actualOutput);
    }
}
