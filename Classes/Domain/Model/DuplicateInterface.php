<?php

namespace CPSIT\DuplicateFinder\Domain\Model;

interface DuplicateInterface {

	/**
	 * Get uid
	 *
	 * @return \integer
	 */
	public function getUid();

	/**
	 * Get is duplicate
	 *
	 * @return bool
	 */
	public function getIsDuplicate();

	/**
	 * Set is duplicate
	 *
	 * @param \bool $isDuplicate
	 */
	public function setIsDuplicate($isDuplicate);

	/**
	 * Get duplicate hash
	 *
	 * @return \int
	 */
	public function getDuplicateHashId();

	/**
	 * Set duplicate hash id
	 *
	 * @param \int $duplicateHashId
	 */
	public function setDuplicateHashId($duplicateHashId);
}