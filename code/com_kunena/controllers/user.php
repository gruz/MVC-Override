<?php
/**
 * Kunena Component
 *
 * @package     Kunena.Site
 * @subpackage  Controllers
 *
 * @copyright   (C) 2008 - 2017 Kunena Team. All rights reserved.
 * @license     https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        https://www.kunena.org
 **/
defined('_JEXEC') or die();

/**
 * Kunena User Controller
 *
 * @since  2.0
 */
class KunenaControllerUser extends KunenaControllerUserDefault
{

	/**
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 */
	protected function saveUser()
	{
		// We only allow users to edit few fields
		/* ##mygruz20170819003658 {
		It was:
		$allow = array('name', 'email', 'password', 'password2', 'params');
		It became: */
		$allow = array('given_name', 'name', 'email', 'password', 'password2', 'params');
		/* ##mygruz20170819003658 } */


		if (JComponentHelper::getParams('com_users')->get('change_login_name', 1))
		{
			$allow[] = 'username';
		}

		// Clean request
		$post       = $this->app->input->post->getArray();
		$post_password = $this->app->input->post->get('password', '','raw');
		$post_password2 = $this->app->input->post->get('password2', '','raw');

		if (empty($post_password) || empty($post_password2))
		{
			unset($post['password'], $post['password2']);
		}
		else
		{
			// Do a password safety check.
			if ($post_password != $post_password2)
			{
				$this->app->enqueueMessage(JText::_('COM_KUNENA_PROFILE_PASSWORD_MISMATCH'), 'notice');

				return false;
			}

			if (strlen($post_password) < 5)
			{
				$this->app->enqueueMessage(JText::_('COM_KUNENA_PROFILE_PASSWORD_NOT_MINIMUM'), 'notice');

				return false;
			}

			$value            = $post_password;
			$meter            = isset($element['strengthmeter'])  ? ' meter="0"' : '1';
			$threshold        = isset($element['threshold']) ? (int) $element['threshold'] : 66;
			$minimumLength    = isset($element['minimum_length']) ? (int) $element['minimum_length'] : 4;
			$minimumIntegers  = isset($element['minimum_integers']) ? (int) $element['minimum_integers'] : 0;
			$minimumSymbols   = isset($element['minimum_symbols']) ? (int) $element['minimum_symbols'] : 0;
			$minimumUppercase = isset($element['minimum_uppercase']) ? (int) $element['minimum_uppercase'] : 0;

			// If we have parameters from com_users, use those instead.
			// Some of these may be empty for legacy reasons.
			$params = JComponentHelper::getParams('com_users');

			if (!empty($params))
			{
				$minimumLengthp    = $params->get('minimum_length');
				$minimumIntegersp  = $params->get('minimum_integers');
				$minimumSymbolsp   = $params->get('minimum_symbols');
				$minimumUppercasep = $params->get('minimum_uppercase');
				$meterp            = $params->get('meter');
				$thresholdp        = $params->get('threshold');

				empty($minimumLengthp) ? : $minimumLength = (int) $minimumLengthp;
				empty($minimumIntegersp) ? : $minimumIntegers = (int) $minimumIntegersp;
				empty($minimumSymbolsp) ? : $minimumSymbols = (int) $minimumSymbolsp;
				empty($minimumUppercasep) ? : $minimumUppercase = (int) $minimumUppercasep;
				empty($meterp) ? : $meter = $meterp;
				empty($thresholdp) ? : $threshold = $thresholdp;
			}

			// If the field is empty and not required, the field is valid.
			$required = ((string) $element['required'] == 'true' || (string) $element['required'] == 'required');

			if (!$required && empty($value))
			{
				return true;
			}

			$valueLength = strlen($value);

			// Load language file of com_users component
			JFactory::getLanguage()->load('com_users');

			// We set a maximum length to prevent abuse since it is unfiltered.
			if ($valueLength > 4096)
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_USERS_MSG_PASSWORD_TOO_LONG'), 'warning');
			}

			// We don't allow white space inside passwords
			$valueTrim = trim($value);

			// Set a variable to check if any errors are made in password
			$validPassword = true;

			if (strlen($valueTrim) != $valueLength)
			{
				JFactory::getApplication()->enqueueMessage(
					JText::_('COM_USERS_MSG_SPACES_IN_PASSWORD'),
					'warning'
				);

				$validPassword = false;
			}

			// Minimum number of integers required
			if (!empty($minimumIntegers))
			{
				$nInts = preg_match_all('/[0-9]/', $value, $imatch);

				if ($nInts < $minimumIntegers)
				{
					JFactory::getApplication()->enqueueMessage(
						JText::plural('COM_USERS_MSG_NOT_ENOUGH_INTEGERS_N', $minimumIntegers),
						'warning'
					);

					$validPassword = false;
				}
			}

			// Minimum number of symbols required
			if (!empty($minimumSymbols))
			{
				$nsymbols = preg_match_all('[\W]', $value, $smatch);

				if ($nsymbols < $minimumSymbols)
				{
					JFactory::getApplication()->enqueueMessage(
						JText::plural('COM_USERS_MSG_NOT_ENOUGH_SYMBOLS_N', $minimumSymbols),
						'warning'
					);

					$validPassword = false;
				}
			}

			// Minimum number of upper case ASCII characters required
			if (!empty($minimumUppercase))
			{
				$nUppercase = preg_match_all('/[A-Z]/', $value, $umatch);

				if ($nUppercase < $minimumUppercase)
				{
					JFactory::getApplication()->enqueueMessage(
						JText::plural('COM_USERS_MSG_NOT_ENOUGH_UPPERCASE_LETTERS_N', $minimumUppercase),
						'warning'
					);

					$validPassword = false;
				}
			}

			// Minimum length option
			if (!empty($minimumLength))
			{
				if (strlen((string) $value) < $minimumLength)
				{
					JFactory::getApplication()->enqueueMessage(
						JText::plural('COM_USERS_MSG_PASSWORD_TOO_SHORT_N', $minimumLength),
						'warning'
					);

					$validPassword = false;
				}
			}

			// If valid has violated any rules above return false.
			if (!$validPassword)
			{
				return false;
			}
		}

		$post = array_intersect_key($post, array_flip($allow));

		if (empty($post))
		{
			return true;
		}

		$username = $this->user->get('username');
		$user = new JUser($this->user->id);

		// Bind the form fields to the user table and save.
		if (!($user->bind($post) && $user->save(true)))
		{
			$this->app->enqueueMessage($user->getError(), 'notice');

			return false;
		}

		// Reload the user.
		$this->user->load($this->user->id);
		$session = JFactory::getSession();
		$session->set('user', $this->user);

		// Update session if username has been changed
		if ($username && $username != $this->user->username)
		{
			$table = JTable::getInstance('session', 'JTable');
			$table->load($session->getId());

			$table->username = $this->user->username;
			$table->store();
		}

		return true;
	}
}
