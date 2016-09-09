<?php
/**
 * Script file
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
// No direct access to this file
defined('_JEXEC') or die;

if (!class_exists('ScriptAry'))
{
	include dirname(__FILE__) . '/scriptary.php';
}


class plgsystemmvcoverrideInstallerScript extends ScriptAry {

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight($type, $parent) {
		$manifest = $parent->getParent()->getManifest();

		if ($type == 'install') {
			//Get the smallest order value
			$db = JFactory::getDbo();
			// Create a new query object.
			$query = $db->getQuery(true);
			$query
				->select($db->quoteName(array('extension_id','element','ordering')))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type').'='.$db->Quote($manifest['type']))
				->where($db->quoteName('folder').'='.$db->Quote($manifest['group']))
				->order($db->quoteName('ordering').' ASC');

			$db->setQuery($query,0,1);
			$row = $db->loadAssoc();
			$ordering = $row['ordering']-1;

			$query = $db->getQuery(true);
			// Fields to update.
			$fields = array(
				$db->quoteName('ordering').'='.$db->Quote($ordering)
			);
			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('type').'='.$db->Quote($manifest['type']),
				$db->quoteName('folder').'='.$db->Quote($manifest['group']),
				$db->quoteName('element').'='.$db->Quote('mvcoverride')
			);
			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
			$db->setQuery($query);


			try {// It's a DB usage construction to contain J2.5 and J3.0 approaches
				if ($result = $db->execute() ) {
					if ($db->getAffectedRows()>0) {
						$this->messages[] = JText::_('GJ_INSTALL_ORDERING_SET');
					}
					else {
						throw new Exception(JText::_('GJ_INSTALL_ORDERING_SET_FAILED'));
					}
				}
				else {
					throw new Exception($db->getErrorMsg());
				}

			} catch (Exception $e) {
				// Catch the error.
				JError::raiseWarning(100, $e->getMessage(), $db->stderr(true));
			}
		}

		parent::postflight($type, $parent);
	}
}
?>

