<?php
namespace CPSIT\DuplicateFinder\Domain\Repository;

use TYPO3\CMS\Core\SingletonInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
 * Hash repository
 *
 * Stores into and retrieves hashes from database
 * @author Dirk Wenzel dirk.wenzel@cps-it.de>
 * @package duplicate_finder
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class HashRepository implements SingletonInterface {
	const HASH_TABLE = 'tx_duplicatefinder_duplicate_hash';

	/**
	 * Database
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $database;

	public function __construct() {
		if(!$this->database instanceof \TYPO3\CMS\Core\Database\DatabaseConnection) {
			$this->database = $GLOBALS['TYPO3_DB'];
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
	public function containsHashForRecord($tableName, $uid) {
		$result = $this->database->exec_SELECTcountRows(
			'foreign_uid',
			self::HASH_TABLE,
			'foreign_uid="' . $uid . '" AND foreign_table="' . $tableName . '"');
		if ($result > 0){
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Tells whether a hash is in data base
	 * @param \string $hash
	 * @param \string $tableName
	 * @return \boolean
	 */
	public function contains($hash, $tableName) {
		if($this->database->exec_SELECTcountRows(
				'hash',
				self::HASH_TABLE,
				'hash="' . $hash . '" AND foreign_table="' . $tableName . '"'
		)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Update hash in hash table
	 * If an object is given its hash will be computed and stored in the database.
	 *
	 * @param \string $tableName
	 * @param \integer $uid
	 * @param \string $hash
	 * @param \string $fuzzyHash
	 * return void
	 */
	public function update($tableName, $uid, $hash, $fuzzyHash = NULL) {
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
				// update record by table and uid
				$this->database->exec_UPDATEquery(
					$tableName,
					'uid=' . $uid,
					array(
						'duplicate_hash_id' => $duplicateHashId
					)
				);
			$this->database->sql_free_result($result);
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
	public function clear($tableName = NULL) {
		if ($tableName) {
			$this->removeByTable($tableName);
		} else {
			$tableNames = $this->getTableNames();
			foreach($tableNames as $table) {
				$this->removeByTable($table);
			}
		}
	}

	/**
	 * Gets an array with table names for which entries
	 * are in the hash table
	 * @return \array
	 */
	public function getTableNames() {
		$tableNames = array();
		$tables = $this->database->exec_SELECTgetRows(
				'DISTINCT foreign_table',
				self::HASH_TABLE,
				'foreign_table!=""'
			);
		if ((bool) $tables) {
			foreach ($tables as $table) {
				$tableNames[] = $table['foreign_table'];
			}
		}
		return $tableNames;
	}

	/**
	 * Clears hashes for a table
	 * Removes all entries in the hash table for a given foreign
	 * table and un-sets the is_duplicate and duplicate_hash_id fields
	 * for all records of this table
	 *
	 * @param $tableName
	 */
	public function removeByTable($tableName) {
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
 }

