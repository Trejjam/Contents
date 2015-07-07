<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 19.6.15
 * Time: 5:41
 */

namespace Trejjam\Contents;


use Nette,
	Trejjam;

class Factory
{
	/**
	 * @param                                         $configuration
	 * @param                                         $data
	 * @param Trejjam\Contents\Subtypes\SubType[] $subTypes
	 * @return Items\Base
	 */
	static function getItemObject($configuration, $data, array $subTypes = [])
	{
		$type = isset($configuration['type']) ? $configuration['type'] : NULL;
		if (is_scalar($configuration)) {
			$type = $configuration;
		}

		$out = NULL;
		switch ($type) {
			case 'container':

				$out = new Items\Container($configuration, $data, $subTypes);
				break;

			case 'list':

				$out = new Items\ListContainer($configuration, $data, $subTypes);
				break;

			case 'text':

				$out = new Items\Text($configuration, $data, $subTypes);
				break;

			default:
				throw new Trejjam\Contents\InvalidArgumentException("Unknown item type '$type'.", Trejjam\Contents\Exception::UNKNOWN_ITEM_TYPE);
		}

		return $out;
	}
}
