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
	 * Hash tables
	 * add one for each database table
	 *
	 * @var array
	 */
	protected $hashTables = array();

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
		if(!$this->database instanceof \TYPO3\CMS\Core\Database\DatabaseConnection) {
			$this->database = $GLOBALS['TYPO3_DB'];
		}
		/**
		 * todo All this is only necessary if extbase is not initialized, for instance when calling
		 * DuplicateFinder via scheduler task. We should try and find a simpler way to read
		 * configuration.
		 */
		if(!$this->configurationManager instanceof ConfigurationManager) {
			/** @var ObjectManager $objectManager */
			$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
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
	 * @param \CPSIT\DuplicateFinder\Domain\Model\DuplicateInterface|\array $object
	 * @return \string
	 */
	public function getHash($object) {
		$hash = hash(
				$this->getHashFunction(),
				$this->getHashFieldsContent($object)
		);
		if (strlen($hash) > self::HASH_MAX_LENGTH) {
			$hash = substr($hash, 0, self::HASH_MAX_LENGTH);
		}
		return $hash;
	}

	/**
	 * Gets a fuzzy hash value over configured fields
	 *
	 * @param \CPSIT\DuplicateFinder\Domain\Model\DuplicateInterface|\array $object
	 * @return \string
	 */
	public function getFuzzyHash($object) {
		return call_user_func(
				$this->getFuzzyHashFunction(), 
				$this->getHashFieldsContent($object)
			);
	}

	/**
	 * Gets the content of the hash fields
	 *
	 * @param \CPSIT\DuplicateFinder\Domain\Model\DuplicateInterface|\array $object
	 * @return \string
	 */
	protected function getHashFieldsContent($object) {
		$input = '';
		if(is_object($object)) {
			$fields = GeneralUtility::trimExplode(',', $this->getDuplicateHashFields($object), TRUE);
			foreach($fields as $field) {
				if(ObjectAccess::isPropertyGettable($object, $field)) {
					$input .= (string)ObjectAccess::getProperty($object, $field);
				}
			}
		} elseif (is_array($object)) {
			foreach($object as $key=>$value) {
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
	 * Returns whether a given record is already hashed.
	 * I.e. is in hash table.
	 *
	 * @param \string $tableName
	 * @param \integer $uid
	 * @return \bool
	 */
	public function isRecordHashed($tableName, $uid) {
		$result = $this->database->exec_SELECTcountRows(
			'foreign_uid',
			self::HASH_TABLE,
			'foreign_uid="' . $uid . '" AND foreign_table="' . $tableName . '"');
		return ($result) ? TRUE : FALSE;
	}

	/**
	 * Tells whether an object is hashed.
	 * Its duplicateHash property must be set and it has to
	 * be found in the hash table
	 *
	 * @param DuplicateInterface $object
	 * @return \boolean
	 */
	public function isObjectHashed($object) {
		if (!$object->getDuplicateHashId()) {
			$uid = $object->getUid();
			$tableName = ReflectionUtility::getTableName($object);
			return $this->isRecordHashed($tableName, $uid);
		}
		return FALSE;
	}

	/**
	 * Returns whether a given hash is a duplicate
	 *
	 * @param \string $hash
	 * @param \string $tableName
	 * @return \boolean
	 */
	public function isDuplicate($hash, $tableName) {
		$isDuplicate = FALSE;
		if(isset($this->hashTables[$tableName])) {
			// lookup transient table
			if (!$isDuplicate = array_key_exists($hash, $this->hashTables[$tableName])) {
				// lookup database table
				$isDuplicate = $this->isHashInDataBase($hash, $tableName);
			}
		} else {
			$isDuplicate = $this->isHashInDataBase($hash, $tableName);
		}
		return $isDuplicate;
	}

	protected function isHashInDataBase($hash, $tableName) {
		return $this->database->exec_SELECTcountRows(
				'hash',
				self::HASH_TABLE,
				'hash = "' . $hash . '" AND foreign_table = "' . $tableName . '"'
		);
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
	 * Update hash in hash table
	 * If an object is given its hash will be computed and stored in the database.
	 *
	 * @param \string $tableName
	 * @param \integer $uid
	 * @param DuplicateInterface $object
	 * @param \string $hash
	 * @param \string $fuzzyHash
	 * return bool
	 */
	public function updateHash($object = NULL, $tableName = NULL, $uid = NULL, $hash, $fuzzyHash = NULL) {
		if($object AND $tableName = ReflectionUtility::getTableName($object)) {
			$uid = $object->getUid();
			$object->setIsDuplicate(FALSE);
			$hash = $this->getHash($object);
		}
		if ($tableName AND $uid) {
			//remove existing entries
			$this->database->exec_DELETEquery(
				self::HASH_TABLE,
				'foreign_uid = "' . $uid . '" AND foreign_table="' . $tableName . '"'
			);

			$fieldValues = array(
				'foreign_uid' => $uid,
				'foreign_table' => $tableName,
				'hash' => $hash,
				'tstamp' => time()
			);

			if ($fuzzyHash) {
				$fieldValues['fuzzy_hash'] = $fuzzyHash;
			}

			$result = $this->database->exec_INSERTquery(self::HASH_TABLE, $fieldValues);
			if($result) {
				$duplicateHashId = $this->database->sql_insert_id();
				if($object) {
					// update object @todo do we have to persist manually?
					$object->setDuplicateHashId($duplicateHashId);
				} else{
					// update record by table and uid
					$this->database->exec_UPDATEquery(
						$tableName,
						'uid=' . $uid,
						array(
							'duplicate_hash_id' => $duplicateHashId
						)
					);
				}
				$this->database->sql_free_result($result);
			}
		}
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
			$this->addHashTable($tableName);
			$this->addDuplicateTable($tableName);
			foreach($this->queue as $record) {
				$uid = $record['uid'];
				unset($record['uid']);
				$hash = $this->getHash($record);
				if ($doFuzzyHashing) {
					$fuzzyHash = $this->getFuzzyHash($record);
				}
				if ($this->isDuplicate($hash, $tableName)) {
					$this->addDuplicate($tableName, $uid);
				} else {
					$this->addHash($hash, $tableName, $uid);
				}
				if (!$this->isRecordHashed($tableName, $uid)) {
					// @todo gather and update all hashes at once. Can we use exec_INSERTmultipleRows?
					$this->updateHash(NULL, $tableName, $uid, $hash, $fuzzyHash);
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
		if ($tableName) {
			$this->clearByTable($tableName);
		} else {
			$tableNames = $this->database->exec_SELECTgetRows(
				'DISTINCT foreign_table',
				self::HASH_TABLE,
				'foreign_table!=""'
			);
			foreach($tableNames as $table) {
				$this->clearByTable($table['foreign_table']);
			}
		}
	}

	/**
	 * Clears hashes for a table
	 * Removes all entries in the hash table for a given foreign
	 * table and un-sets the is_duplicate and duplicate_hash_id fields
	 * in for all records of this table
	 *
	 * @param $tableName
	 */
	protected function clearByTable($tableName) {
		$this->database->exec_UPDATEquery(
			$tableName,
			'',
			array(
				'is_duplicate' => 0,
				'duplicate_hash_id' => 0
			)
		);
		$this->database->exec_DELETEquery(
			self::HASH_TABLE,
			'foreign_table="' . $tableName . '"'
		);
	}

	/**
	 * Adds a (transient) hash table if it does not exist
	 *
	 * @var \string $tableName
	 */
	public function addHashTable($tableName) {
		if(!array_key_exists($tableName, $this->hashTables)) {
			$this->hashTables[$tableName] = array();
		}
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
	 * Add hash to a hash table
	 * The table has to exist. Note: named hash tables are transient (kept in memory)
	 *
	 * @param \string $hash
	 * @param \string $tableName
	 * @param \int $uid optional uid
	 */
	public function addHash($hash, $tableName, $uid = NULL) {
		if(array_key_exists($tableName, $this->hashTables)) {
			$this->hashTables[$tableName][$hash] = $uid;
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
	 * Get uid related to a given hash value
	 *
	 * @param \string $hash
	 * @param \string $tableName
	 * @return int|FALSE
	 */
	public function getUid($hash, $tableName) {
		if(isset($this->hashTables[$tableName]) AND
		array_key_exists($hash, $this->hashTables[$tableName])) {
			return $this->hashTables[$tableName][$hash];
		}
		return FALSE;
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
 }

