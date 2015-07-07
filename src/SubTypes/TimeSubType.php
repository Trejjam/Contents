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
	Trejjam\Contents\Items,
	Nette\Application\UI;

class TimeSubType extends SubType implements Items\IEditItem
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
		return $data;
	}

	public function removedContent($rawData, $data)
	{
		return parent::removedContent($rawData, $data);
	}

	public function generateForm(Items\Base $item, Nette\Forms\Container &$formContainer, $name, $parentName, $togglingObject, array $userOptions = [])
	{
		$input = $formContainer->addText($name, $name);
		$input->setOption('id', $parentName . '__' . $name);
		$input->setValue($item->getContent());
		$input->setType('time');

		$input
			->addCondition(UI\Form::FILLED)
			->addRule(UI\Form::PATTERN, __('Time must be in format HH:MM'), '([0-9]{2}[-: ]{1}[0-9]{2})');

		if (!is_null($togglingObject)) {
			$togglingObject->toggle($input->getOption('id'));
		}

		$item->applyUserOptions($input, $userOptions);
	}
}
