<?php
/**
 * @package    Profiles Package
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;

class ProfilesModelUser extends BaseDatabaseModel
{
	/**
	 * Get username by parameter
	 *
	 * @param string        $by
	 * @param string| array $value
	 *
	 * @return bool|string
	 *
	 * @since 1.0.0
	 */
	public function getUsername($by = 'id', $value)
	{
		if (!empty($value))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('u.username')
				->from($db->quoteName('#__users', 'u'));

			if ($by == 'id')
			{
				$query->where($db->quoteName('u.id') . ' = ' . $db->quote($value));
			}
			elseif ($by == 'email')
			{
				$query->where($db->quoteName('u.email') . ' = ' . $db->quote($value));
			}
			elseif ($by == 'phone')
			{
				$code   = '+7';
				$number = $this->clearPhoneNumber($value);
				if (empty($number))
				{
					return false;
				}
				$query->join('LEFT', '#__user_phones AS phone ON phone.user_id = u.id')
					->where($db->quoteName('phone.code') . ' = ' . $db->quote($code))
					->where($db->quoteName('phone.number') . ' = ' . $db->quote($number));
			}
			elseif ($by == 'social')
			{
				if (empty($value['provider']) || empty($value['social_id']))
				{
					return false;
				}

				$provider  = $value['provider'];
				$social_id = $value['social_id'];
				$query->join('LEFT', '#__user_socials AS social ON social.user_id = u.id')
					->where($db->quoteName('social.provider') . ' = ' . $db->quote($provider))
					->where($db->quoteName('social.social_id') . ' = ' . $db->quote($social_id));
			}

			$db->setQuery($query);
			$username = $db->loadResult();

			return (!empty($username)) ? $username : false;
		}

		return false;
	}

	/**
	 * Get id by parameter
	 *
	 * @param $by
	 * @param $value
	 *
	 * @return bool|string
	 *
	 * @since 1.0.0
	 */
	public function getID($by = 'email', $value)
	{
		if (!empty($value))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('u.id')
				->from($db->quoteName('#__users', 'u'));

			if ($by == 'email')
			{
				$query->where($db->quoteName('u.email') . ' = ' . $db->quote($value));
			}
			elseif ($by == 'phone')
			{
				$code   = '+7';
				$number = $this->clearPhoneNumber($value);
				if (empty($number))
				{
					return false;
				}
				$query->join('LEFT', '#__user_phones AS phone ON phone.user_id = u.id')
					->where($db->quoteName('phone.code') . ' = ' . $db->quote($code))
					->where($db->quoteName('phone.number') . ' = ' . $db->quote($number));
			}
			elseif ($by == 'social')
			{
				if (empty($value['provider']) || empty($value['social_id']))
				{
					return false;
				}
				$provider  = $value['provider'];
				$social_id = $value['social_id'];
				$query->join('LEFT', '#__user_socials AS social ON social.user_id = u.id')
					->where($db->quoteName('social.provider') . ' = ' . $db->quote($provider))
					->where($db->quoteName('social.social_id') . ' = ' . $db->quote($social_id));
			}

			$db->setQuery($query);
			$id = $db->loadResult();

			return (!empty($id)) ? $id : false;
		}

		return false;
	}

	/**
	 * Get user phone
	 *
	 * @param int $user_id User ID
	 *
	 * @return  bool | object
	 * @since 1.0.0
	 */
	public function getPhone($user_id)
	{
		if (!empty($user_id))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('code', 'number'))
				->from($db->quoteName('#__user_phones'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id));
			$db->setQuery($query);
			$phone = $db->loadObject();

			return (!empty($phone)) ? $phone : false;
		}

		return false;
	}

	/**
	 * Add user phone
	 *
	 * @param int   $user_id User ID
	 * @param array $value   Phone code and number
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function addPhone($user_id, $value)
	{
		if (!empty($user_id))
		{
			if (!empty($value))
			{
				if (is_object($value))
				{
					$value = ArrayHelper::fromObject($value);
				}
				elseif (is_string($value))
				{
					$value = array('number' => $value);
				}

				$phone          = new stdClass();
				$phone->user_id = $user_id;
				$phone->code    = (!empty($value['code'])) ? $value['code'] : '+7';
				$phone->number  = (!empty($value['number'])) ? $this->clearPhoneNumber($value['number']) : '';

				if (!empty($phone->number))
				{
					return Factory::getDbo()->insertObject('#__user_phones', $phone);
				}
			}
		}

		return false;
	}

	/**
	 * Delete user phone
	 *
	 * @param int $user_id User ID
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function deletePhone($user_id)
	{
		if (!empty($user_id))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__user_phones'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id));
			$db->setQuery($query);

			return $db->execute();
		}

		return false;
	}

	/**
	 * Prepare user phone
	 *
	 * @param  array $phone phone array
	 *
	 * @param string $action
	 *
	 * @return array|string
	 *
	 * @since 1.0.0
	 */
	public function preparePhone($phone, $action = 'save')
	{
		if ($action == 'save')
		{
			$phone = (!empty($phone['phone_1'])) ? $phone['phone_1'] : false;
			if ($phone)
			{
				$result           = array();
				$result['code']   = (!empty($phone['code'])) ? $phone['code'] : '+7';
				$result['number'] = (!empty($phone['number'])) ? $this->clearPhoneNumber($phone['number']) : '';

				return (!empty($result['number'])) ? $result : '';
			}
		}
		if ($action == 'get' && $phone)
		{
			if (empty($phone->number))
			{
				return '';
			}

			$result                      = array();
			$result['phone_1']           = array();
			$result['phone_1']['code']   = (!empty($phone->code)) ? $phone->code : '+7';
			$result['phone_1']['number'] = (!empty($phone->number)) ? $this->clearPhoneNumber($phone->number) : '';

			return (!empty($result['phone_1']['number'])) ? $result : '';
		}

		return '';
	}

	/**
	 * Validate user phone
	 *
	 * @param int   $user_id User ID
	 * @param array $value   Phone code and number
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function validatePhone($user_id = 0, $value)
	{
		if (empty($value))
		{
			return true;
		}
		if (is_object($value))
		{
			$value = ArrayHelper::fromObject($value);
		}
		elseif (is_string($value))
		{
			$value = array('number' => $value);
		}

		$code   = (!empty($value['code'])) ? $value['code'] : '+7';
		$number = (!empty($value['number'])) ? $this->clearPhoneNumber($value['number']) : '';
		if (empty($number))
		{
			return true;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('user_id')
			->from('#__user_phones')
			->where($db->quoteName('user_id') . ' <> ' . $db->quote($user_id))
			->where($db->quoteName('code') . ' = ' . $db->quote($code))
			->where($db->quoteName('number') . ' = ' . $db->quote($number));
		$db->setQuery($query);

		return (empty($db->loadResult()));
	}

	/**
	 * Clear phone number
	 *
	 * @param  string $number Phone number
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function clearPhoneNumber($number)
	{
		if (mb_strlen($number) > 10)
		{
			$number = str_replace(array('+7'), '', $number);
			$number = preg_replace('/\D/', '', $number);

		}
		if (mb_strlen($number) > 10 && mb_substr($number, 0, 1) == 8)
		{
			$number = mb_substr($number, 1);
		}
		$number = (int) $number;

		return $number;
	}

	/**
	 * Get user social
	 *
	 * @param  int   $user_id  User id
	 * @param string $provider Social provider
	 *
	 * @return bool | object | array
	 *
	 * @since 1.0.0
	 */
	public function getSocial($user_id, $provider = '')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__user_socials'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id));

		if (!empty($provider))
		{
			$query->where($db->quoteName('provider') . ' = ' . $db->quote($provider));
		}
		$db->setQuery($query);

		return (!empty($provider)) ? $db->loadObject() : $db->loadObjectList('provider');
	}

	/**
	 * Add user social
	 *
	 * @param int    $user_id   User ID
	 * @param string $provider  Social provider
	 * @param string $social_id Social profile id
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function addSocial($user_id, $provider, $social_id)
	{
		if (!empty($user_id) && !empty($provider) && !empty($social_id))
		{
			$object            = new stdClass();
			$object->user_id   = $user_id;
			$object->provider  = $provider;
			$object->social_id = $social_id;

			return Factory::getDbo()->insertObject('#__user_socials', $object);
		}

		return false;
	}

	/**
	 * Delete  user social
	 *
	 * @param int    $user_id  User ID
	 * @param string $provider Social provider
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function deleteSocial($user_id, $provider = '')
	{
		if (!empty($user_id))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__user_socials'))
				->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id));
			if (!empty($provider))
			{
				$query->where($db->quoteName('provider') . ' = ' . $db->quote($provider));
			}
			$db->setQuery($query);

			return $db->execute();
		}

		return false;
	}

	/**
	 * Generate  user passwords for social authentication
	 *
	 * @param int    $user_id   Joomla user id
	 * @param string $provider  Social network name
	 * @param int    $social_id Social network user id
	 *
	 * @return string md5 password
	 *
	 * @since 1.0.0
	 */
	public function generateSocialPassword($user_id, $provider, $social_id)
	{
		$params = ComponentHelper::getParams('com_profiles');

		return md5($user_id . $provider . $social_id . $params->get('secret', ''));
	}

	/**
	 * Get user passwords for social networks
	 *
	 * @param int $user_id Joomla user id
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getSocialPasswords($user_id)
	{
		$socials = $this->getSocial($user_id);

		if (!empty($socials))
		{
			$passwords = array();
			foreach ($socials as $data)
			{
				$passwords[] = $this->generateSocialPassword($data->user_id, $data->provider, $data->social_id);
			}
		}

		return (!empty($socials)) ? $passwords : array();
	}

}