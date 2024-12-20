<?php
namespace PTA\Tests\Update;

use PTA\Update\Plugin_Updater;
use PTA\Tests\TestCase;
use Brain\Monkey\Functions;

class PluginUpdaterTest extends TestCase
{
    private $updater;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->updater = new Plugin_Updater(
            __DIR__ . '/test-plugin.php',
            'test-user',
            'test-repo'
        );
    }

    public function testCheckUpdate()
    {
        $mockResponse = [
            'tag_name' => 'v2.0.0',
            'html_url' => 'https://github.com/test/test',
            'zipball_url' => 'https://api.github.com/repos/test/test/zipball/v2.0.0'
        ];

        Functions\when('wp_remote_get')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode($mockResponse)
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode($mockResponse));

        $transient = new \stdClass();
        $transient->checked = ['test-plugin/test-plugin.php' => '1.0.0'];

        $result = $this->updater->check_update($transient);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertObjectHasProperty('response', $result);
    }

    public function testNoUpdateNeeded()
    {
        $mockResponse = [
            'tag_name' => 'v1.0.0',
            'html_url' => 'https://github.com/test/test',
            'zipball_url' => 'https://api.github.com/repos/test/test/zipball/v1.0.0'
        ];

        Functions\when('wp_remote_get')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode($mockResponse)
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode($mockResponse));

        $transient = new \stdClass();
        $transient->checked = ['test-plugin/test-plugin.php' => '1.0.0'];

        $result = $this->updater->check_update($transient);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertObjectNotHasProperty('response', $result);
    }

    public function testUpgraderPreDownload()
    {
        $package = 'https://api.github.com/repos/test/test/zipball/v2.0.0';

        Functions\when('wp_remote_get')->justReturn([
            'response' => ['code' => 200],
            'body' => 'test-body'
        ]);

        $upgrader = $this->createMock(\stdClass::class);
        $upgrader->strings = [];

        $result = $this->updater->upgrader_pre_download(true, $package, $upgrader);

        $this->assertEquals('/tmp/test.zip', $result);
    }
}