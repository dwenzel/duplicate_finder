<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "duplicate_finder".
 *
 * Auto generated 15-06-2015 16:27
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Duplicate Finder',
	'description' => 'Provides a service for finding and marking duplicates of any domain model or database table',
	'category' => 'service',
	'author' => 'Dirk Wenzel',
	'author_email' => 'dirk.wenzl@cps-it.de',
	'author_company' => 'CPS-IT',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '0.2.0',
	'constraints' => 
	array (
		'depends' => 
		array (
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
	'_md5_values_when_last_written' => 'a:27:{s:9:"ChangeLog";s:4:"ecd8";s:17:"ext_localconf.php";s:4:"fcae";s:14:"ext_tables.php";s:4:"2d56";s:14:"ext_tables.sql";s:4:"e6b8";s:46:"Classes/Command/DuplicateCommandController.php";s:4:"e2d7";s:43:"Classes/Configuration/DuplicateRegistry.php";s:4:"027e";s:55:"Classes/Configuration/InvalidConfigurationException.php";s:4:"c395";s:43:"Classes/Domain/Model/DuplicateInterface.php";s:4:"6797";s:50:"Classes/Domain/Repository/CachedHashRepository.php";s:4:"a6fb";s:44:"Classes/Domain/Repository/HashRepository.php";s:4:"0072";s:32:"Classes/Hooks/FormEngineHook.php";s:4:"6894";s:33:"Classes/Hooks/IconUtilityHook.php";s:4:"abc1";s:42:"Classes/Service/DuplicateFinderService.php";s:4:"32bb";s:53:"Classes/Service/Tca/DuplicateConfigurationService.php";s:4:"b352";s:36:"Classes/Utility/DuplicateUtility.php";s:4:"7468";s:37:"Classes/Utility/ReflectionUtility.php";s:4:"0887";s:38:"Configuration/TypoScript/constants.txt";s:4:"f624";s:34:"Configuration/TypoScript/setup.txt";s:4:"5a89";s:43:"Resources/Private/Language/de.locallang.xlf";s:4:"6086";s:40:"Resources/Private/Language/locallang.xlf";s:4:"958e";s:44:"Resources/Public/Icons/icon-is-duplicate.gif";s:4:"b351";s:52:"Resources/Public/Icons/icon-overlay-is-duplicate.gif";s:4:"b351";s:25:"Tests/Build/UnitTests.xml";s:4:"f1af";s:50:"Tests/Unit/Configuration/DuplicateRegistryTest.php";s:4:"0074";s:57:"Tests/Unit/Domain/Repository/CachedHashRepositoryTest.php";s:4:"2810";s:51:"Tests/Unit/Domain/Repository/HashRepositoryTest.php";s:4:"186d";s:49:"Tests/Unit/Service/DuplicateFinderServiceTest.php";s:4:"651f";}',
);

