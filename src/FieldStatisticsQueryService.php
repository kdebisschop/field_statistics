<?php
/**
 * Created by PhpStorm.
 * User: debisschop
 * Date: 11/9/17
 * Time: 11:55 PM
 */

namespace Drupal\field_statistics;

use Drupal\Core\Database\Connection;

/**
 * Provides methods to query summary statistics about fields.
 */
class FieldStatisticsQueryService {

  /**
   * Doupal instance primary database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * FieldStatisticsQueryService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal instance primary database.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Build an aggregation query.
   *
   * @param string $field
   *   The desired type of aggregation.
   *
   * @return \Drupal\field_statistics\Select
   *   A select statement.
   */
  public function getMaxValue($field) {
    $statement = new Select("node__field_$field", 'f', $this->database);
    $statement->aggregateQuery('max');
    return $statement;
  }

}