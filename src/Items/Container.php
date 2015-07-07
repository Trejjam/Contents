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
	Trejjam\Contents;

class Container extends Base
{
	/**
	 * @var Base[]
	 */
	protected $data = [];

	protected $updated = [];

	public function __clone()
	{
		foreach ($this->data as $k => $v) {
			$this->data[$k] = clone $v;
		}
	}

	protected function sanitizeData($data, $first=false)
	{
		if (!isset($this->configuration['child'])) {
			throw new Contents\DomainException('Container has not defined child.', Contents\Exception::INCOMPLETE_CONFIGURATION);
		}
		$child = $this->configuration['child'];

		/** @var Base[] $out */
		$out = $this->data;

		foreach ($child as $k => $v) {
			if (isset($out[$k])) {
				$out[$k]->update(isset($data[$k]) ? $data[$k] : NULL);
			}
			else {
				$out[$k] = Contents\Factory::getItemObject($v, isset($data[$k]) ? $data[$k] : NULL, $this->subTypes);
			}
		}

		return $out;
	}

	/**
	 * @return Base[]
	 */
	public function getChild()
	{
		return $this->data;
	}

	/**
	 * @param bool|FALSE $forceObject
	 * @return array|object
	 */
	public function getContent($forceObject = FALSE)
	{
		$out = [];

		foreach ($this->data as $k => $v) {
			if (is_null($v)) {
				continue;
			}

			$out[$k] = $v->getContent($forceObject);
		}

		return $forceObject ? Nette\Utils\ArrayHash::from($out) : $out;
	}

	/**
	 * @param bool|FALSE $forceObject
	 * @return array|object
	 */
	public function getRawContent($forceObject = FALSE)
	{
		$out = [];

		foreach ($this->data as $k => $v) {
			if (is_null($v)) {
				continue;
			}

			$out[$k] = $v->getRawContent($forceObject);
		}

		return $forceObject ? Nette\Utils\ArrayHash::from($out) : $out;
	}

	public function getRemovedItems()
	{
		$out = [];

		if (is_null($this->rawData)) {
			return NULL;
		}
		else if (!is_array($this->rawData)) {
			return $this->rawData;
		}

		foreach (array_merge($this->rawData, $this->data) as $k => $v) {
			if ($v instanceof Container) {
				$tempSubRemoved = $v->getRemovedItems();

				if (!is_null($tempSubRemoved) && (!is_array($tempSubRemoved) || count($tempSubRemoved) > 0)) {
					$out[$k] = $tempSubRemoved;
				}
			}
			else if ($v instanceof Base) {
				$removed = $v->getRemovedItems();
				if (!is_null($removed)) {
					$out[$k] = $removed;
				}
			}
			else {
				$out[$k] = $this->rawData[$k];
			}
		}

		return $out;
	}

	/**
	 * @param Base|Container                   $item
	 * @param Nette\Forms\Container            $formContainer
	 * @param                                  $name
	 * @param                                  $parentName
	 * @param Nette\Forms\Rules                $togglingObject
	 * @param array                            $userOptions
	 */
	public function generateForm(Base $item, Nette\Forms\Container &$formContainer, $name, $parentName, $togglingObject, array $userOptions = [])
	{
		$container = is_null($name) ? $formContainer : $formContainer->addContainer($name);

		foreach ($this->getChild() as $childName => $child) {
			$child->generateForm(
				$child,
				$container,
				$childName,
				$parentName . '__' . $name,
				$togglingObject,
				isset($userOptions['child']) && isset($userOptions['child'][$childName]) && is_array($userOptions['child'][$childName]) ? $userOptions['child'][$childName] : []
			);
		}

		$item->applyUserOptions($container, $userOptions);
	}

	/**
	 * @param Nette\Forms\Container $control
	 * @param array                 $options
	 */
	public function applyUserOptions($control, array $options)
	{

	}

	public function update($data)
	{
		$this->rawData = $data;
		$this->init();

		$this->isUpdated = FALSE;
		$this->updated = [];

		foreach ($this->getChild() as $childName => $child) {
			if (is_null($child)) {
				$this->isUpdated = TRUE;

				continue;
			}

			$updated = $child->getUpdated();

			if (!is_null($updated) || (is_array($updated) && count($updated) > 0)) {
				$this->isUpdated = TRUE;
				$this->updated[$childName] = $child->getUpdated();
			}
		}
	}

	public function __toString()
	{
		$data = $this->getRawContent();

		return Nette\Utils\Json::encode($data);
	}
}
