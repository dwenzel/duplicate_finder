<?php
namespace CPSIT\DuplicateFinder\Hooks;
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

/**
 * Class FormEngineHook
 * Provides hook methods for TYPO3\CMS\Backend\Form\FormEngine
 *
 * @author Dirk Wenzel <dirk.wenzel@cps-it.de>
 * @package Cps\WisImportCourses\Hooks
 */
use TYPO3\CMS\Backend\Form\FormEngine;
class FormEngineHook {
	/**
	 * Performs tasks before FormEngine renders a field
	 *
	 * @param string $table The table name
	 * @param string $field The field name
	 * @param array $row The record to edit from the database table.
	 * @param string $altName Alternative field name label to show.
	 * @param boolean $palette Set this if the field is on a palette (in top frame), otherwise not. (if set, field will render as a hidden field).
	 * @param string $extra The "extra" options from "Part 4" of the field configurations found in the "types" "showitem" list. Typically parsed by $this->getSpecConfFromString() in order to get the options as an associative array.
	 * @param integer $pal The palette pointer.
	 * @param FormEngine $parentObject
	 * @return void
	 */
	public function getSingleField_preProcess($table, $field, &$row, &$altName, &$palette, $extra, $pal, &$parentObject) {
		// do not display field 'is_duplicate' if its value is FALSE
		if ($field === 'is_duplicate' AND !(bool)$row['is_duplicate']) {
			$GLOBALS['TCA'][$table]['columns'][$field]['config']['noTableWrapping'] = TRUE;
		}
	}
}
