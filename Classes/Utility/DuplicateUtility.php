<?php
namespace CPSIT\DuplicateFinder\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class DuplicateUtility {

	/**
	 * Makes a table duplicate aware by adding value into the duplicate registry.
	 * FOR USE IN ext_localconf.php FILES or files in Configuration/TCA/Overrides/*.php Use the latter to benefit from TCA caching!
	 *
	 * @param string $extensionKey Extension key to be used
	 * @param string $tableName Name of the table to be made duplicate aware
	 * @param string $fieldName Name of the field to be used to store the duplicate status
	 * @param array $options Additional configuration options
	 */
	static public function makeDuplicateAware($extensionKey, $tableName, $fieldName = 'is_duplicate', array $options = array()) {
		// Update the duplicate registry
		$result = \CPSIT\DuplicateFinder\Configuration\DuplicateRegistry::getInstance()->add($extensionKey, $tableName, $fieldName, $options);
		if ($result === FALSE) {
			$message = '\CPSIT\DuplicateFinder\Configuration\DuplicateRegistry: not registered as duplicate aware for table "%s". Key was already registered.';
			/** @var $logger \TYPO3\CMS\Core\Log\Logger */
			$logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
			$logger->warning(
				sprintf($message, $tableName)
			);
		}
	}
}