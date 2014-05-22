<?php
require_once(dirname(__FILE__).'/../bootstrap.php');

require_once(dirname(__FILE__).'/../../src/Database/Access.php');

class GeodbDatabaseAccessTest extends PapayaTestCase {

  /**
  * @covers GeosearchDatabaseAccess::compactSearch
  * @dataProvider compactSearchProvider
  */
  public function testCompactSearch($query, $dbResult, $expected, $queryCheckList) {

    $object = new GeosearchDatabaseAccessProxy();
    $dbObject = $this->getMockConfigurationObject(
      array('PAPAYA_DB_TABLEPREFIX' => 'papaya')
    );
    $object->setConfiguration($dbObject);

    $object->setDbResultFixture($dbResult);

    $this->assertEquals($expected, $object->compactSearch($query));

    $sql = $object->getLastSqlQuery();
    //print $sql."\n";
    foreach ($queryCheckList as $test) {
      $this->assertRegExp($test, $sql);
    }
  }

  /**
  * provider for compact searches
  */
  public static function compactSearchProvider() {
    return array(
      /**
      * check conditions:
      * 1) input is a zip
      * 2) input zip is too long and should be properly truncated
      * 3) return has two different suburbs with a common zip
      */
      array(
        // query
        508230,
        // db result
        array(
          array('50823', 'Ehrenfeld', '8', 'Köln', '5', '123450', 1.992, 32.3421),
          array('50823', 'Neuehrenfeld', '8', 'Köln', '5', '123460', 33.12, 12.55),
        ),
        // expected function result
        array (
          '0' => array (
            'fullText' => '50823 Ehrenfeld, Köln',
            'zip' => '50823',
            'id' => '123450',
            'latitude' => 1.992,
            'longitude' => 32.3421,
            'city' => 'Köln',
            'suburb' => 'Ehrenfeld',
          ),
          '1' => array (
            'fullText' => '50823 Neuehrenfeld, Köln',
            'zip' => '50823',
            'id' => '123460',
            'latitude' => 33.12,
            'longitude' => 12.55,
            'city' => 'Köln',
            'suburb' => 'Neuehrenfeld',
          ),
        ),
        // query check list
        array(
          '/SELECT zip, child.name, child.type_id, parent.name, parent.type_id, zip.loc_id/',
          '/WHERE zip LIKE \'50823\'/',
          '/ORDER BY zip, parent.name, child.name/'
        )
      ),

      /**
      * check conditions:
      * 1) input is a uppercase text with a special german character - check proper
      *   mysql regexp generation to match a group of alternate characters
      * 2) db query returns a city with state as a parent - check proper stripping of
      *   state name in result
      * 3) result also has some suburbs with common or different zips
      */
      array(
        // query
        'KÖLN',
        // db result
        array(
          array('50667','Köln', '5', 'Nordrhein-Westfalen', '2', '123400', 12.12, 13.13),
          array('50667','Altstadt-Süd', '8', 'Köln', '5', '123440', 14.14, 15.15),
          array('50667','Altstadt-Nord', '8', 'Köln', '5', '123420', 16.16, 17.17),
          array('51107','Eil', '8', 'Köln', '5', '123409', 110.22, 122.22),
          array('50931','Lindenthal', '8', 'Köln', '5', '123499', 123.123, 125.156),
        ),
        // expected function result
        array (
          array (
            'fullText' => '50667 Köln',
            'zip' => '50667',
            'id' => '123400',
            'latitude' => 12.12,
            'longitude' => 13.13,
            'city' => 'Köln'
          ),
          array (
            'fullText' => '50667 Altstadt-Süd, Köln',
            'zip' => '50667',
            'id' => '123440',
            'latitude' => 14.14,
            'longitude' => 15.15,
            'city' => 'Köln',
            'suburb'  => 'Altstadt-Süd'
          ),
          array (
            'fullText' => '50667 Altstadt-Nord, Köln',
            'zip' => '50667',
            'id' => '123420',
            'latitude' => 16.16,
            'longitude' => 17.17,
            'city' => 'Köln',
            'suburb'  => 'Altstadt-Nord'
          ),
          array (
            'fullText' => '51107 Eil, Köln',
            'zip' => '51107',
            'id' => '123409',
            'latitude' => 110.22,
            'longitude' => 122.22,
            'city' => 'Köln',
            'suburb'  => 'Eil'
          ),
          array (
            'fullText' => '50931 Lindenthal, Köln',
            'zip' => '50931',
            'id' => '123499',
            'latitude' => 123.123,
            'longitude' => 125.156,
            'city' => 'Köln',
            'suburb'  => 'Lindenthal'
          ),
        ),
        // query check list
        array(
          '/SELECT zip, child.name, child.type_id, parent.name, parent.type_id, zip.loc_id/u',

          '/WHERE \(child.name RLIKE \'\^k\(ö\|oe\|o\)ln\' ' .
          'OR parent.name RLIKE \'\^k\(ö\|oe\|o\)ln\'\)/u',

          '/ORDER BY child.type_id, zip, child.name RLIKE \'\^k\(ö\|oe\|o\)ln\$\' DESC, '.
          'child.name RLIKE \'\^k\(ö\|oe\|o\)ln\' DESC\, child.zip_count DESC, child.name/u'
        )
      ),

      /**
      * check conditions:
      * 1) input is a simple lowercase city name - check the simple
      *   mysql regexp generation
      * 2) db query returns a city with region as a parent - check proper concatenation of
      *   region name in the result entry
      * 3) result also has some suburbs with different zips
      */
      array(
        // query
        'marburg',
        // db result
        array(
          array('35037','Marburg','7','Marburg-Biedenkopf','6', '1230', 1, 2),
          array('35043','Bauerbach','8','Marburg','7', '3230', 4, 6),
          array('35041','Dagobertshausen','8','Marburg','7', '2230', 7, 3),
        ),
        // expected function result
        array (
          array (
            'fullText' => '35037 Marburg, Landkreis Marburg-Biedenkopf',
            'zip' => '35037',
            'id' => '1230',
            'latitude' => 1,
            'longitude' => 2,
            'city' => 'Marburg',
          ),
          array (
            'fullText' => '35043 Bauerbach, Marburg',
            'zip' => '35043',
            'id' => '3230',
            'latitude' => 4,
            'longitude' => 6,
            'city' => 'Marburg',
            'suburb' => 'Bauerbach',
          ),
          array (
            'fullText' => '35041 Dagobertshausen, Marburg',
            'zip' => '35041',
            'id' => '2230',
            'latitude' => 7,
            'longitude' => 3,
            'city' => 'Marburg',
            'suburb' => 'Dagobertshausen',
          ),
        ),
        // query check list
        array(
          '/SELECT zip, child.name, child.type_id, parent.name, parent.type_id, zip.loc_id/',
          '/WHERE \(child.name RLIKE \'\^marburg\' OR parent.name RLIKE \'\^marburg\'\)/',
          '/ORDER BY child.type_id, zip, child.name RLIKE \'\^marburg\$\' DESC, '.
          'child.name RLIKE \'\^marburg\' DESC, child.zip_count DESC, child.name/'
        )
      ),

      /**
      * check conditions:
      * 1) input is a incomplete zip with '0' prefix - check proper mysql
      *   LIKE-placehoulder generation to search only in 4-digit zips
      * 2) db query returns a city with state as a parent - check proper stripping of
      *   state name in result
      * 3) return also has a suburb
      */
      array(
        // query
        '0106',
        // db result
        array(
          array('1067', 'Dresden', '5', 'Sachsen', '2', '200', 12.12, 13.13),
          array('1069', 'Wilsdruffer Vorstadt', '8', 'Dresden', '5', '500', 14.14, 15.15),
        ),
        // expected function result
        array (
          array (
            'fullText' => '01067 Dresden',
            'zip' => '01067',
            'id' => '200',
            'latitude' => 12.12,
            'longitude' => 13.13,
            'city' => 'Dresden',
          ),
          array (
            'fullText' => '01069 Wilsdruffer Vorstadt, Dresden',
            'zip' => '01069',
            'id' => '500',
            'latitude' => 14.14,
            'longitude' => 15.15,
            'city' => 'Dresden',
            'suburb' => 'Wilsdruffer Vorstadt',
          ),
        ),
        // query check list
        array(
          '/SELECT zip, child.name, child.type_id, parent.name, parent.type_id, zip.loc_id/',
          '/WHERE zip LIKE \'106_\'/',
          '/ORDER BY zip, parent.name, child.name/'
        )
      ),
    );
  }

}

/**
* a proxy class, to reimplement some DB funcitons for better query debugging
*/
class GeosearchDatabaseAccessProxy extends GeosearchDatabaseAccess {

  /**
  * very basic reimplementation of databaseGetSQLCondition
  */
  public function databaseGetSQLCondition($fieldName, $values) {
    $result = $fieldName;
    if (is_array($values)) {
      $valList = array();
      foreach ($values as $val) {
        $valList[] = "'".addslashes($val)."'";
      }
      $result .= " IN (".implode(",", $valList).")";
    } else {
      $result .= " = '".addslashes($values)."'";
    }
    return $result;
  }

  /**
  * simulate escaping
  */
  public function escapeStr($val) {
    return addslashes($val);
  }

  /**
  * query
  */
  public function databaseQuery($sql, $limit, $offset = 0) {
    if ($limit) {
      $sql .= sprintf(" LIMIT %d, %d", $offset, $limit);
    } elseif ($offset) {
      $sql .= sprintf(" LIMIT %d", $offset);
    }
    $this->_lastSqlQuery = $sql;
    return $this;
  }

  /**
  * get last query
  */
  public function getLastSqlQuery() {
    return $this->_lastSqlQuery;
  }

  /**
  * set db result fixture
  */
  public function setDbResultFixture($data) {
    $this->_dbResultFixture = $data;
  }

  /**
  * db result fixture helper
  */
  public function fetchRow() {
    return array_shift($this->_dbResultFixture);
  }
}
