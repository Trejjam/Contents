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

class DateSubType extends SubType implements Items\IEditItem
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

	public function generateForm(Items\Base $item, Nette\Forms\Container &$formContainer, $name, $parentName, $togglingObject, array $userOptions = [])
	{
		$input = $formContainer->addText($name, $name);
		$input->setOption('id', $parentName . '__' . $name);
		$input->setValue($item->getContent());
		$input->setType('date');

		$input
			->addCondition(UI\Form::FILLED)
			->addRule(UI\Form::PATTERN, __('Date must be in format YYYY-MM-DD'), '([0-9]{4}-[0-9]{2}-[0-9]{2})|(\d{1,2}[/.]{1}[ ]{0,1}\d{1,2}[/.]{1}[ ]{0,1}\d{4})');

		if (!is_null($togglingObject)) {
			$togglingObject->toggle($input->getOption('id'));
		}

		$item->applyUserOptions($input, $userOptions);
	}

	public function update(Items\Base $item, $data)
	{
		if (preg_match('~^\d{1,2}[/.]{1}[ ]{0,1}\d{1,2}[/.]{1}[ ]{0,1}\d{4}$~', $data, $arr)) {
			$dateArr = preg_split('/[.\/]{1}[ ]{0,1}/s', $arr[0]);
			$date = new Nette\Utils\DateTime($dateArr[2] . '-' . $dateArr[1] . '-' . $dateArr[0]);

			$data = $date->format('Y-m-d');
		}

		return $data;
	}
}
