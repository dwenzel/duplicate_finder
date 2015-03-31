<?php
namespace CPSIT\DuplicateFinder\Hooks;

class IconUtilityHook {
	/**
	 * Override Icon Overlay
	 * Sets a status which can be interpreted by the
	 * IconUtility for rendering an overlay
	 *
	 * @param \string $table
	 * @param \array $row
	 * @param \array $status
	 */
	public function overrideIconOverlay(&$table, &$row, &$status) {
		if ((bool)$row['is_duplicate']) {
			$status['isDuplicate'] = TRUE;
		}
	}
}
