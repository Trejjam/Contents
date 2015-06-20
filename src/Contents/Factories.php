<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 19.6.15
 * Time: 5:41
 */

namespace Trejjam\Utils\Contents;


use Nette,
	Trejjam;

class Factory
{
	/**
	 * @param $configuration
	 * @param $data
	 * @return Items\Base
	 */
	static function getItemObject($configuration, $data)
	{
		$type = isset($configuration['type']) ? $configuration['type'] : NULL;
		if (is_scalar($configuration)) {
			$type = $configuration;
		}

		$out = NULL;
		switch ($type) {
			case 'container':

				$out = new Items\Container($configuration, $data);
				break;

			case 'list':

				$out = new Items\ListContainer($configuration, $data);
				break;

			case 'text':

				$out = new Items\Text($configuration, $data);
				break;

			default:
				throw new Trejjam\Utils\InvalidArgumentException("Unknown item type '$type'.", Trejjam\Utils\Exception::CONTENTS_UNKNOWN_ITEM_TYPE);
		}

		return $out;
	}
}