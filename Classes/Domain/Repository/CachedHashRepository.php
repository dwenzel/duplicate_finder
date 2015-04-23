<?php
namespace CPSIT\DuplicateFinder\Domain\Repository;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
 * Cached Hash Repository
 *
 * Caches hash entries for hash repository. 
 * @author Dirk Wenzel dirk.wenzel@cps-it.de>
 * @package duplicate_finder
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
use \TYPO3\CMS\Core\Utility\GeneralUtility;

class CachedHashRepository implements SingletonInterface {
	/**
	 * Hash repository
	 *
	 * @var HashRepository
	 */
	protected $hashRepository;

	/**
	 * Hash tables
	 * add one for each database table
	 *
	 * @var \array
	 */
	protected $hashTables = array();

	/**
	 * Index tables
	 * one for each database table
	 * @var \array
	 */
	protected $indexTables = array();

	public function __construct() {
		if(!$this->hashRepository instanceof HashRepository) {
			/** @var ObjectManager $objectManager */
			$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			$this->hashRepository = $objectManager->get('CPSIT\\DuplicateFinder\\Domain\\Repository\\HashRepository');
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
		if ($this->hasIndexTable($tableName)) {
				if(array_key_exists($uid, $this->indexTables[$tableName])) {
					return TRUE;
				}
		}
		return $this->hashRepository->containsHashForRecord($tableName, $uid);
	}

	/**
	 * Tells if a hash is in hash table. Looks up the cache first and if not found there
	 * the persistent hash repository
	 *
	 * @param \string $hash
	 * @param \string $tableName
	 * @return \boolean
	 */
	public function contains($hash, $tableName) {
		if($this->hasHashTable($tableName)) {
			if (array_key_exists($hash, $this->hashTables[$tableName])) {
				return TRUE;
			}
		}
		return $this->hashRepository->contains($hash, $tableName);
	}

	/**
	 * Update hash in hash table
	 * If an object is given its hash will be computed and stored in the database.
	 *
	 * @param \string $tableName
	 * @param \integer $uid
	 * @param \string $hash
	 * @param \string $fuzzyHash
	 */
	public function update($tableName = NULL, $uid = NULL, $hash, $fuzzyHash = NULL) {
		// @todo Is it possible to cache this too?
		$this->hashRepository->update($tableName, $uid, $hash, $fuzzyHash);
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
			unset($this->hashTables[$tableName]);
			$this->hashRepository->clear($tableName);
		} else {
			unset($this->hashTables);
			$this->hashRepository->clear();
		}
	}

	/**
	 * Adds a (transient) hash table if it does not exist
	 *
	 * @var \string $tableName
	 */
	protected function addHashTable($tableName) {
		if(!$this->hasHashTable($tableName)) {
			$this->hashTables[$tableName] = array();
		}
	}

	/**
	 * Adds a (transient) index table if it does not exist
	 *
	 * @var \string $tableName
	 */
	protected function addIndexTable($tableName) {
		if(!$this->hasIndexTable($tableName)) {
			$this->indexTables[$tableName] = array();
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
	public function add($hash, $tableName, $uid = NULL) {
		if(!$this->hasHashTable($tableName)) {
			$this->addHashTable($tableName);
		}
		$this->hashTables[$tableName][$hash] = $uid;
		
		if($uid !== NULL) {
			if(!$this->hasIndexTable($tableName)) {
				$this->addIndexTable($tableName);
			}
			$this->indexTables[$tableName][$uid] = $hash;
		}
	}

	/**
	 * Tells if a hash table for a given table exists
	 *
	 * @param \string $tableName
	 * @return \boolean
	 */
	protected function hasHashTable($tableName) {
		return array_key_exists($tableName, $this->hashTables);
	}

	/**
	 * Tells if an index table for a given table exists
	 *
	 * @param \string $tableName
	 */
	protected function hasIndexTable($tableName) {
		return array_key_exists($tableName, $this->indexTables);
	}

	/**
	 * Get uid related to a given hash value
	 *
	 * @param \string $hash
	 * @param \string $tableName
	 * @return int|FALSE
	 */
	public function getUid($hash, $tableName) {
		if($this->hasHashTable($tableName) AND 
				array_key_exists($hash, $this->hashTables[$tableName])) {
			return $this->hashTables[$tableName][$hash];
		}
		return FALSE;
	}
 }

