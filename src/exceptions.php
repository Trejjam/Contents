<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 18.6.15
 * Time: 12:17
 */

namespace Trejjam\Contents;


use Nette,
	Trejjam;

interface Exception
{
	const
		UNKNOWN_ITEM_TYPE = 1,
		INCOMPLETE_CONFIGURATION = 2,
		COLLISION_CONFIGURATION = 4,
		MISSING_CONFIGURATION = 8,
		JSON_DECODE = 16,
		CHILD_NOT_EXIST = 32,

		/**
		 * @deprecated
		 */
		CONTENTS_UNKNOWN_ITEM_TYPE = self::UNKNOWN_ITEM_TYPE,
		/**
		 * @deprecated
		 */
		CONTENTS_INCOMPLETE_CONFIGURATION = self::INCOMPLETE_CONFIGURATION,
		/**
		 * @deprecated
		 */
		CONTENTS_COLLISION_CONFIGURATION = self::COLLISION_CONFIGURATION,
		/**
		 * @deprecated
		 */
		CONTENTS_MISSING_CONFIGURATION = self::MISSING_CONFIGURATION,
		/**
		 * @deprecated
		 */
		CONTENTS_JSON_DECODE = self::JSON_DECODE,
		/**
		 * @deprecated
		 */
		CONTENTS_CHILD_NOT_EXIST = self::CHILD_NOT_EXIST;
}

class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}

class DomainException extends \DomainException implements Exception
{
}

class LogicException extends \LogicException implements Exception
{
}
