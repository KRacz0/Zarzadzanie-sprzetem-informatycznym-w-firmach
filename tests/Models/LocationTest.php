<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/Location.php';

class LocationTest extends TestCase {
    public function testGetTotalLocationsReturnsCount(): void {
        $result = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_assoc'])
            ->getMock();

        $result->expects($this->once())
            ->method('fetch_assoc')
            ->willReturn(['total' => 5]);

        $conn = $this->getMockBuilder(stdClass::class)
            ->addMethods(['query'])
            ->getMock();

        $conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('COUNT(*) AS total'))
            ->willReturn($result);

        $location = new Location($conn);

        $this->assertSame(5, $location->getTotalLocations());
    }

    public function testGetTotalLocationsDefaultsToZero(): void {
        $result = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_assoc'])
            ->getMock();

        $result->expects($this->once())
            ->method('fetch_assoc')
            ->willReturn(null);

        $conn = $this->getMockBuilder(stdClass::class)
            ->addMethods(['query'])
            ->getMock();

        $conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('COUNT(*) AS total'))
            ->willReturn($result);

        $location = new Location($conn);

        $this->assertSame(0, $location->getTotalLocations());
    }
}
