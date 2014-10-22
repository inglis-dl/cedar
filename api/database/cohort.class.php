<?php
/**
 * cohort.class.php
 *
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar\database;
use cenozo\lib, cenozo\log, cedar\util;

/**
 * cohort: record
 */
class cohort extends \cenozo\database\cohort
{
  /**
   * Call parent method without restricting records by service.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @param boolean $id_only Whether to return a list of primary ids instead of active records
   * @param boolean $full Do not use, parameter ignored.
   * @access public
   * @static
   */
  public static function select(
    $modifier = NULL, $count = false, $distinct = true, $id_only = false, $full = false )
  {
    return parent::select( $modifier, $count, $distinct, $id_only, true );
  }

  /**
   * Override parent method so that records are not restricted by service.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the list or count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @param boolean $count If true then this method returns the count instead of list of records.
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @param boolean $id_only Whether to return a list of primary ids instead of active records
   * @return array( record ) | array( int ) | int
   * @access protected
   */
  public function get_record_list(
    $record_type,
    $modifier = NULL,
    $inverted = false,
    $count = false,
    $distinct = true,
    $id_only = false )
  {
    $grand_parent = get_parent_class( get_parent_class( get_class() ) );
    return $grand_parent::get_record_list(
      $record_type, $modifier, $inverted, $count, $distinct, $id_only );
  }
}
