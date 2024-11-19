<?php
namespace PTA\interfaces\DB;

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Interface for database query builders.
 */
interface QueryBuilderInterface
{
  /**
   * Specify the columns to select.
   *
   * @param string|array $fields Field names to select.
   * @return $this
   */
  public function select($fields);

  /**
   * Specify the table to select from.
   *
   * @param string $table Table name.
   * @return $this
   */
  public function from($table);

  /**
   * Add a JOIN clause.
   *
   * @param string|array $table Table name or array with alias.
   * @param string       $condition Join condition.
   * @param string       $type      Type of join (INNER, LEFT, etc.).
   * @return $this
   */
  public function join($table, $condition, $type = 'INNTER');

  /**
   * Add a WHERE condition using a nested associative array.
   *
   * @param array $conditions Conditions array.
   * @return $this
   */
  public function where(array $conditions);

  /**
   * Add a GROUP BY clause.
   *
   * @param string|array $fields Field names to group by.
   * @return $this
   */
  public function groupBy($fields);

  /**
   * Add a HAVING clause.
   *
   * @param string $condition HAVING condition.
   * @return $this
   */
  public function having($condition);

  /**
   * Add an ORDER BY clause.
   *
   * @param string|array $fields Field names to order by.
   * @param string       $order  Order direction.
   * @return $this
   */
  public function orderBy($fields, $order = 'ASC');

  /**
   * Limit the number of results.
   *
   * @param int $limit Number of records to limit.
   * @return $this
   */
  public function limit($limit);

  /**
   * Offset the results.
   *
   * @param int $offset Number of records to skip.
   * @return $this
   */
  public function offset($offset);

  /**
   * Get the SQL query.
   *
   * @return string SQL query.
   */
  public function getSql();

  /**
   * Get the first result.
   *
   * @param string $output OBJECT, ARRAY_A, or ARRAY_N.
   * @return mixed|null First result or null.
   */
  public function first($output = OBJECT);

}