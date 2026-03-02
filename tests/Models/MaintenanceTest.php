<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/Maintenance.php';

class MaintenanceTest extends TestCase {
    public function testGetLocationsWithOutdatedServiceReturnsResults(): void {
        $result = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_all'])
            ->getMock();

        $expected = [
            ['id' => 1, 'name' => 'Stacja Test1', 'last_service_date' => null],
        ];

        $result->expects($this->once())
            ->method('fetch_all')
            ->with(MYSQLI_ASSOC)
            ->willReturn($expected);

        $conn = $this->getMockBuilder(stdClass::class)
            ->addMethods(['query'])
            ->getMock();

        $conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('FROM locations'))
            ->willReturn($result);

        $maintenance = new Maintenance($conn);

        $this->assertSame($expected, $maintenance->getLocationsWithOutdatedService());
    }

    public function testGetAllLocationsWithLastServiceDateReturnsResults(): void {
        $result = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_all'])
            ->getMock();

        $expected = [
            ['id' => 2, 'name' => 'Stacja Test2', 'last_service_date' => '2026-01-01'],
        ];

        $result->expects($this->once())
            ->method('fetch_all')
            ->with(MYSQLI_ASSOC)
            ->willReturn($expected);

        $conn = $this->getMockBuilder(stdClass::class)
            ->addMethods(['query'])
            ->getMock();

        $conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('GROUP BY l.id'))
            ->willReturn($result);

        $maintenance = new Maintenance($conn);

        $this->assertSame($expected, $maintenance->getAllLocationsWithLastServiceDate());
    }
}
