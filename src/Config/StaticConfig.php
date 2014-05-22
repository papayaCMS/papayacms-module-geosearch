<?php
/**
* geodata configuration class
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

class StaticConfig {

  /**
  * GEODB value group function
  *
  * @param STRING $variable - name of the variable to get
  * @param STRING|NULL $key - key name to get scalar value from a set, elsewhere ignored
  * @return MIXED - return the variable as is,
  * an once value from the set if $key given, or the set itself
  */
  public static function GEODB($variable, $key = NULL) {
    switch ($variable) {

    case 'charTranslations':
      static $charTranslations = array(
        "ä" => '(ä|ae|a)',
        "ö" => '(ö|oe|o)',
        "ü" => '(ü|ue|u)',
        "ß" => '(ß|ss)',
        "ae" => '(ä|ae)',
        "oe" => '(ö|oe)',
        "ue" => '(ü|ue)',
        "ss" => '(ß|ss|sz)',
        "sz" => '(ß|ss|sz)',
      );
      if (is_null($key)) {
        return $charTranslations;
      }
      if (isset($charTranslations[$key])) {
        return $charTranslations[$key];
      }
      trigger_error('wrong key "'.$key.'" on '.__LINE__, E_USER_ERROR);
      return FALSE;
      break;

    case 'types':
      static $types = array(
        "Land" => "1",
        "Bundesland" => "2",
        "Region" => "3",
        "Kreis" => "4",
        "Stadtkreis/kreisfrei" => "5",
        "Landkreis" => "6",
        "Stadt" => "7",
        "Stadtteil" => "8"
      );
      if (is_null($key)) {
        return $types;
      }
      if (isset($types[$key])) {
        return $types[$key];
      }
      trigger_error('wrong key "'.$key.'" on '.__LINE__, E_USER_ERROR);
      return FALSE;
      break;

    case 'cityTypes':
      return array("5", "7", "8");
      break;

    case 'regionTerms':
      return array("Region", "Kreis", "Landkreis");
      break;

    case 'countries':
      static $countries = array(
        "de" => "Deutschland",
        "ch" => "Schweiz"
      );
      if (is_null($key)) {
        return $countries;
      }
      if (isset($countries[$key])) {
        return $countries[$key];
      }
      trigger_error('wrong key "'.$key.'" on '.__LINE__, E_USER_ERROR);
      return FALSE;
      break;

    case 'states_de':
      static $states_de = array(
        "108" => "Brandenburg",
        "109" => "Berlin",
        "110" => "Baden-Württemberg",
        "111" => "Bayern",
        "112" => "Bremen",
        "113" => "Hessen",
        "114" => "Hamburg",
        "115" => "Mecklenburg-Vorpommern",
        "116" => "Niedersachsen",
        "117" => "Nordrhein-Westfalen",
        "118" => "Rheinland-Pfalz",
        "119" => "Schleswig-Holstein",
        "120" => "Saarland",
        "121" => "Sachsen",
        "122" => "Sachsen-Anhalt",
        "123" => "Thüringen"
      );
      if (is_null($key)) {
        return $states_de;
      }
      if (isset($states_de[$key])) {
        return $states_de[$key];
      }
      trigger_error('wrong key "'.$key.'" on '.__LINE__, E_USER_ERROR);
      return FALSE;
      break;

    case 'special_suburb_names':
      return array(
        "Altstadt",
        "Gartenstadt",
        "Neustadt",
        "Innenstadt",
        "Zentrum",
        "Mitte",
        "Südstadt",
        "Nordstadt",
        "Weststadt",
        "Oststadt",
        "West",
        "Süd",
        "Nord",
        "Ost",
        "Flughafen",
        "Hafen",
        "FWH"
      );
      break;

    default:
      trigger_error('wrong variable "'.$variable.'" on '.__LINE__, E_USER_ERROR);
      return FALSE;
    }
  }
}
?>