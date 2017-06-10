<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this Source code.
 */

namespace Monolog\Formatter;

use Monolog\Logger;
use Monolog\TestCase;

class FlowdockFormatterTest extends TestCase
{
    /**
     * @covers Monolog\Formatter\FlowdockFormatter::format
     */
    public function testFormat()
    {
        $formatter = new FlowdockFormatter('test_source', 'Source@test.com');
        $record = $this->getRecord();

        $expected = array(
            'Source' => 'test_source',
            'from_address' => 'Source@test.com',
            'subject' => 'in test_source: WARNING - test',
            'content' => 'test',
            'tags' => array('#logs', '#warning', '#test'),
            'project' => 'test_source',
        );
        $formatted = $formatter->format($record);

        $this->assertEquals($expected, $formatted['flowdock']);
    }

    /**
     * @ covers Monolog\Formatter\FlowdockFormatter::formatBatch
     */
    public function testFormatBatch()
    {
        $formatter = new FlowdockFormatter('test_source', 'Source@test.com');
        $records = array(
            $this->getRecord(Logger::WARNING),
            $this->getRecord(Logger::DEBUG),
        );
        $formatted = $formatter->formatBatch($records);

        $this->assertArrayHasKey('flowdock', $formatted[0]);
        $this->assertArrayHasKey('flowdock', $formatted[1]);
    }
}
