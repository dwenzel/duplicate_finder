<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "duplicate_finder".
 *
 * Auto generated 31-03-2015 15:51
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
	'version' => '0.1.0',
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
	'_md5_values_when_last_written' => 'a:19:{s:9:"ChangeLog";s:4:"cc01";s:17:"ext_localconf.php";s:4:"fcae";s:14:"ext_tables.php";s:4:"2d56";s:14:"ext_tables.sql";s:4:"461b";s:46:"Classes/Command/DuplicateCommandController.php";s:4:"a4bf";s:43:"Classes/Configuration/DuplicateRegistry.php";s:4:"027e";s:43:"Classes/Domain/Model/DuplicateInterface.php";s:4:"6797";s:32:"Classes/Hooks/FormEngineHook.php";s:4:"6894";s:33:"Classes/Hooks/IconUtilityHook.php";s:4:"abc1";s:42:"Classes/Service/DuplicateFinderService.php";s:4:"2199";s:53:"Classes/Service/Tca/DuplicateConfigurationService.php";s:4:"b352";s:36:"Classes/Utility/DuplicateUtility.php";s:4:"7468";s:37:"Classes/Utility/ReflectionUtility.php";s:4:"74b8";s:38:"Configuration/TypoScript/constants.txt";s:4:"f624";s:34:"Configuration/TypoScript/setup.txt";s:4:"8e1b";s:43:"Resources/Private/Language/de.locallang.xlf";s:4:"6086";s:40:"Resources/Private/Language/locallang.xlf";s:4:"958e";s:44:"Resources/Public/Icons/icon-is-duplicate.gif";s:4:"b351";s:52:"Resources/Public/Icons/icon-overlay-is-duplicate.gif";s:4:"b351";}',
);

