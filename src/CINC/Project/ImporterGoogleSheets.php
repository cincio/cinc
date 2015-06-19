<?php

namespace CINC\Project;

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
use Symfony\Component\Yaml\Dumper;
use CINC\Project\Importer\GoogleSheets\ConfigType\ContentType;
use CINC\Project\Importer\GoogleSheets\ConfigType\Field;
use CINC\Project\Importer\GoogleSheets\ConfigType\FieldInstance;
use CINC\Project\Importer\GoogleSheets\ConfigType\FieldGroup;
use CINC\Project\Importer\GoogleSheets\ConfigType\ImageStyle;
use CINC\Project\Importer\GoogleSheets\ConfigType\Menu;
use CINC\Project\Importer\GoogleSheets\ConfigType\TaxonomyVocabulary;

/**
 * Provides Google Sheets import functionality for CINC projects.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class ImporterGoogleSheets {

  public $sheets = array();
  public $lists = array();
  public $configTypes = array();
  public $yamlDumper;

  public function import(\CINC\Project $project, $parameters) {

    $this->yamlDumper = new Dumper();

    $serviceRequest = new DefaultServiceRequest($parameters['oauthtoken']);
    ServiceRequestFactory::setInstance($serviceRequest);

    $spreadsheetService = new \Google\Spreadsheet\SpreadsheetService();
    $spreadsheet = $spreadsheetService->getSpreadsheetById($parameters['key']);

    $this->sheets = $this->spreadsheetToArray($spreadsheet);

    $this->configTypes[] = new ContentType();
    $this->configTypes[] = new Field();
    $this->configTypes[] = new FieldInstance();
    $this->configTypes[] = new FieldGroup();
    $this->configTypes[] = new ImageStyle();
    $this->configTypes[] = new Menu();
    $this->configTypes[] = new TaxonomyVocabulary();

    foreach ($this->configTypes as $configType) {
      $configType->sheetsToLists($this);
    }

    foreach ($this->configTypes as $configType) {
      $configType->listsToYaml($this, $project);
    }

  }

  /**
   * Converts entire $spreadsheet to nested array.
   */
  public function spreadsheetToArray($spreadsheet) {

    $sheets = array();
    $worksheets = $spreadsheet->getWorksheets();

    foreach ($worksheets as $worksheet) {

      $worksheetTitle = $worksheet->getTitle() . '';

      if (!isset($sheets[$worksheetTitle])) {
        $sheets[$worksheetTitle] = array();
      }

      $cells = $worksheet->getCellFeed();

      foreach ($cells->getEntries() as $cell) {

        $row = $cell->getRow() - 2;
        $column = $cell->getColumn() - 1;
        $value = $cell->getContent();

        if ($row >= 0) {
          if (!isset($sheets[$worksheetTitle][$row])) {
            $sheets[$worksheetTitle][$row] = array();
          }
          $sheets[$worksheetTitle][$row][$column] = $value;
        }

      }

    }

    return $sheets;
  }

  /**
   * Gets a worksheet matching $matchNames from $sheets.
   */
  public function worksheetFromSheets($sheets, $matchNames) {
    $key = FALSE;
    $worksheetNames = array_keys($sheets);

    foreach ($worksheetNames as $worksheetName) {
      if (in_array(strtolower($worksheetName), $matchNames)) {
        $key = $worksheetName;
      }
    }

    if (!$key) {
      return FALSE;
    }

    return $sheets[$key];
  }

}
