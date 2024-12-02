<?php
namespace PTA\Tests\DB\functions;

use PHPUnit\Framework\TestCase;
use PTA\DB\db_handler;
use PTA\DB\functions\user\user_functions;

print_r(get_included_files());

class UserFunctionsnotest extends TestCase
{
  private $userFunctions;
  private $dbHandlerMock;
  private $wpdbMock;

  protected function setUp(): void
  {
    parent::setUp();

    echo "Setting up mocks...\n";

    try {
      echo "Checking if db_handler class exists...\n";
      if (!class_exists('db_handler')) {
        throw new \Exception('Class db_handler does not exist.');
      }
      echo "Creating dbHandlerMock...\n";
      $this->dbHandlerMock = new db_handler();
      echo "dbHandlerMock created.\n";
    } catch (\Exception $e) {
      echo "Error creating dbHandlerMock: " . $e->getMessage() . "\n";
    }

    try {
      $this->userFunctions = new user_functions($this->dbHandlerMock, $this->dbHandlerMock->get_instance('functions'));
      echo "userFunctions instance created.\n";
    } catch (\Exception $e) {
      echo "Error creating userFunctions instance: " . $e->getMessage() . "\n";
    }

    echo "Setup complete.\n";
  }

  public function testGetUserSubmissionsInTimePeriod()
  {

    echo "Starting testGetUserSubmissionsInTimePeriod...\n";

    $user_id = 1;
    $time_period = '1 day';

    $expected_sql = "SELECT user_owner_id FROM submission_data WHERE user_owner_id = $user_id AND created_at >= NOW() - INTERVAL 24 HOUR";

    $result = $this->userFunctions->get_user_submissions_in_time_period($user_id, $time_period);

    // print the result and expected_sql to see if they match
    //print_r($result);
    //print_r($expected_sql);

    $this->assertEquals($expected_sql, $result);

    echo "Test complete.\n";
  }
}