<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 19.6.15
 * Time: 5:30
 */

namespace Trejjam\Contents\Items;


use Nette,
	Trejjam,
	Trejjam\Contents\SubTypes;

class Text extends Base
{
	protected function sanitizeData($data, $first = FALSE)
	{
		if (is_scalar($data)) {
			return $data;
		}
		else {
			$this->isRawValid = FALSE;

			return '';
		}
	}

	/**
	 * @param bool|FALSE $forceObject
	 * @return string
	 */
	public function getContent($forceObject = FALSE)
	{
		return $this->data;
	}

	/**
	 * @param bool|FALSE $forceObject
	 * @return mixed
	 */
	public function getRawContent($forceObject = FALSE)
	{
		return $this->rawData;
	}
	public function getValidRawContent($forceObject = FALSE)
	{
		return $this->isRawValid ? $this->rawData : $this->data;
	}

	public function getRemovedItems()
	{
		$out = $this->rawData !== $this->data ? $this->rawData : NULL;

		list(, , $out) = $this->useSubType(function (SubTypes\SubType $subtype, array $inData) {
			list($data, $rawData, $previous) = $inData;

			$out = [
				$data,
				$rawData,
				$previous,
			];

			if ($previous !== FALSE) {
				$subRemoved = $subtype->removedContent($rawData, $data);

				if (!is_null($subRemoved)) {
					$out[2] = $subRemoved;
				}
			}

			return $out;
		}, [
			$this->data,
			$this->rawData,
			$out,
		]);

		return $out === FALSE ? NULL : $out;
	}

	/**
	 * @param Base|Text                        $item
	 * @param Nette\Forms\Container            $formContainer
	 * @param                                  $name
	 * @param                                  $parentName
	 * @param Nette\Forms\Rules                $togglingObject
	 * @param array                            $userOptions
	 */
	public function generateForm(Base $item, Nette\Forms\Container &$formContainer, $name, $parentName, $togglingObject, array $userOptions = [])
	{
		$addFormItem = $this->useSubType(function (SubTypes\SubType $subType, $addFormItem) use ($formContainer, $name, $parentName, $togglingObject, $userOptions) {
			if ($subType instanceof IEditItem) {
				$subType->generateForm($this, $formContainer, $name, $parentName, $togglingObject, $userOptions);

				return FALSE;
			}

			return $addFormItem;
		}, TRUE);

		if ($addFormItem) {
			$input = $formContainer->addText($name, $name);
			$input->setOption('id', $parentName . '__' . $name);
			$input->setValue($item->getValidRawContent());

			if (!is_null($togglingObject)) {
				$togglingObject->toggle($input->getOption('id'));
			}

			$item->applyUserOptions($input, $userOptions);
		}
	}

	public function update($data)
	{
		$oldData = $this->rawData;

		parent::update($data);

		if ($this->isUpdated) {
			$this->updated = is_null($oldData) ? self::EMPTY_VALUE : $oldData;
		}
	}
}
