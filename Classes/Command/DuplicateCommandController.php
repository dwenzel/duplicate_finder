<?php
namespace CPSIT\DuplicateFinder\Command;

use CPSIT\DuplicateFinder\Service\DuplicateFinderService;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class DuplicateCommandController extends CommandController {
	/**
	 * @var \CPSIT\DuplicateFinder\Service\DuplicateFinderService $duplicateFinderService
	 */
	protected $duplicateFinderService;

	public function injectDuplicateFinderService(DuplicateFinderService $duplicateFinderService) {
		$this->duplicateFinderService = $duplicateFinderService;
	}

	/**
	 * Find Duplicates for a given table
	 *
	 * @param \string $tableName table name
	 * @param \int $queueLength Queue length: How many records should be processed per run.
	 */
	public function findCommand ($tableName, $queueLength = 100) {
		$this->duplicateFinderService->find($tableName, $queueLength);
	}

	/**
	 * Clear hash table for.
	 *
	 * Removes all entries in the hash table for a
	 * given foreign table and un-sets the isDuplicate and
	 * duplicateHashId properties of the related records.
	 *
	 * @param \string $tableName Name of the table
	 */
	public function clearTableCommand($tableName) {
		$this->duplicateFinderService->clearAll($tableName);
	}
}
