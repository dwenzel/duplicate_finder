<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
	$_EXTKEY,
	'Configuration/TypoScript',
	'Duplicate Finder'
);

// add priority for overlay
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'][] = 'isDuplicate';
// add name for overlay
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames']['isDuplicate'] = 'extensions-duplicate_finder-status-overlay-is-duplicate';
// add icon for overlay @todo replace icon (placeholder)
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
	array(
		'status-overlay-is-duplicate' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon-overlay-is-duplicate.gif',
		'icon-is-duplicate' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon-is-duplicate.gif',
	),
	$_EXTKEY
);
