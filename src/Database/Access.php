<?php
/**
* geodata database access class
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
* @package Papaya-Modules
* @subpackage Geosearch
* @version $Id
*/

/**
* Load base db class
*/
require_once(PAPAYA_INCLUDE_PATH.'system/sys_base_db.php');
require_once(dirname(__FILE__).'/../Config/StaticConfig.php');

/**
* geodata database access class
*
* @package Papaya-Modules
* @subpackage Geosearch
*/
class GeosearchDatabaseAccess extends base_db {

  /**
  * Configuration object
  * @var PapayaConfiguration
  */
  private $_configuration = NULL;

  /**
  * Set configuration object
  *
  * @param PapayaConfiguration $configuration
  */
  public function setConfiguration($configuration) {
    $this->_configuration = $configuration;
    $this->setTableNames();
  }

  /**
  * Set database table names
  */
  public function setTableNames() {
    $tablePrefix = $this->_configuration->getOption('PAPAYA_DB_TABLEPREFIX');
    $this->_tableLocationsBase = $tablePrefix.'_geosearch_locations';
    $this->_tableZips = $tablePrefix.'_geosearch_zips';
  }

  /**
  * lightweight search function to use in autocompletion
  *
  * @param string $query - search query from user
  * @param number $limit - optional limit, default = 10
  * @param number $offset - optional offset for paging, default = 0
  */
  public function compactSearch($query, $limit = 50, $offset = 0) {

    $result = array();

    $rule = array();
    if (preg_match('/(\d+)/', $query, $var)) {
      if (substr((string)$var[1], 0, 1) === '0') {
        $length = 4;
      } else {
        $length = 5;
      }
      $zip = (int)$var[1];
      if (strlen($zip) > 5) {
        $zip = (int)substr($zip, 0, 5);
      }
      $rightLength = $length - strlen($zip);
      if ($rightLength < 0) {
        $rightLength = 0;
      }
      $placeholder = str_repeat('_', $rightLength);
      $zipQueryFlag = TRUE;
      $rule[] = sprintf("zip LIKE '%d%s'", $zip, $placeholder);
      $order = array('zip', 'parent.name', 'child.name');
      if ($rightLength > 0) {
        $group = array('zip');
      } else {
        $group = array('child.name', 'child.parent_loc_id');
      }
    } else {
      $order = array('child.zip_count DESC', 'child.name');
      $group = array('child.name', 'child.parent_loc_id');
    }

    if (preg_match('/[^\d\s]+.+/iu', $query, $var)) {
      $city = (string)($var[0]);
      $cityPrepared = $this->escapeStr($this->prepareName($city));
      $rule[] = sprintf(
        "(child.name RLIKE '^%s' OR parent.name RLIKE '^%1\$s')",
        $cityPrepared
      );
      array_unshift(
        $order,
        sprintf(
          "child.type_id, zip, child.name RLIKE '^%s\$' DESC, child.name RLIKE '^%1\$s' DESC",
          $cityPrepared
        )
      );
    }

    if (empty($rule)) {
      return $result;
    }

    $rule[] = $this->databaseGetSQLCondition(
      "child.type_id",
      StaticConfig::GEODB('cityTypes')
    );

    $sql = sprintf(
      "SELECT zip, child.name, child.type_id, parent.name, parent.type_id, zip.loc_id,
         child.latitude, child.longitude
         FROM %s AS child
         JOIN %1\$s AS parent ON (child.parent_loc_id = parent.loc_id)
         JOIN %s AS zip ON (zip.loc_id = child.loc_id)
        WHERE ",
      $this->_tableLocationsBase,
      $this->_tableZips
    );
    $sql .= implode(" AND ", $rule);
    $sql .= "
    GROUP BY ". implode(", ", $group);
    $sql .= "
    ORDER BY ". implode(", ", $order);
    $suburbTypeId = StaticConfig::GEODB('types', 'Stadtteil');
    $stateTypeId = StaticConfig::GEODB('types', 'Bundesland');

    $res = $this->databaseQuery($sql, $limit, $offset);

    if (! $res) {
      trigger_error("database error", E_USER_WARNING);
      return FALSE;
    }

    while ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ORDERED)) {
      list(
        $zip,
        $childName,
        $childType,
        $parentName,
        $parentType,
        $locationId,
        $latitude,
        $longitude
      ) = $row;
      $zipString = sprintf("%05d", $zip);
      $fullText = $zipString . " ";
      if (strpos($childName, $parentName) === FALSE && $parentType != $stateTypeId) {
        $fullText .= $this->expandChildName($childName, $parentName, $parentType);
      } else {
        $fullText .= $childName;
      }
      $row = array(
        'fullText' => $fullText,
        'zip' => $zipString,
        'id' => $locationId,
        'latitude' => $latitude,
        'longitude' => $longitude,
      );
      if ($childType == $suburbTypeId) {
        $row['city'] = $parentName;
        $row['suburb'] = $childName;
      } else {
        $row['city'] = $childName;
      }

      $result[] = $row;
    }

    return $result;

  }

  /**
  * expand child location name with parent name
  *
  * @param string $childName
  * @param string $parentName
  * @param string $parentType
  */
  public function expandChildName($childName, $parentName, $parentType) {

    $kreisIdList = array();
    foreach (StaticConfig::GEODB('regionTerms') as $title) {
      $kreisIdList[StaticConfig::GEODB('types', $title)] = $title;
    }

    $childName .= ", ";
    if (! preg_match('/kreis|region/i', $parentName) && isset($kreisIdList[$parentType])) {
      $childName .= $kreisIdList[$parentType]." ";
    }

    $childName .= $parentName;

    return $childName;
  }

  /**
  * prepare location name for a search - convert into an mysql regexp
  *
  * @param string $name
  */
  public function prepareName($name) {
    $translations = StaticConfig::GEODB('charTranslations');
    $name = preg_replace('/[^\wäöüß\(\)\|]+/iu', ".+", $name);
    $name = preg_quote($name);
    $name = strtr(papaya_strings::strtolower(trim($name)), $translations);
    return $name;
  }

}
