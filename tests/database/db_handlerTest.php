<?php

use PHPUnit\Framework\TestCase;
use PTA\DB\db_handler;
use PTA\DB\Tables\UserInfoTable;
use PTA\DB\Tables\SubmissionDataTable;
use PTA\DB\Tables\ImageDataTable;

class db_handlerTest extends TestCase
{
  private $dbHandler;

  protected function setUp(): void
  {
    $this->dbHandler = new db_handler();
  }

  public function testGetTablePath()
  {
    $userInfoTableMock = $this->createMock(UserInfoTable::class);
    $userInfoTableMock->method('get_table_path')->willReturn('user_info_path');
    $this->dbHandler->user_info_table = $userInfoTableMock;

    $submissionDataTableMock = $this->createMock(SubmissionDataTable::class);
    $submissionDataTableMock->method('get_table_path')->willReturn('submission_data_path');
    $this->dbHandler->submission_data_table = $submissionDataTableMock;

    $imageDataTableMock = $this->createMock(ImageDataTable::class);
    $imageDataTableMock->method('get_table_path')->willReturn('image_data_path');
    $this->dbHandler->image_data_table = $imageDataTableMock;

    $this->assertEquals('user_info_path', $this->dbHandler->get_table_path('user_info'));
    $this->assertEquals('submission_data_path', $this->dbHandler->get_table_path('submission_data'));
    $this->assertEquals('image_data_path', $this->dbHandler->get_table_path('image_data'));
    $this->assertNull($this->dbHandler->get_table_path('invalid_table'));
  }

  public function testGetTable()
  {
    $userInfoTableMock = $this->createMock(UserInfoTable::class);
    $this->dbHandler->user_info_table = $userInfoTableMock;

    $submissionDataTableMock = $this->createMock(SubmissionDataTable::class);
    $this->dbHandler->submission_data_table = $submissionDataTableMock;

    $imageDataTableMock = $this->createMock(ImageDataTable::class);
    $this->dbHandler->image_data_table = $imageDataTableMock;

    $this->assertSame($userInfoTableMock, $this->dbHandler->get_table('user_info'));
    $this->assertSame($submissionDataTableMock, $this->dbHandler->get_table('submission_data'));
    $this->assertSame($imageDataTableMock, $this->dbHandler->get_table('image_data'));
    $this->assertSame([$userInfoTableMock, $submissionDataTableMock, $imageDataTableMock], $this->dbHandler->get_table('all'));
    $this->assertNull($this->dbHandler->get_table('invalid_table'));
  }

  public function testGetDbVersionWp()
  {
    update_option('wld_pta_db_version', '1.0');
    $this->assertEquals('1.0', $this->dbHandler->get_db_version_wp());

    delete_option('wld_pta_db_version');
    $this->assertEquals('1.0', $this->dbHandler->get_db_version_wp());
  }

  public function testGetDbVersionLocal()
  {
    $this->assertEquals('1.0', $this->dbHandler->get_db_version_local());
  }

  public function testGetPtaPrefix()
  {
    $this->assertEquals('wld_pta_', $this->dbHandler->get_pta_prefix());
  }
}
?>