<?php
/**
 * @package		Joomla.Site
 * @subpackage	com_users
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Registration controller class for Users.
 *
 * @package		Joomla.Site
 * @subpackage	com_users
 * @since		1.6
 */
class UsersControllerRegistration extends UsersControllerRegistrationDefault
{
	/**
	 * Method to register a user.
	 *
	 * @since	1.6
	 */
	public function register()
	{
		die('This is my override '.__METHOD__.' at '.__FILE__);
	}
}
