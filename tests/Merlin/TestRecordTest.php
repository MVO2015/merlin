<?php

use Lmc\Merlin\Server\TestRecord;
use PHPUnit\Framework\TestCase;

class TestRecordTest extends TestCase
{
    public function testBaselineRecord() {
        $timeStart = time();
        $timeEnd = $timeStart + 1;
        $testRecord = New TestRecord(
            "ENV",
            "JOB",
            "BUILD",
            "TESTCASE",
            "TESTNAME",
            "STATUS",
            $timeStart,
            $timeEnd,
            0,
            0);
        $this->assertTrue($testRecord->isBaseline());
    }
}
