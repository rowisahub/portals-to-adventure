<?php
/*
File: src/plugin/DB/QueryBuilder.php
Description: Query builder for the plugin.
*/

namespace PTA\DB;

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

use PTA\interfaces\DB\QueryBuilderInterface;

/**
 * Class QueryBuilder
 *
 * A flexible and secure SQL query builder for constructing and executing complex database queries.
 * 
 * Create an instance of the QueryBuilder
 * 
 * $queryBuilder = new PTA\DB\QueryBuilder($wpdb);
 * 
 * $queryBuilder->select(['id', 'name'])
 * 
 *        ->from('my_table')
 *        ->where(['status' => 'active'])
 *        ->orderBy('name', 'ASC')
 *        ->limit(10);
 * 
 * $sql = $queryBuilder->get_sql();
 *
 * @package PTA\DB
 */
class QueryBuilder implements QueryBuilderInterface
{
  /**
   * WordPress database access object.
   *
   * @var wpdb
   */
  protected $wpdb;

  /**
   * SELECT clause fields.
   *
   * @var string
   */
  protected $select = '*';

  /**
   * FROM clause table.
   *
   * @var string
   */
  protected $from = '';

  /**
   * JOIN clauses.
   *
   * @var string
   */
  protected $joins = '';

  /**
   * WHERE clause conditions.
   *
   * @var string
   */
  protected $wheres = '';

  /**
   * GROUP BY clause fields.
   *
   * @var string
   */
  protected $groupBy = '';

  /**
   * HAVING clause conditions.
   *
   * @var string
   */
  protected $having = '';

  /**
   * ORDER BY clause fields.
   *
   * @var string
   */
  protected $orderBy = '';

  /**
   * LIMIT clause value.
   *
   * @var int
   */
  protected $limit = '';

  /**
   * OFFSET clause value.
   *
   * @var int
   */
  protected $offset = '';

  /**
   * Query bindings for prepared statements.
   *
   * @var array
   */
  protected $bindings = [];

  /**
   * Constructor.
   *
   * @param \wpdb $wpdb WordPress database access object.
   */
  public function __construct($wpdb)
  {
    $this->wpdb = $wpdb;
  }

  /**
   * Specify the columns to select.
   *
   * @param string|array $fields Field names to select.
   * @return $this
   */
  public function select($fields)
  {
    if (is_array($fields)) {
      $this->select = implode(', ', array_map([$this, 'escapeField'], $fields));
    } else {
      $args = func_get_args();
      $this->select = implode(', ', array_map([$this, 'escapeField'], $args));
    }
    return $this;
  }

  /**
   * Specify the table to select from.
   *
   * @param string|array $table Table name or array with alias.
   * @return $this
   */
  public function from($table)
  {
    if (is_array($table)) {
      $alias = reset($table);
      $table = key($table);
      $this->from = "{$this->escapeTable($table)} AS {$this->escapeField($alias)}";
    } else {
      $this->from = $this->escapeTable($table);
    }
    return $this;
  }

  /**
   * Add a JOIN clause.
   *
   * @param string|array $table Table name or array with alias.
   * @param string       $condition Join condition.
   * @param string       $type      Type of join (INNER, LEFT, etc.).
   * @return $this
   */
  public function join($table, $condition, $type = 'INNER')
  {
    if (is_array($table)) {
      $alias = reset($table);
      $table = key($table);
      $tableName = "{$this->escapeTable($table)} AS {$this->escapeField($alias)}";
    } else {
      $tableName = $this->escapeTable($table);
    }
    $join = strtoupper($type) . ' JOIN ' . $tableName . ' ON ' . $condition;
    $this->joins .= " {$join}";
    return $this;
  }

  /**
   * Add a WHERE condition using a nested associative array.
   *
   * @param array $conditions Conditions array.
   * @return $this
   */
  public function where(array $conditions)
  {
    $condition = $this->buildCondition($conditions);
    if ($condition) {
      $this->wheres = $this->wheres ? "({$this->wheres}) AND ({$condition})" : $condition;
    }
    return $this;
  }

  /**
   * Add a GROUP BY clause.
   *
   * @param string|array $fields Field names to group by.
   * @return $this
   */
  public function groupBy($fields)
  {
    $fields = is_array($fields) ? $fields : func_get_args();
    $this->groupBy = implode(', ', array_map([$this, 'escapeField'], $fields));
    return $this;
  }

  /**
   * Add a HAVING condition using a nested associative array.
   *
   * @param array $conditions Conditions array.
   * @return $this
   */
  public function having(array $conditions)
  {
    $havingClause = $this->buildCondition($conditions);
    if ($havingClause) {
      $this->having = $havingClause;
    }
    return $this;
  }

  /**
   * Add an ORDER BY clause.
   *
   * @param string|array $fields Field names or associative array of field => direction.
   * @return $this
   */
  public function orderBy($fields)
  {
    if (is_array($fields)) {
      $orderings = [];
      foreach ($fields as $field => $direction) {
        $orderings[] = "{$this->escapeField($field)} " . strtoupper($direction);
      }
      $this->orderBy = implode(', ', $orderings);
    } else {
      $args = func_get_args();
      $this->orderBy = implode(', ', array_map([$this, 'escapeField'], $args));
    }
    return $this;
  }

  /**
   * Limit the number of results.
   *
   * @param int $limit Number of records to limit.
   * @return $this
   */
  public function limit($limit)
  {
    $this->limit = (int) $limit;
    return $this;
  }

  /**
   * Offset the results.
   *
   * @param int $offset Number of records to skip.
   * @return $this
   */
  public function offset($offset)
  {
    $this->offset = (int) $offset;
    return $this;
  }

  /**
   * Execute the query and get the results.
   *
   * @param string $output OBJECT, ARRAY_A, or ARRAY_N.
   * @return array|null Query results.
   */
  public function get($output = OBJECT)
  {
    $sql = $this->buildQuery();
    $results = $this->wpdb->get_results($sql, $output);

    if ($this->wpdb->last_error) {
      error_log('Database Error: ' . $this->wpdb->last_error);
      return null;
    }

    return $results;
  }

  /**
   * Get the SQL query.
   *
   * @return string SQL query.
   */
  public function get_sql()
  {
    return $this->buildQuery();
  }

  /**
   * Get the first result.
   *
   * @param string $output OBJECT, ARRAY_A, or ARRAY_N.
   * @return mixed|null First result or null.
   */
  public function first($output = OBJECT)
  {
    $this->limit(1);
    $results = $this->get($output);
    return $results ? $results[0] : null;
  }

  /**
   * Build the SQL query.
   *
   * @return string SQL query string.
   */
  protected function buildQuery()
  {
    $sql = "SELECT {$this->select} FROM {$this->from}";

    if ($this->joins) {
      $sql .= $this->joins;
    }

    if ($this->wheres) {
      $sql .= " WHERE {$this->wheres}";
    }

    if ($this->groupBy) {
      $sql .= " GROUP BY {$this->groupBy}";
    }

    if ($this->having) {
      $sql .= " HAVING {$this->having}";
    }

    if ($this->orderBy) {
      $sql .= " ORDER BY {$this->orderBy}";
    }

    if ($this->limit !== '') {
      $sql .= " LIMIT {$this->limit}";
    }

    if ($this->offset !== '') {
      $sql .= " OFFSET {$this->offset}";
    }

    if ($this->bindings) {
      $sql = $this->wpdb->prepare($sql, $this->bindings);
    }

    return $sql;
  }

  /**
   * Build the WHERE or HAVING condition string and bindings.
   *
   * @param array  $conditions Conditions array.
   * @param string $operator   Logical operator (AND, OR).
   * @return string Condition string.
   */
  protected function buildCondition(array $conditions, $operator = 'AND')
  {
    $clauses = [];

    foreach ($conditions as $key => $value) {
      $keyUpper = strtoupper($key);

      if ($keyUpper === 'AND' || $keyUpper === 'OR') {
        $nested = $this->buildCondition($value, $keyUpper);
        if ($nested) {
          $clauses[] = "({$nested})";
        }
      } else {
        $clauses[] = $this->buildComparison($key, $value);
      }
    }

    return implode(" {$operator} ", $clauses);
  }

  /**
   * Build a comparison expression.
   *
   * @param string $field Field name and operator.
   * @param mixed  $value Value to compare.
   * @return string Comparison string.
   */
  protected function buildComparison($field, $value)
  {
    $operator = '=';
    $fieldParts = explode(' ', trim($field), 2);

    if (count($fieldParts) > 1) {
      $operator = strtoupper($fieldParts[1]);
      $field = $fieldParts[0];
    }

    // Check if field contains SQL functions and avoid escaping
    $escapedField = $this->needsEscaping($field) ? $this->escapeField($field) : $field;

    if (is_array($value)) {
      $placeholders = implode(', ', array_fill(0, count($value), $this->getPlaceholder($value[0])));
      $this->bindings = array_merge($this->bindings, $value);
      $operator = ($operator === 'NOT IN') ? 'NOT IN' : 'IN';
      return "{$escapedField} {$operator} ({$placeholders})";
    } else {
      $placeholder = $this->getPlaceholder($value);
      $this->bindings[] = $value;
      return "{$escapedField} {$operator} {$placeholder}";
    }
  }

  /**
   * Determine if a field needs escaping.
   *
   * @param string $field Field name.
   * @return bool True if escaping is needed, false otherwise.
   */
  protected function needsEscaping($field)
  {
    // If the field contains SQL functions or parentheses, do not escape
    return !preg_match('/\b(AVG|COUNT|SUM|MIN|MAX)\b\s*\(/i', $field) && strpos($field, '(') === false;
  }

  /**
   * Escape field names to prevent SQL injection.
   *
   * @param string $field Field name.
   * @return string Escaped field name.
   */
  protected function escapeField($field)
  {
    if (!$this->needsEscaping($field)) {
      return $field;
    }
    if (strpos($field, '.') !== false) {
      $parts = explode('.', $field);
      return implode('.', array_map([$this, 'escapeIdentifier'], $parts));
    }
    return $this->escapeIdentifier($field);
  }

  /**
   * Escape an individual identifier (field or table name).
   *
   * @param string $identifier Identifier name.
   * @return string Escaped identifier.
   */
  protected function escapeIdentifier($identifier)
  {
    return '`' . str_replace('`', '``', $identifier) . '`';
  }

  /**
   * Get the appropriate placeholder for a value.
   *
   * @param mixed $value Value to get placeholder for.
   * @return string Placeholder string.
   */
  protected function getPlaceholder($value)
  {
    if (is_int($value)) {
      return '%d';
    } elseif (is_float($value)) {
      return '%f';
    } else {
      return '%s';
    }
  }

  /**
   * Escape table name with prefix.
   *
   * @param string $table Table name.
   * @return string Escaped table name.
   */
  protected function escapeTable($table)
  {
    if (strpos($table, $this->wpdb->prefix) === 0) {
      return $this->escapeField($table);
    }
    return $this->escapeField($this->wpdb->prefix . $table);
  }

  /**
   * Reset the query builder instance.
   *
   * @return $this
   */
  public function reset()
  {
    $this->select = '*';
    $this->from = '';
    $this->joins = '';
    $this->wheres = '';
    $this->groupBy = '';
    $this->having = '';
    $this->orderBy = '';
    $this->limit = '';
    $this->offset = '';
    $this->bindings = [];
    return $this;
  }
}