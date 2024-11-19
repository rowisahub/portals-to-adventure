<?php

namespace PTA\Tests\DB;


if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

use PHPUnit\Framework\TestCase;
use PTA\DB\db_handler;
use PTA\DB\db_update;
use PTA\DB\db_backup;
use PTA\DB\db_functions;
use PTA\DB\Tables\UserInfoTable;
use PTA\DB\Tables\SubmissionDataTable;
use PTA\DB\Tables\ImageDataTable;
use PTA\logger\Log;
use Brain\Monkey;
use Brain\Monkey\Functions;

const ARRAY_A = 'ARRAY_A';
const ARRAY_N = 'ARRAY_N';
const OBJECT = 'OBJECT';

class DBHandlerTest extends TestCase
{
    private $dbHandler;
    private $logger;
    private $update;
    private $backup;
    private $functions;
    private $userInfoTable;
    private $submissionDataTable;
    private $imageDataTable;
    private $db_tables;

    protected function setUp(): void
    {
        parent::setUp();
        //echo "setUp\n";
        Monkey\setUp();
        //echo "Monkey\setUp\n";

        // Mock WordPress functions
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => '/bitnami/wordpress/wp-content/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads'
        ]);
        //echo "wp_upload_dir\n";
        Functions\when('get_option')->alias(function ($option) {
            if ($option === 'pta_environment') {
                return 'development'; // or 'production', depending on your test
            }
            if ($option === 'wld_pta_db_version') {
                return '1.0';
            }
            return false;
        });
        //echo "get_option\n";
        Functions\when('register_activation_hook')->justReturn(null);
        Functions\when('plugins_loaded')->justReturn(null);
        //echo "register_activation_hook\n";

        // Mock global $wpdb
        global $wpdb;
        $wpdb = $this->createMock(\wpdb::class);

        // Mock the logger
        //echo "Before logger\n";
        $this->logger = $this->getMockBuilder(Log::class)
            ->disableOriginalConstructor()
            ->getMock();
        //echo "After logger\n";
        $this->logger->method('getLogger')->willReturn(new \Psr\Log\NullLogger());
        //echo "logger\n";

        // Mock dependencies
        $this->update = $this->createMock(db_update::class);
        //echo "update\n";
        $this->backup = $this->createMock(db_backup::class);
        //echo "backup\n";
        $this->functions = $this->createMock(db_functions::class);
        //echo "functions\n";

        // Mock tables
        $this->userInfoTable = $this->createMock(UserInfoTable::class);
        //echo "userInfoTable\n";
        $this->submissionDataTable = $this->createMock(SubmissionDataTable::class);
        //echo "submissionDataTable\n";
        $this->imageDataTable = $this->createMock(ImageDataTable::class);
        //echo "imageDataTable\n";


        // Instantiate the db_handler with mocks
        $this->dbHandler = new db_handler(
            logger: $this->logger,
            update: $this->update,
            backup: $this->backup,
            functions: $this->functions,
            userInfoTable: $this->userInfoTable,
            submissionDataTable: $this->submissionDataTable,
            imageDataTable: $this->imageDataTable
        );

        $this->db_tables = [
            $this->userInfoTable,
            $this->submissionDataTable,
            $this->imageDataTable
        ];

        $this->dbHandler->init();

        //echo "setUp done\n";
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

        $this->dbHandler->plugin_activation();
    }

    public function testPluginActivationFailure()
    {
        // Simulate a failure in one of the table creations
        $this->userInfoTable->expects($this->once())
            ->method('create_table')
            ->willReturn(true);

        $this->submissionDataTable->expects($this->once())
            ->method('create_table')
            ->willReturn(false); // Simulate failure

        $this->imageDataTable->expects($this->never())
            ->method('create_table'); // Should not be called

        $responce = $this->dbHandler->plugin_activation();

        $this->assertFalse($responce);

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
        $this->assertSame($this->db_tables, $result);
    }

    public function testGetDbVersionWp()
    {
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
