<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 19.6.15
 * Time: 5:26
 */

namespace Trejjam\Contents\Items;


use Nette,
	Trejjam,
	Trejjam\Contents\SubTypes;

abstract class Base implements IEditItem
{
	const
		NEW_CONTAINER = '__new_container',
		NEW_ITEM_BUTTON = '__button__',
		NEW_ITEM_BUTTON_LABEL = 'New item',
		NEW_ITEM_CONTENT = '__content__',
		EMPTY_VALUE = '__empty__';

	protected $isRawValid = TRUE;
	protected $isUpdated  = FALSE;
	protected $updated    = NULL;
	protected $rawData;
	protected $data;
	protected $configuration;
	/**
	 * @var SubTypes\SubType[]
	 */
	protected $subTypes;

	/**
	 * @param                    $configuration
	 * @param null               $data
	 * @param SubTypes\SubType[] $subTypes
	 */
	function __construct($configuration, $data = NULL, array $subTypes = [])
	{
		$this->configuration = $configuration;
		$this->rawData = $data;
		$this->subTypes = $subTypes;

		$this->init(TRUE);
	}

	protected function init($first = FALSE)
	{
		$this->data = $this->sanitizeSubTypeData(
			$this->sanitizeData($this->rawData, $first)
		);
	}

	/**
	 * @return SubTypes\SubType[]
	 */
	protected function getSuitableSubTypes()
	{
		$out = [];

		foreach ($this->subTypes as $k => $v) {
			if ($v->applyOn($this)) {
				$out[$k] = $v;
			}
		}

		return $out;
	}
	/**
	 * @param mixed $data
	 * @return mixed
	 */
	protected function sanitizeSubTypeData($data)
	{
		return $this->useSubType(function (SubTypes\SubType $subType, $data) {
			return $subType->sanitize($data);
		}, $data);
	}

	public function useSubType(callable $callback, $previous = NULL)
	{
		if (isset($this->configuration['subType'])) {
			$itemSubType = is_array($this->configuration['subType']) ? $this->configuration['subType'] : [$this->configuration['subType']];

			foreach ($this->getSuitableSubTypes() as $subTypeName => $subType) {
				if (in_array($subTypeName, $itemSubType)) {
					$previous = $callback($subType, $previous);
				}
			}
		}

		return $previous;
	}

	abstract protected function sanitizeData($data);
	abstract public function getContent($forceObject = FALSE);
	abstract public function getRawContent($forceObject = FALSE);
	abstract public function getRemovedItems();

	/**
	 * @param Nette\Forms\Controls\BaseControl $control
	 * @param array                            $options
	 */
	public function applyUserOptions($control, array $options)
	{
		$class = isset($options['class']) ? $options['class'] : NULL;
		$label = isset($options['label']) ? $options['label'] : NULL;

		if (!is_null($class)) {
			$control->setAttribute('class', $class);
		}
		if (!is_null($label)) {
			$control->caption = $label;
		}
		//$this->setRules($input, $validate);
	}

	public function update($data)
	{
		$data = $this->useSubType(function (SubTypes\SubType $subType, $data) {
			return $subType->update($this, $data);
		}, $data);

		$this->isUpdated = is_scalar($this->rawData) && is_scalar($data)
			? $this->rawData != $data
			: $this->rawData !== $data;

		$this->rawData = $data;

		$this->init();

		return $data;
	}

	public function getUpdated()
	{
		return $this->isUpdated ? $this->updated : NULL;
	}

	public function getConfigValue($name, $default, $userOptions = [])
	{
		return isset($userOptions[$name])
			? $userOptions[$name]
			: (isset($this->configuration[$name])
				? $this->configuration[$name]
				: $default
			);
	}

	public function __toString()
	{
		return $this->rawData;
	}
}
