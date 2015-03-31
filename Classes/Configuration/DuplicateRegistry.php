<?php
namespace CPSIT\DuplicateFinder\Configuration;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class DuplicateRegistry implements SingletonInterface {

	/**
	 * @var array
	 */
	protected $registry = array();

	/**
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * @var string
	 */
	protected $sqlTemplate = '';

	/**
	 * @var array
	 */
	protected $addedDataQualityTabs = array();

	/**
	 * Returns a class instance
	 *
	 * @return \CPSIT\DuplicateFinder\Configuration\DuplicateRegistry
	 */
	static public function getInstance() {
		return GeneralUtility::makeInstance(__CLASS__);
	}

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->sqlTemplate = str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL
			. '  %s tinyint(3) DEFAULT \'0\' NOT NULL,' . PHP_EOL
			. ' duplicate_hash_id int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL
			. ');' . str_repeat(PHP_EOL, 3);
	}

	/**
	 * Adds a new duplicate configuration to this registry.
	 * TCA changes are directly applied
	 *
	 * @param string $extensionKey Extension key to be used
	 * @param string $tableName Name of the table to be registered
	 * @param string $fieldName Name of the field to be registered
	 * @param array $options Additional configuration options
	 *              + fieldList: field configuration to be added to showitems
	 *              + typesList: list of types that shall visualize the duplicate field
	 *              + position: insert position of the duplicate field
	 *              + label: backend label of the duplicate field
	 *              + fieldConfiguration: TCA field config array to override defaults
	 * @return boolean
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function add($extensionKey, $tableName, $fieldName = 'is_duplicate', array $options = array()) {
		$didRegister = FALSE;
		if (empty($tableName) || !is_string($tableName)) {
			throw new \InvalidArgumentException('No or invalid table name "' . $tableName . '" given.', 1427275047
			);
		}
		if (empty($extensionKey) || !is_string($extensionKey)) {
			throw new \InvalidArgumentException('No or invalid extension key "' . $extensionKey . '" given.', 1427275072
			);
		}

		if (!$this->isRegistered($tableName, $fieldName)) {
			$this->registry[$tableName][$fieldName] = $options;
			$this->extensions[$extensionKey][$tableName][$fieldName] = $fieldName;

			if (!isset($GLOBALS['TCA'][$tableName]['columns']) && isset($GLOBALS['TCA'][$tableName]['ctrl']['dynamicConfigFile'])) {
				// Handle deprecated old style dynamic TCA column loading.
				$columnsConfigFile = $GLOBALS['TCA'][$tableName]['ctrl']['dynamicConfigFile'];
				if ($columnsConfigFile) {
					if (GeneralUtility::isAbsPath($columnsConfigFile)) {
						include($columnsConfigFile);
					} else {
						throw new \RuntimeException(
							'Dynamic config file for table ' . $tableName . ' not found',
							1427289509
						);
					}
				}
			}

			if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
				$this->applyTcaForTableAndField($tableName, $fieldName);
				$didRegister = TRUE;
			}
		}

		return $didRegister;
	}

	/**
	 * Gets all extension keys that registered a duplicate configuration.
	 *
	 * @return array
	 */
	public function getExtensionKeys() {
		return array_keys($this->extensions);
	}

	/**
	 * Gets all duplicate aware tables
	 *
	 * @return array
	 */
	public function getDuplicateAwareTables() {
		return array_keys($this->registry);
	}


	/**
	 * Tells whether a table has a duplicate configuration in the registry.
	 *
	 * @param string $tableName Name of the table to be looked up
	 * @param string $fieldName Name of the field to be looked up
	 * @return boolean
	 */
	public function isRegistered($tableName, $fieldName = 'is_duplicate') {
		return isset($this->registry[$tableName][$fieldName]);
	}

	/**
	 * Generates tables definitions for all registered tables.
	 *
	 * @return string
	 */
	public function getDatabaseTableDefinitions() {
		$sql = '';
		foreach ($this->getExtensionKeys() as $extensionKey) {
			$sql .= $this->getDatabaseTableDefinition($extensionKey);
		}

		return $sql;
	}

	/**
	 * Generates table definitions for registered tables by an extension.
	 *
	 * @param string $extensionKey Extension key to have the database definitions created for
	 * @return string
	 */
	public function getDatabaseTableDefinition($extensionKey) {
		if (!isset($this->extensions[$extensionKey]) || !is_array($this->extensions[$extensionKey])) {
			return '';
		}
		$sql = '';

		foreach ($this->extensions[$extensionKey] as $tableName => $fields) {
			foreach ($fields as $fieldName) {
				$sql .= sprintf($this->sqlTemplate, $tableName, $fieldName);
			}
		}

		return $sql;
	}

	/**
	 * Applies the additions directly to the TCA
	 *
	 * @param string $tableName
	 * @param string $fieldName
	 */
	protected function applyTcaForTableAndField($tableName, $fieldName) {
		$this->addTcaColumn($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
		$this->addToAllTcaTypes($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
		$this->addEnableColumn($tableName, $fieldName);
	}

	/**
	 * Adds the field to the enable columns
	 *
	 * @param $tableName
	 * @param $fieldName
	 */
	protected function addEnableColumn($tableName, $fieldName) {
		if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
			AND is_array($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])
			AND !array_key_exists($fieldName, $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'])) {
			$GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$fieldName] = $fieldName;
		}
	}

	/**
	 * Add a new TCA Column
	 *
	 * @param string $tableName Name of the table to be duplicate aware
	 * @param string $fieldName Name of the field to be used to store duplicate status
	 * @param array $options Additional configuration options
	 *              + fieldConfiguration: TCA field config array to override defaults
	 *              + label: backend label of the duplicate status field
	 * @return void
	 */
	protected function addTcaColumn($tableName, $fieldName, array $options) {
		// Makes sure to add more TCA to an existing structure
		if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
			// Take specific label into account
			$label = 'LLL:EXT:duplicate_finder/Resources/Private/Language/locallang.xlf:label.duplicateStatus';
			if (!empty($options['label'])) {
				$label = $options['label'];
			}

			// Take specific value of exclude flag into account
			$exclude = TRUE;
			if (isset($options['exclude'])) {
				$exclude = (bool) $options['exclude'];
			}

			$fieldConfiguration = empty($options['fieldConfiguration']) ? array() : $options['fieldConfiguration'];

			$columns = array(
				$fieldName => array(
					'exclude' => $exclude,
					'label' => $label,
					'config' => static::getTcaFieldConfiguration($tableName, $fieldName, $fieldConfiguration),
				),
			);

			// Adding fields to an existing table definition
			ExtensionManagementUtility::addTCAcolumns($tableName, $columns);
		}
	}

	/**
	 * Creates the 'fieldList' string for $fieldName which includes a data quality tab.
	 * But only one data quality tab is added per table.
	 *
	 * @param string $tableName
	 * @param string $fieldName
	 * @return string
	 */
	protected function addDataQualityTab($tableName, $fieldName) {
		$fieldList = '';
		if (!in_array($tableName, $this->addedDataQualityTabs)) {
			$fieldList .= '--div--;LLL:EXT:duplicate_finder/Resources/Private/Language/locallang.xlf:label.tab.dataQuality, ';
			$this->addedDataQualityTabs[] = $tableName;
		}
		$fieldList .= $fieldName . ',duplicates';
		return $fieldList;
	}

	/**
	 * Add a new field into the TCA types -> showitem
	 *
	 * @param string $tableName Name of the table to become duplicate aware
	 * @param string $fieldName Name of the field to be used to store the duplicate status
	 * @param array $options Additional configuration options
	 *              + fieldList: field configuration to be added to showitems
	 *              + typesList: list of types that shall visualize the duplicate status field
	 *              + position: insert position of the duplicate status field
	 * @return void
	 */
	protected function addToAllTcaTypes($tableName, $fieldName, array $options) {

		// Makes sure to add more TCA to an existing structure
		if (isset($GLOBALS['TCA'][$tableName]['columns'])) {

			if (empty($options['fieldList'])) {
				$fieldList = $this->addDataQualityTab($tableName, $fieldName);
			} else {
				$fieldList = $options['fieldList'];
			}

			$typesList = '';
			if (!empty($options['typesList'])) {
				$typesList = $options['typesList'];
			}

			$position = '';
			if (!empty($options['position'])) {
				$position = $options['position'];
			}
			// Makes the new duplicate status field visible.
			ExtensionManagementUtility::addToAllTCAtypes($tableName, $fieldList, $typesList, $position);
		}
	}

	/**
	 * Get the config array for given table and field.
	 * This method does NOT take care of adding sql fields nor adding the field to TCA types
	 * This has to be taken care of manually!
	 *
	 * @param string $tableName The table name
	 * @param string $fieldName The field name (default is_duplicate)
	 * @param array $fieldConfigurationOverride Changes to the default configuration
	 * @return array
	 * @api
	 */
	static public function getTcaFieldConfiguration($tableName, $fieldName = 'is_duplicate', array $fieldConfigurationOverride = array()) {
		// set up a new field, default name is 'is_duplicate'
		$fieldConfiguration = array(
			'type' => 'user',
			'userFunc' => 'CPSIT\DuplicateFinder\Service\Tca\DuplicateConfigurationService->getIsDuplicateField',
			'parameters' => array(
				'tableName' => $tableName,
				'fieldName' => $fieldName,
				'fieldConfigurationOverride' => $fieldConfigurationOverride,
			),
			'noTableWrapping' => 1,
		);

		// Merge changes to TCA configuration
		if (!empty($fieldConfigurationOverride)) {
			ArrayUtility::mergeRecursiveWithOverrule(
				$fieldConfiguration,
				$fieldConfigurationOverride
			);
		}

		return $fieldConfiguration;
	}

	/**
	 * A slot method to inject the required duplicate database fields to the
	 * tables definition string
	 *
	 * @param array $sqlString
	 * @return array
	 */
	public function addDuplicateDatabaseSchemaToTablesDefinition(array $sqlString) {
		$sqlString[] = $this->getDatabaseTableDefinitions();
		return array('sqlString' => $sqlString);
	}

	/**
	 * A slot method to inject the required duplicate database fields of an
	 * extension to the tables definition string
	 *
	 * @param array $sqlString
	 * @param string $extensionKey
	 * @return array
	 */
	public function addExtensionDuplicateDatabaseSchemaToTablesDefinition(array $sqlString, $extensionKey) {
		$sqlString[] = $this->getDatabaseTableDefinition($extensionKey);
		return array('sqlString' => $sqlString, 'extensionKey' => $extensionKey);
	}
}