<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * @testdox A Network has
 */
class NetworkTest extends TestCase
{
    /** @var Network */
    private $network;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $configuration = new Configuration($dbHandle);
        $this->network = new Network($configuration);
    }

    /**
     * @dataProvider ipAddressDataProvider
     * @testdox IP addresses, which are anonymize
     * @param $ipAddress
     * @param $anonymizeAddress
     */
    public function testAnonymizeIp($ipAddress, $anonymizeAddress)
    {
        $this->assertEquals($this->network->anonymizeIp($ipAddress), $anonymizeAddress);
    }

    /**
     * @return string[]
     */
    public function ipAddressDataProvider(): array
    {
        return [
            [ '207.142.131.005', '207.142.131.xxx'],
            [ '2001:0db8:0000:08d3:0000:8a2e:0070:7344', '2001:0db8:0000:08d3:0000:8a2e:xxxx:xxxx' ],
            [ '2001:0db8:0000:08d3:0000:8a2e:0070:734a', '2001:0db8:0000:08d3:0000:8a2e:xxxx:xxxx' ],
            [ '207.142.131.5', '207.142.131.xxx' ],
            [ '2001:0db8::8d3::8a2e:7:7344', '2001:0db8::8d3::8a2e:xxxx:xxxx' ],
            [ '::1', ':xxxx:xxxx' ],
            [ '127.0.0.1', '127.0.0.xxx' ]
        ];
    }
}
