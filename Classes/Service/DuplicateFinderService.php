<?php

namespace CPSIT\DuplicateFinder\Service;

use CPSIT\DuplicateFinder\Domain\Model\DuplicateInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use CPSIT\DuplicateFinder\Configuration\InvalidConfigurationException;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *	Duplicate finder service
 *
 * Find duplicate records by comparing their hashes
 * @author Dirk Wenzel dirk.wenzel@cps-it.de>
 * @package duplicate_finder
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
use \CPSIT\DuplicateFinder\Domain\Repository\CachedHashRepository;

class DuplicateFinderService implements SingletonInterface {
	const HASH_TABLE = 'tx_duplicatefinder_duplicate_hash';
	const HASH_MAX_LENGTH = 64;

	/**
	 * Configuration Manager
	 *
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Cached hash repository
	 *
	 * @var \CPSIT\DuplicateFinder\Domain\Repository\CachedHashRepository
	 * @inject
	 */
	protected $hashRepository;

	/**
	 * Duplicate tables
	 * add one for each database table
	 *
	 * @var array
	 */
	protected $duplicateTables = array();

	/**
	 * Database
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $database;

	/**
	 * Configuration
	 *
	 * @var \array $configuration
	 */
	protected $configuration;

	/**
	 * @var \array $queue
	 */
	protected $queue;

	public function __construct() {
		if (!$this->database instanceof \TYPO3\CMS\Core\Database\DatabaseConnection) {
			$this->database = $GLOBALS['TYPO3_DB'];
		}
			/** @var ObjectManager $objectManager */
			$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		if (!$this->hashRepository instanceof CachedHashRepository) {
			$this->hashRepository = $objectManager->get(
					'CPSIT\\DuplicateFinder\\Domain\\Repository\\CachedHashRepository'
					);
		}

		/**
		 * todo All this is only necessary if extbase is not initialized, for instance when calling
		 * DuplicateFinder via scheduler task. We should try and find a simpler way to read
		 * configuration.
		 */
		if(!$this->configurationManager instanceof ConfigurationManager) {
			$this->configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
		}
		if(empty($this->configuration)) {
			$typoScriptService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
			$fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
			$fullTypoScript= $typoScriptService->convertTypoScriptArrayToPlainArray($fullTypoScript);
			$this->setConfiguration(ArrayUtility::getValueByPath($fullTypoScript, 'module/tx_duplicatefinder/settings'));
		}
	}
	
	/**
	 * Gets the configuration
	 *
	 * @return \array | NULL
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Set configuration
	 * See TS module.tx_duplicatefinder.settings for a valid example
	 *
	 * @param \array $configuration An array containing a valid configuration.
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Build queue
	 *
	 * @param \string $tableName Name of table to fetch from
	 * @param \string $fieldNames Comma separated list of field names
	 * @param \integer $length Limit of query
	 */
	public function buildQueue ($tableName, $fieldNames, $length = 100) {
		if ($result = $this->database->exec_SELECTgetRows(
			'uid,' . $fieldNames,
			$tableName,
			'deleted=0 AND (duplicate_hash_id="" OR duplicate_hash_id=0)',
			'', 
			'uid', 
			(string)$length
		)) {
			$this->queue = $result;
		}
	}

	/**
	 * Gets a hash value over configured fields
	 * Please be aware that we limit the length of the
	 * hash string to 64 characters
	 *
	 * @param \array $record
	 * @return \string
	 */
	public function getHash($record) {
		$hash = hash(
				$this->getHashFunction(),
				$this->getHashFieldsContent($record)
		);
		return $this->cropHash($hash);
	}

	/**
	 * Gets a fuzzy hash value over configured fields
	 *
	 * @param \array $record
	 * @return \string
	 */
	public function getFuzzyHash($record) {
		return call_user_func(
				$this->getFuzzyHashFunction(), 
				$this->getHashFieldsContent($record)
			);
	}

	/**
	 * Gets the content of the hash fields
	 *
	 * @param array $record
	 * @return \string
	 */
	protected function getHashFieldsContent($record) {
		$input = '';
		if ((bool)$record) {
			foreach($record as $key=>$value) {
				$input .= (string)$value;
			}
		}
		return $input;
	}

	/**
	 * Gets a list of database fields which should be included
	 * when building a hash
	 *
	 * @param $tableName
	 * @throws \CPSIT\DuplicateFinder\Configuration\InvalidConfigurationException
	 * @return \string
	 */
	public function getDuplicateHashFields($tableName) {
		if (isset($this->configuration['tables'][$tableName]['hashFields'])) {
			return ArrayUtility::getValueByPath(
				$this->configuration,
				'tables/' . $tableName . '/hashFields'
			);
		} else {
			throw new InvalidConfigurationException('Hash fields for table ' . $tableName . ' are not configured', 1427630639);
		}
	}

	/**
	 * Returns whether a given hash is a duplicate
	 *
	 * @param \string $hash
	 * @param \string $tableName
	 * @return \boolean
	 */
	public function isDuplicate($hash, $tableName) {
		return $this->hashRepository->contains($hash, $tableName);
	}

	/**
	 * Sets the is_duplicate to 1 for a given record
	 * @param \string $tableName
	 * @param \integer $uid
	 * @return bool|\mysqli_result|object
	 */
	public function setIsDuplicate($tableName, $uid) {
		return $this->database->exec_UPDATEquery(
			$tableName,
			'uid=' . $uid,
			array(
				'is_duplicate' => 1
			)
		);
	}

	/**
	 * Returns possible duplicates of a record
	 *
	 * @param \string $hash
	 * @param \string $table optional table name
	 * @return \array
	 */
	public function getDuplicates($hash, $table = NULL, $fieldNames = 'uid') {
		$andWhere = '';
		if($table) {
			$andWhere = ' AND WHERE foreign_table=' . $table;
		}
		$result = $this->database->exec_SELECTgetRows(
			$fieldNames,
			self::HASH_TABLE,
			'hash = "' . $hash . '"' . $andWhere
		);
		return $result;
	}

	/**
	 * Get configured hash function
	 *
	 * @return \string
	 */
	public function getHashFunction(){
		return ArrayUtility::getValueByPath(
			$this->configuration,
			'hash/function'
		);
	}

	/**
	 * Get configured fuzzy hash function
	 *
	 * @throws \CPSIT\DuplicateFinder\Configuration\InvalidConfigurationException
	 * @return \string
	 */
	public function getFuzzyHashFunction(){
		$fuzzyHashFunction = ArrayUtility::getValueByPath(
			$this->configuration,
			'fuzzyHash/function'
		);
		if (function_exists($fuzzyHashFunction)) {
			return $fuzzyHashFunction;
		} else {
			throw new InvalidConfigurationException(
					'The configured fuzzy hash function ' . $fuzzyHashFunction . ' does not exist',
					1427637255
					);
		}
	}

	/**
	 * Find Duplicates for a given table
	 *
	 * @param \string $tableName Table name
	 * @param \int $queueLength How many record should be processed at once.
	 * @return void
	 */
	public function find($tableName, $queueLength = 100) {
		$fieldNames = $this->getDuplicateHashFields($tableName);
		if (!empty($fieldNames)) {
			$this->buildQueue($tableName, $fieldNames, $queueLength);
		}
		if ((bool) $this->queue) {
			$doFuzzyHashing = $this->isFuzzyHashingEnabled();
			$this->addDuplicateTable($tableName);
			foreach($this->queue as $record) {
				$uid = $record['uid'];
				unset($record['uid']);
				$hash = $this->getHash($record);
				if ($doFuzzyHashing) {
					$fuzzyHash = $this->getFuzzyHash($record);
				}
				if ($this->hashRepository->contains($hash, $tableName)) {
					$this->addDuplicate($tableName, $uid);
				} else {
					$this->hashRepository->add($hash, $tableName, $uid);
				}
				if (!$this->hashRepository->containsHashForRecord($tableName, $uid)) {
					// @todo gather and update all hashes at once. Can we use exec_INSERTmultipleRows?
					$this->hashRepository->update(NULL, $tableName, $uid, $hash, $fuzzyHash);
				}
			}
			$this->persistDuplicates($tableName);
		}
	}

	/**
	 * Clear hash table
	 * Removes all entries in the hash table for a given foreign
	 * table and un-sets the is_duplicate and duplicate_hash_id fields.
	 * If no foreign_table is given all entries in the hash table will
	 * be removed
	 * in the related records
	 * @param \string $tableName
	 */
	public function clearAll($tableName = NULL) {
		$this->hashRepository->clear($tableName);
	}

	/**
	 * Adds a (transient) duplicate table if it does not exist
	 *
	 * @var \string $tableName
	 */
	public function addDuplicateTable($tableName) {
		if(!array_key_exists($tableName, $this->duplicateTables)) {
			$this->duplicateTables[$tableName] = array();
		}
	}

	/**
	 * Add duplicate to a duplicate table
	 * The table has to exist. Note: named hash tables are transient (kept in memory)
	 *
	 * @param \string $tableName
	 * @param \int $uid Record uid
	 */
	public function addDuplicate($tableName, $uid) {
		if(array_key_exists($tableName, $this->duplicateTables)) {
			$this->duplicateTables[$tableName][] = $uid;
		}
	}

	/**
	 * Tells if fuzzy hashing is enabled
	 * @return \boolean
	 */
	public function isFuzzyHashingEnabled() {
		if (isset($this->configuration['fuzzyHash']['enabled'])) {
			return ArrayUtility::getValueByPath(
						$this->configuration,
						'fuzzyHash/enabled');
		}
		return FALSE;
	}

	protected function persistDuplicates($tableName) {
		if (isset($this->duplicateTables[$tableName])) {
			$duplicates = $this->duplicateTables[$tableName];
			if ((bool) $duplicates) {
				$uidList = implode(',', array_unique($duplicates));
				$this->database->exec_UPDATEquery(
						$tableName,
						'uid IN (' . $uidList . ')',
						array(
							'is_duplicate' => 1
						)
					);
			}
		}
	}
	
	/**
	 * Crops a string to HASH_MAX_LENGTH
	 * @param \string $hash
	 * @return \string
	 */
	protected function cropHash($hash) {
		return substr($hash, 0, self::HASH_MAX_LENGTH);
	}
 }

