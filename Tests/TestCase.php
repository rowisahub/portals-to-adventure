<?php
namespace PTA\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Monkey\Functions\when('plugin_basename')->justReturn('test-plugin/test-plugin.php');
        Monkey\Functions\when('get_plugin_data')->justReturn(['Version' => '1.0.0']);
        Monkey\Functions\when('get_option')->justReturn('test-token');
        Monkey\Functions\when('get_bloginfo')->justReturn('6.0');
        Monkey\Functions\when('is_wp_error')->justReturn(false);
        Monkey\Functions\when('add_filter')->justReturn(true);
        Monkey\Functions\when('download_url')->justReturn('/tmp/test.zip');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}