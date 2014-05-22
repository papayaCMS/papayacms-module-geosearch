<?php
require_once(dirname(__FILE__).'/bootstrap.php');

require_once(dirname(__FILE__).'/../src/Connector.php');

class GeosearchConnectorTest extends PapayaTestCase {

  /**
  * get connector object
  */
  public function getConnectorObject() {
    $object = new GeosearchConnector($this);
    $object->setConfiguration(
      $this->getMockConfigurationObject(array('PAPAYA_DB_TABLEPREFIX' => 'papaya'))
    );
    $object->setDatabaseAccessObject($this);
    return $object;
  }

  /**
  * @covers GeosearchConnector::setConfiguration
  */
  public function testSetConfiguration() {
    $object = new GeosearchConnector($this);
    $object->setConfiguration(
      $configuration = $this->mockPapaya()->options(
        array('PAPAYA_DB_TABLEPREFIX' => 'papaya')
      )
    );
    $this->assertAttributeSame($configuration, '_configuration', $object);
  }

  /**
  * @covers GeosearchConnector::getDatabaseAccessObject
  */
  public function testGetDatabaseAccessObject() {
    $object = $this->getConnectorObject();
    $dbObject = $object->getDatabaseAccessObject();
    $this->assertSame($this, $dbObject);
  }

  /**
  * @covers GeosearchConnector::getCityListFulltextSearch
  */
  public function testGetCityListFulltextSearch() {
    $object = $this->getConnectorObject();
    $this->compactSearchQuery = $searchString = 'test';
    $this->compactSearchLimit = 10;
    $this->compactSearchResult = $expected = array(
      array(
        'fullText' => '23936 Testorf-Steinfort, Landkreis Nordwestmecklenburg',
        'zip' => 23936,
        'city' => 'Testorf-Steinfort'
      )
    );
    $result = $object->getCityListFulltextSearch($searchString);
    $this->assertEquals($expected, $result);
  }

  /**
  * @covers GeosearchConnector::getCityListByZipcode
  */
  public function testGetCityListByZipcode() {
    $object = $this->getConnectorObject();
    $this->compactSearchQuery = $zipcode = '35415';
    $this->compactSearchLimit = 10;
    $this->compactSearchResult = $expected = array(
      '0' => array(
          'fullText' => '35415 Pohlheim, Landkreis Gießen',
          'zip' => '35415',
          'city' => 'Pohlheim',
        )
    );
    $result = $object->getCityListByZipcode($zipcode);
    $this->assertEquals($expected, $result);
  }

  /**
  * helper method to mimic db access object
  */
  public function compactSearch($query, $limit) {
    $this->assertEquals($this->compactSearchQuery, $query);
    $this->assertEquals($this->compactSearchLimit, $limit);
    return $this->compactSearchResult;
  }

}
