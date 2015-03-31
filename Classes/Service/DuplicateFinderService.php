<?php

namespace CPSIT\DuplicateFinder\Service;

use CPSIT\DuplicateFinder\Domain\Model\DuplicateInterface;
use CPSIT\DuplicateFinder\Utility\ReflectionUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

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
 * Find duplicate courses by comparing their hashes
 * @author Dirk Wenzel dirk.wenzel@cps-it.de>
 * @package wis_import_courses
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
 
class DuplicateFinderService implements SingletonInterface {
	const HASH_TABLE = 'tx_duplicatefinder_duplicate_hash';
	const HASH_MAX_LENGTH = 64;

	/**
	 * Course repository
	 *
	 * @var \CPSIT\WisPascourse\Domain\Repository\CourseRepository
	 */
	protected $courseRepository;

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
	 * Set configuration
	 * See TS module.tx_duplicatefinder.settings.duplicateFinder for a valid example
	 *
	 * @param \array $configuration An array containing a valid configuration.
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Build queue
	 *
	 * @param \string $tableName
	 * @param \integer $length
	 */
	public function buildQueue ($tableName, $length = 100) {
		$res = $this->database->exec_SELECTquery(
			'uid',$tableName, 'deleted=0 AND (duplicate_hash_id="" OR duplicate_hash_id=0)', '', '', (string)$length
		);
		if($res) {
			$IDs = array();
			while($row = $this->database->sql_fetch_row($res)) {
				$IDs[] = $row[0];
			}
			$this->queue = $IDs;
		}
		$this->database->sql_free_result($res);
	}

	/**
	 * Gets a hash value over configured fields
	 * Please be aware that we limit the length of the
	 * hash string to 64 characters


*
*@param \CPSIT\DuplicateFinder\Domain\Model\DuplicateInterface|\array $object
	 * @return \string
	 */
	public function getHash($object) {
		$input = '';
		if(is_object($object)) {
			$fields = GeneralUtility::trimExplode(',', $this->getDuplicateHashFields($object), TRUE);
			foreach($fields as $field) {
				if(ObjectAccess::isPropertyGettable($object, $field)) {
					$input .= (string)ObjectAccess::getProperty($object, $field);
				}
			}
		} elseif (is_array($object)) {
			// todo get configuration from TS
			foreach($object as $key=>$value) {
				$input .= (string)$value;
			}
		}
		$hash = hash($this->getHashFunction(), $input);
		if (strlen($hash) > self::HASH_MAX_LENGTH) {
			$hash = substr($hash, 0, self::HASH_MAX_LENGTH);
		}
		return $hash;
	}

	/**
	 * @param $tableName
	 * @return mixed
	 */
	public function getDuplicateHashFields($tableName) {
		return ArrayUtility::getValueByPath(
			$this->configuration,
			'tables/' . $tableName . '/hashFields'
		);
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
		if(isset($this->hashTables[$tableName])) {
		 return array_key_exists($hash, $this->hashTables[$tableName]);
		} else {
			return $this->database->exec_SELECTcountRows(
				'hash',
				self::HASH_TABLE,
				'hash = "' . $hash . '" AND foreign_table = "' . $tableName . '"'
			);
		}
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
		$rows = array();
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
	 * return bool
	 */
	public function updateHash($object = NULL, $tableName = NULL, $uid = NULL, $hash) {
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
	 * Find Duplicates for a given table
	 *
	 * @param \string $tableName Table name
	 * @param \int $queueLength How many record should be processed at once.
	 * @return void
	 */
	public function find($tableName, $queueLength = 100) {
		$this->buildQueue($tableName, $queueLength);
		$fieldNames = $this->getDuplicateHashFields($tableName);
		foreach($this->queue as $uid) {
			$record = $this->database->exec_SELECTgetSingleRow(
				$fieldNames,
				$tableName,
				'uid=' . $uid . ' AND deleted=0'
			);
			if($record) {
				$hash = $this->getHash($record);
				if ($this->isDuplicate($hash, $tableName)) {
					$this->setIsDuplicate($tableName, $uid);
				}
				if (!$this->isRecordHashed($tableName, $uid)) {
					$this->updateHash(NULL, $tableName, $uid, $hash);
				}
			}
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
 }
