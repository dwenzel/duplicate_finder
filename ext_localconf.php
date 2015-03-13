<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'CPSIT\\DuplicateFinder\\Command\\DuplicateCommandController';

// add a hook for icon rendering
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay']['wis_import_courses'] = 'CPSIT\\DuplicateFinder\\Hooks\\IconUtilityHook';
// add a hook for single field rendering
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']['wis_import_courses'] = 'CPSIT\\DuplicateFinder\\Hooks\\FormEngineHook';
