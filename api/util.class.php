<?php
/**
 * util.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace cedar;
use cenozo\lib;

/**
 * util: utility class of static methods
 *
 * Extends cenozo's util class with additional functionality.
 */
class util extends \cenozo\util
{
  /**
   * Attempts to convert a word into its plural form.
   * 
   * Warning: this method by no means returns the correct answer in every case.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $word
   * @return string
   * @static
   * @access public
   */
  public static function pluralize( $word )
  {
    return parent::pluralize( $word );
  }

  /**
   * Casts stdClass objects to arrays
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param  stdClass $array
   * @return array
   * @static
   * @access public
   */
  public static function array_cast_recursive( $array )
  {
    if( is_array( $array ) )
    {
      foreach( $array as $key => $value )
      {
        if( is_array( $value ) )
        {
          $array[$key] = self::array_cast_recursive( $value );
        }
        if( $value instanceof\stdClass )
        {
          $array[$key] = self::array_cast_recursive( (array)$value );
        }
      }
    }
    if( $array instanceof\stdClass )
    {
      return (array)$array;
    }
    return $array;
  }
}
