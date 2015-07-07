<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 22.6.15
 * Time: 16:27
 */

namespace Trejjam\Contents\SubTypes;


use Nette,
	Trejjam,
	Trejjam\Contents\Items;

class BoolSubType extends SubType implements Items\IEditItem
{
	/**
	 * Enable usage in items
	 * @param Items\Base $base
	 * @return bool
	 */
	public function applyOn(Items\Base $base)
	{
		$use = FALSE;

		if ($base instanceof Items\Text) {
			$use = TRUE;
		}

		return $use;
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize($data)
	{
		if (is_bool($data)) {
			return $data;
		}
		else {
			return FALSE;
		}
	}

	public function generateForm(Items\Base $item, Nette\Forms\Container &$formContainer, $name, $parentName, $togglingObject, array $userOptions = [])
	{
		$input = $formContainer->addCheckbox($name, $name);
		$input->setOption('id', $parentName . '__' . $name);
		$input->setValue($item->getContent());

		if (!is_null($togglingObject)) {
			$togglingObject->toggle($input->getOption('id'));
		}

		$item->applyUserOptions($input, $userOptions);
	}
}
