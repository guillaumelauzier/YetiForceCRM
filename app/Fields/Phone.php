<?php
/**
 * Tools for phone class.
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Fields;

/**
 * Phone class.
 */
class Phone
{
	/**
	 * Get phone details.
	 *
	 * @param string      $phoneNumber
	 * @param null|string $phoneCountry
	 *
	 * @return array|bool
	 */
	public static function getDetails(string $phoneNumber, ?string $phoneCountry = null)
	{
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$swissNumberProto = $phoneUtil->parse($phoneNumber, $phoneCountry);
			if ($phoneUtil->isValidNumber($swissNumberProto)) {
				return [
					'number' => $phoneUtil->format($swissNumberProto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL),
					'geocoding' => \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance()->getDescriptionForNumber($swissNumberProto, \App\Language::getLanguage()),
					'carrier' => \libphonenumber\PhoneNumberToCarrierMapper::getInstance()->getNameForValidNumber($swissNumberProto, \App\Language::getShortLanguageName()),
					'country' => $phoneUtil->getRegionCodeForNumber($swissNumberProto),
				];
			}
		} catch (\libphonenumber\NumberParseException $e) {
			\App\Log::info($e->getMessage(), __CLASS__);
		}
		return false;
	}

	/**
	 * Verify phone number.
	 *
	 * @param string      $phoneNumber
	 * @param null|string $phoneCountry
	 *
	 * @throws \App\Exceptions\FieldException
	 *
	 * @return bool
	 */
	public static function verifyNumber($phoneNumber, $phoneCountry)
	{
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		if ($phoneCountry && !in_array($phoneCountry, $phoneUtil->getSupportedRegions())) {
			throw new \App\Exceptions\FieldException('LBL_INVALID_COUNTRY_CODE');
		}
		try {
			$swissNumberProto = $phoneUtil->parse($phoneNumber, $phoneCountry);
			if ($phoneUtil->isValidNumber($swissNumberProto)) {
				$phoneNumber = $phoneUtil->format($swissNumberProto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);

				return [
					'isValidNumber' => true,
					'number' => $phoneNumber,
					'geocoding' => \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance()->getDescriptionForNumber($swissNumberProto, \App\Language::getLanguage()),
					'carrier' => \libphonenumber\PhoneNumberToCarrierMapper::getInstance()->getNameForValidNumber($swissNumberProto, \App\Language::getShortLanguageName()),
					'country' => $phoneUtil->getRegionCodeForNumber($swissNumberProto),
				];
			}
		} catch (\libphonenumber\NumberParseException $e) {
			\App\Log::info($e->getMessage(), __CLASS__);
		}
		throw new \App\Exceptions\FieldException('LBL_INVALID_PHONE_NUMBER');
	}

	/**
	 * Get proper number.
	 *
	 * @param string   $numberToCheck
	 * @param null|int $userId
	 *
	 * @return false|string Return false if wrong number
	 */
	public static function getProperNumber(string $numberToCheck, ?int $userId = null)
	{
		if (null === $userId) {
			$userId = \App\User::getCurrentUserId();
		}
		$returnVal = false;
		if (static::getDetails($numberToCheck)) {
			$returnVal = $numberToCheck;
		} else {
			$country = \App\User::getUserModel($userId)->getDetail('sync_carddav_default_country');
			if (!empty($country) && ($phoneDetails = static::getDetails($numberToCheck, Country::getCountryCode($country)))) {
				$returnVal = $phoneDetails['number'];
			}
		}
		return $returnVal;
	}
}
