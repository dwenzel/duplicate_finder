<?php
namespace CPSIT\DuplicateFinder\Service\Tca;
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Class DuplicateConfigurationService
 * Provides TCA configuration for records implementing the DuplicateInterface
 * (Cps\WisLib\Domain\Model\DuplicateInterface)
 *
 * @author Dirk Wenzel <dirk.wenzel@cps-it.de>
 * @package CPSIT\WisImportCourses\Service\Tca
 */
class DuplicateConfigurationService {
	/**
	 * @var string
	 */
	protected static $languageFile = 'LLL:EXT:duplicate_finder/Resources/Private/Language/locallang.xlf:';

	/**
	 * @var string
	 */
	protected static $fieldTemplateIsDuplicate = '<strong>###FIELD_NAME###</strong><br />
					<p>###FIELD_INTRO###<br />
					<span class="status ###FIELD_STATUSCLASS###">###FIELD_STATUS###</span> ###FIELD_DUPLICATEMESSAGE###</p>
					###FIELD_RECORDS###';

	/**
	 * @var array
	 */
	protected $templateMarker = array('NAME', 'STATUSCLASS', 'STATUS', 'DUPLICATEMESSAGE', 'RECORDS');

	/**
	 * @var string
	 */
	protected static $fieldWrap = '<tr class="class-main1"><td colspan="2" class="formField-field" valign="top">
				<span class="t3-form-field-container"><div class="t3-form-field-item tx-duplicate-finder">|</div></span></td></tr>';

	/**
	 * @var string
	 */
	protected static $duplicateListEntryTemplate = '<li>###FIELD_EDITLINK###<span class="title">###FIELD_TITLE###</span></li>';

	/**
	 * @var string
	 */
	protected static $originalRecordTemplate = '<div>###FIELD_SHOWLINK###<span class="title">###FIELD_TITLE###</span>';

	/**
	 * @var string
	 */
	protected static $editLinkTemplate = '<a class="btn edit"
				href="alt_doc.php?edit[###FIELD_TABLE###][###FIELD_UID###]=edit&returnUrl=###FIELD_RETURNURL###"
				title="###FIELD_TITLE###">###FIELD_ICON###</a>';


	/**
	 * @var string
	 */
	protected static $showLinkTemplate = '<a class="btn show"
				href="#"
				onclick="top.launchView(\'###FIELD_TABLE###\', \'###FIELD_UID###\'); return false;"
				title="###FIELD_TITLE###">###FIELD_ICON###</a>';

	/**
	 * User function: Gets a backend form field for 'is_duplicate'
	 *
	 * @param \array $parameters
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject
	 * @return \string|NULL
	 */
	public function getIsDuplicateField ($parameters, $parentObject) {
		$isDuplicate = (bool)$parameters['row'][$parameters['field']];
		$isHashed = (bool)($parameters['row']['duplicate_hash_id']);
		$this->templateMarker['NAME'] = $parameters['label'];
		$this->templateMarker['INTRO'] = $parentObject->sL(self::$languageFile . 'question.duplicateTestPassed');
		$field = $parentObject->getSingleHiddenField(
			$parameters['table'],
			$parameters['field'],
			$parameters['row'],
			$parameters
		);
		if ($isHashed) {
			if ($isDuplicate) {
				$labelKey = 'label.failed';
				$this->templateMarker['DUPLICATEMESSAGE'] = $parentObject->sL(self::$languageFile . 'message.isDuplicateOf');
				$this->templateMarker['STATUSCLASS'] = 'error';
				$this->templateMarker['RECORDS'] = $this->renderOriginal($parameters['row'], $parameters['table'], $parentObject);
			} else {
				$labelKey = 'label.passed';
				$this->templateMarker['STATUSCLASS'] = 'ok';
				$duplicateRecords = $this->getDuplicates($parameters['table'], $parameters['field'], $parameters['row']);
				if ((bool) $duplicateRecords) {
					$this->templateMarker['RECORDS'] = $this->renderDuplicatesList($duplicateRecords, $parameters['table'], $parentObject);
					$this->templateMarker['DUPLICATEMESSAGE'] = sprintf(
						$parentObject->sL(self::$languageFile . 'message.hasDuplicates'),
						count($duplicateRecords)
					);
				} else {
					$this->templateMarker['DUPLICATEMESSAGE'] = $parentObject->sL(self::$languageFile . 'message.noDuplicates');
				}
			}
		} else {
			$labelKey = 'label.notProcessedYet';
			$this->templateMarker['STATUSCLASS'] = 'notice';
		}
		$this->templateMarker['STATUS'] = $parentObject->sL(self::$languageFile . $labelKey);

		$field .= $parentObject->intoTemplate($this->templateMarker, self::$fieldTemplateIsDuplicate);
		$parts = explode('|', self::$fieldWrap, 2);
		$out = $parts[0] . $field . $parts[1];
		return $out;
	}

	/**
	 * Gets duplicates for an original record
	 *
	 * @param \string $tableName
	 * @param \string $fieldName
	 * @param \array $originalRecord
	 * @return \array | NULL
	 */
	protected function getDuplicates ($tableName, $fieldName, $originalRecord) {
		return BackendUtility::getRecordsByField(
			$tableName,
			$fieldName,
			'1',
			' AND duplicate_hash_id=' . $originalRecord['duplicate_hash_id']
		);
	}

	/**
	 * Get the original for a duplicate
	 *
	 * @param \string $tableName
	 * @param \string $fieldName
	 * @param \array $duplicateRecord
	 * @return \array | NULL
	 */
	protected function getOriginal ($tableName, $fieldName, $duplicateRecord) {
		return BackendUtility::getRecordsByField(
			$tableName,
			$fieldName,
			'0',
			' AND duplicate_hash_id=' . $duplicateRecord['duplicate_hash_id'],
			'',
			'',
			1
		);
	}

	/**
	 * Renders a list of duplicates with edit link buttons
	 * 
	 * @param \array $duplicates An array of records
	 * @param \string $tableName
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject
	 * @return \string A list of duplicates
	 */
	protected function renderDuplicatesList ($duplicates, $tableName, &$parentObject) {
		$duplicatesList = '<ul>';
		foreach ($duplicates as $record) {
			$duplicatesList .= $parentObject->intoTemplate(
				array(
					'TITLE' => BackendUtility::getRecordTitle($tableName, $record, TRUE, TRUE),
					'EDITLINK' => $this->renderEditLink($record, $tableName, $parentObject)
				),
				self::$duplicateListEntryTemplate
			);
		}
		$duplicatesList .= '</ul>';
		return $duplicatesList;
	}

	/**
	 * Renders the title of the original with a show link
	 *
	 * @param \array $record An array with values
	 * @param \string $tableName
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject
	 * @return \string HTML
	 */
	protected function renderOriginal ($record, $tableName, &$parentObject) {
		return $parentObject->intoTemplate(
			array(
				'TITLE' => BackendUtility::getRecordTitle($tableName, $record, TRUE, TRUE),
				'SHOWLINK' => $this->renderShowLink($record, $tableName, $parentObject),
			),
			self::$originalRecordTemplate
		);
	}

	/**
	 * Renders an edit link with icon for a record.
	 *
	 * @param \array $record An array with values
	 * @param \string $tableName
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject
	 * @return \string An edit wizard
	 */
	protected function renderEditLink ($record, $tableName, $parentObject) {
		return $parentObject->intoTemplate(
			array(
				'TITLE' => $parentObject->sl(self::$languageFile . 'label.edit'),
				'TABLE' => $tableName,
				'UID' => $record['uid'],
				'ICON' => IconUtility::getSpriteIcon('actions-document-open'),
				'RETURNURL' => rawurlencode($parentObject->thisReturnUrl()),
			),
			self::$editLinkTemplate
		);
	}

	/**
	 * Renders a show link with icon for a record
	 *
	 * @param \array $record An array with values
	 * @param \string $tableName
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject
	 * @return \string An edit wizard
	 */
	protected function renderShowLink ($record, $tableName, $parentObject) {
		return $parentObject->intoTemplate(
			array(
				'TITLE' => $parentObject->sl(self::$languageFile . 'label.show'),
				'TABLE' => $tableName,
				'UID' => $record['uid'],
				'ICON' => IconUtility::getSpriteIcon('actions-document-info'),
			),
			self::$showLinkTemplate
		);
	}
}
