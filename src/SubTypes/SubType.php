<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 22.6.15
 * Time: 16:17
 */

namespace Trejjam\Contents\SubTypes;

use Trejjam,
	Trejjam\Contents\Items;

abstract class SubType
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @internal
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Enable usage in items
	 * @param Items\Base $base
	 * @return bool
	 */
	public abstract function applyOn(Items\Base $base);

	/**
	 * @param Items\Base $base
	 * @param      $data
	 * @return mixed
	 */
	public function update(Items\Base $base, $data)
	{
		return $data;
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public abstract function sanitize($data);

	/**
	 * @param $rawData
	 * @param $data
	 * @return NULL|FALSE NULL - without change result, FALSE - force NULL result
	 */
	public function removedContent($rawData, $data)
	{
		return NULL;
	}
}
