
<?php
namespace CPSIT\DuplicateFinder\Utility;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;

class ReflectionUtility {

	/**
	 * Determines the SQL table for a given object.
	 * If it is an Extbase objects, the framework configuration will be looked up.
	 * If not the table name is 'guessed' by transforming the class name from camel case
	 * to lower case with underscores
	 *
	 * @param Object $object
	 * @return \string
	 * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
	 */
	public static function getTableName($object) {
		$className = get_class($object);
		/** @var BackendConfigurationManager $configurationManager */
		$configurationManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');

		/**
		 * todo: this will probably not work when extbase is not initialized. See DuplicateFinderService __construct
		 */
		$frameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$table = ArrayUtility::getValueByPath(
			$frameworkConfiguration,
			'persistence/classes/' . $className . '/mapping/tableName');
		if(!empty($table)) {
			return $table;
		} else {
			return 'tx_' . GeneralUtility::camelCaseToLowerCaseUnderscored($className);
		}
	}
}
