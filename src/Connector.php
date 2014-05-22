<?php
/**
* Connector for the Geosearch module
*
* Connector GUID: b3652a2669aae6923e574cbba7430e1a
*
* @copyright 2002-2010 by papaya Software GmbH - All rights reserved.
* @link http://www.papaya-cms.com/
* @license papaya Commercial License (PCL)
*
* Redistribution of this script or derivated works is strongly prohibited!
* The Software is protected by copyright and other intellectual property
* laws and treaties. papaya owns the title, copyright, and other intellectual
* property rights in the Software. The Software is licensed, not sold.
*
* @tutorial Geosearch/Geosearch.pkg
* @tutorial Geosearch/GeosearchConnector.cls
* @package Papaya-Modules
* @subpackage Geosearch
* @version $Id: Connector.php 2 2013-12-09 14:13:06Z weinert $
*/

/**
* Load mandatory libraries
*/
require_once(PAPAYA_INCLUDE_PATH.'system/base_connector.php');

/**
* Connector to acces data from the geosearch module
*
* @package papaya-modules
* @subpackage Geoserach
*/
class GeosearchConnector extends base_connector {

  /**
  * Configuration object
  * @var PapayaConfiguration
  */
  private $_configuration = NULL;

  /***************************************************************************/
  /** Methods                                                                */
  /***************************************************************************/

  /**
  * Find the given searchstring in a defined set of database fields
  *
  * @param string $searchString
  * @param number $limit , default = 10
  * @return array
  */
  public function getCityListFulltextSearch($searchString, $limit = 10) {
    return $this->getDatabaseAccessObject()->compactSearch($searchString, $limit);
  }

  /**
  * Find the corresponding cities by given zipcode
  *
  * @param string $zipcode
  * @param number $limit , default = 10
  * @return array
  */
  public function getCityListByZipcode($zipcode, $limit = 10) {
    return $this->getDatabaseAccessObject()->compactSearch($zipcode, $limit);
  }


  /***************************************************************************/
  /** Helper / instances                                                     */
  /***************************************************************************/

  /**
  * Set configuration object
  *
  * @param PapayaConfiguration $configuration
  */
  public function setConfiguration($configuration) {
    $this->_configuration = $configuration;
  }

  /**
  * Get an instance of the database access class
  * @return GeosearchDatabaseAccess
  */
  public function getDatabaseAccessObject() {
    if (
         !(
           isset($this->_databaseAccessObject) &&
           is_object($this->_databaseAccessObject)
         )
       ) {
      include_once(dirname(__FILE__).'/Database/Access.php');
      $this->_databaseAccessObject = new GeosearchDatabaseAccess();
      $this->_databaseAccessObject->setConfiguration($this->_configuration);
    }
    return $this->_databaseAccessObject;
  }

  /**
  * Set the PapayaQuestionnaireAnswerDatabaseAccess object to use
  *
  * @param GeosearchDatabaseAccess $databaseAccessObject
  */
  public function setDatabaseAccessObject($databaseAccessObject) {
    $this->_databaseAccessObject = $databaseAccessObject;
  }

}