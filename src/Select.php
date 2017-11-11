<?php
/**
 * Created by PhpStorm.
 * User: debisschop
 * Date: 11/10/17
 * Time: 10:29 AM
 */

namespace Drupal\field_statistics;

use Drupal\Core\Database\Query\Select as CoreSelect;

class Select extends CoreSelect {

  /**
   * Create an aggreggate query.
   *
   * @param string $aggregator
   *   Type of aggregation (e.g., min, max).
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SQL statement.
   */
  public function aggregateQuery(string $aggregator) {
    $count = $this->prepareAggregateQuery();

    $query = $this->connection->select($count, NULL, $this->queryOptions);

    switch (strtolower($aggregator)) {
      case 'max':
        $query->addExpression('MAX(*)');
        break;

      case 'min':
        $query->addExpression('MIN(*)');
        break;

      case 'avg':
        $query->addExpression('AVG(*)');
        break;

    }

    return $query;
  }

  /**
   * Prepares a count query from the current query object.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   A new query object ready to have COUNT(*) performed on it.
   */
  protected function prepareAggregateQuery() {
    // Create our new query object that we will mutate into a count query.
    $count = clone $this;

    $group_by = $count->getGroupBy();
    $having = $count->havingConditions();

    if (!$count->distinct && !isset($having[0])) {
      // When not executing a distinct query, we can zero-out existing fields
      // and expressions that are not used by a GROUP BY or HAVING. Fields
      // listed in a GROUP BY or HAVING clause need to be present in the
      // query.
      $fields =& $count->getFields();
      foreach (array_keys($fields) as $field) {
        if (empty($group_by[$field])) {
          unset($fields[$field]);
        }
      }

      $expressions =& $count->getExpressions();
      foreach (array_keys($expressions) as $field) {
        if (empty($group_by[$field])) {
          unset($expressions[$field]);
        }
      }

      // Also remove 'all_fields' statements, which are expanded into
      // tablename.* when the query is executed.
      foreach ($count->tables as &$table) {
        unset($table['all_fields']);
      }
    }

    // If we've just removed all fields from the query, make sure there is at
    // least one so that the query still runs.
    $count->addExpression('1');

    // Ordering a count query is a waste of cycles, and breaks on some
    // databases anyway.
    $orders = &$count->getOrderBy();
    $orders = [];

    if ($count->distinct && !empty($group_by)) {
      // If the query is distinct and contains a GROUP BY, we need to remove
      // the distinct because SQL99 does not support counting on distinct
      // multiple fields.
      $count->distinct = FALSE;
    }

    // If there are any dependent queries to UNION, prepare each of those for
    // the count query also.
    foreach ($count->union as &$union) {
      /** @var self $query */
      $query = $union['query'];
      $union['query'] = $query->prepareAggregateQuery();
    }

    return $count;
  }

}