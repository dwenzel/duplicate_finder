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
	 * User function: Gets a backend form field for 'is_duplicate'
	 *
	 * @param \array $parameters
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject
	 * @return \string|NULL
	 */
	public function getIsDuplicateField ($parameters, $parentObject) {
		$field = NULL;
		if ((bool)$parameters['row']['is_duplicate']) {
			$field = $parentObject->getSingleHiddenField(
				$parameters['table'],
				$parameters['field'],
				$parameters['row'],
				$parameters
			);
			$field .= IconUtility::getSpriteIcon('extensions-duplicate_finder-icon-is-duplicate');
		}
		return $field;
	}
}
