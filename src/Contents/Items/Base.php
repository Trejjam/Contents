<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 19.6.15
 * Time: 5:26
 */

namespace Trejjam\Utils\Contents\Items;


use Nette,
	Trejjam;

abstract class Base implements IEditItem
{
	const
		NEW_ITEM = '__new__',
		EMPTY_VALUE = '__empty__';

	protected $isRawValid = TRUE;
	protected $isUpdated  = FALSE;
	protected $updated    = NULL;
	protected $rawData;
	protected $data;
	protected $configuration;
	/**
	 * @var SubType[]
	 */
	protected $subTypes;

	/**
	 * @param            $configuration
	 * @param null       $data
	 * @param SubType[]  $subTypes
	 */
	function __construct($configuration, $data = NULL, array $subTypes = [])
	{
		$this->configuration = $configuration;
		$this->rawData = $data;
		$this->subTypes = $subTypes;

		$this->init();
	}

	protected function init()
	{
		$this->data = $this->sanitizeSubTypeData(
			$this->sanitizeData($this->rawData)
		);
	}

	/**
	 * @return SubType[]
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
		return $this->useSubType(function (SubType $subType, $data) {
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

		if (!is_null($class)) {
			$control->setAttribute('class', $class);
		}
		//$this->setRules($input, $validate);
	}

	public function update($data)
	{
		$this->isUpdated = $this->rawData !== $data;

		$this->rawData = $data;
		$this->init();
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
