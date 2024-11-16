<?php
// tests/DBHandlerTest.php

namespace PTA\Tests\DB;

use PHPUnit\Framework\TestCase;
use PTA\DB\db_handler;
use PTA\DB\db_update;
use PTA\DB\db_backup;
use PTA\DB\db_functions;
use PTA\DB\Tables\UserInfoTable;
use PTA\DB\Tables\SubmissionDataTable;
use PTA\DB\Tables\ImageDataTable;
use PTA\logger\log;
use Brain\Monkey;
use Brain\Monkey\Functions;

class DBHandlerTest extends TestCase
{
    private $dbHandler;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Mock the logger
        $this->logger = $this->createMock(log::class);

        // Mock dependencies
        $this->update = $this->createMock(db_update::class);
        $this->backup = $this->createMock(db_backup::class);
        $this->functions = $this->createMock(db_functions::class);

        // Mock tables
        $this->userInfoTable = $this->createMock(UserInfoTable::class);
        $this->submissionDataTable = $this->createMock(SubmissionDataTable::class);
        $this->imageDataTable = $this->createMock(ImageDataTable::class);

        // Instantiate the db_handler
        $this->dbHandler = new db_handler();

        // Inject mocks
        $this->dbHandler->logger = $this->logger;
        $this->dbHandler->update = $this->update;
        $this->dbHandler->backup = $this->backup;
        $this->dbHandler->functions = $this->functions;

        $this->dbHandler->user_info_table = $this->userInfoTable;
        $this->dbHandler->submission_data_table = $this->submissionDataTable;
        $this->dbHandler->image_data_table = $this->imageDataTable;

        $this->dbHandler->db_tables = [
            $this->userInfoTable,
            $this->submissionDataTable,
            $this->imageDataTable
        ];
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testPluginActivation()
    {
        // Mock create_table to return true
        $this->userInfoTable->expects($this->once())
            ->method('create_table')
            ->willReturn(true);

        $this->submissionDataTable->expects($this->once())
            ->method('create_table')
            ->willReturn(true);

        $this->imageDataTable->expects($this->once())
            ->method('create_table')
            ->willReturn(true);

        // Expect logger to log info
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Tables created successfully');

        $this->dbHandler->plugin_activation();
    }

    public function testGetTablePath()
    {
        $this->userInfoTable->expects($this->once())
            ->method('get_table_path')
            ->willReturn('path_to_user_info');

        $result = $this->dbHandler->get_table_path('user_info');
        $this->assertEquals('path_to_user_info', $result);
    }

    public function testGetTable()
    {
        $result = $this->dbHandler->get_table('user_info');
        $this->assertSame($this->userInfoTable, $result);

        $result = $this->dbHandler->get_table('submission_data');
        $this->assertSame($this->submissionDataTable, $result);

        $result = $this->dbHandler->get_table('image_data');
        $this->assertSame($this->imageDataTable, $result);

        $result = $this->dbHandler->get_table('all');
        $this->assertSame($this->dbHandler->db_tables, $result);
    }

    public function testGetDbVersionWp()
    {
        Functions\expect('get_option')
            ->once()
            ->with('wld_pta_db_version')
            ->andReturn(false);

        Functions\expect('update_option')
            ->once()
            ->with('wld_pta_db_version', '1.0');

        $result = $this->dbHandler->get_db_version_wp();
        $this->assertEquals('1.0', $result);
    }

    public function testGetDbVersionLocal()
    {
        $result = $this->dbHandler->get_db_version_local();
        $this->assertEquals('1.0', $result);
    }

    public function testGetPtaPrefix()
    {
        $result = $this->dbHandler->get_pta_prefix();
        $this->assertEquals('wld_pta_', $result);
    }
}
