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

class ListContainer extends Container
{
	const
		LIST_BOX = '__list__',
		DELETE_ITEM = '__delete__';

	protected $removedData = [];

	protected function sanitizeData($data, $first = FALSE)
	{
		$count = isset($this->configuration['count']) ? $this->configuration['count'] : NULL;
		$max = isset($this->configuration['max']) ? $this->configuration['max'] : NULL;
		$child = isset($this->configuration['child'])
			? $this->configuration['child']
			: (isset($this->configuration['listItem']) ? $this->configuration['listItem'] : NULL);

		if (is_null($child)) {
			throw new Contents\DomainException('List has not defined child.', Contents\Exception::INCOMPLETE_CONFIGURATION);
		}
		if (!is_null($count) && !is_null($max)) {
			throw new Contents\DomainException('List has defined \'count\' and \'max\' at same time.', Contents\Exception::COLLISION_CONFIGURATION);
		}

		/** @var Base[] $out */
		$out = $this->data;

		if ($first && is_array($data)) {
			$i = 0;

			$dataNew = [];
			foreach ($data as $k => $v) {
				if (!is_numeric($k)) {
					$this->removedData[$k] = $v;
					continue;
				}
				$dataNew[$i++] = $v;
			}
			$data = $dataNew;
		}

		$i = 0;
		foreach (is_null($data) ? [] : $data as $k => $v) {
			if (
				!is_numeric($k) ||
				(!is_null($count) && $i >= $count) ||
				(!is_null($max) && $i >= $max)
			) {
				$this->removedData[$k] = $v;
				continue;
			}

			if (isset($out[$k]) && isset($this->data[$k]) && !is_null($this->data[$k])) {
				$out[$k]->update(isset($data[$k]) ? $data[$k] : NULL);
			}
			else if (isset($out[$k]) && isset($this->data[$k]) && is_null($out[$k])) {
				$this->removedData[$k] = $v;
				//manually deleted item
			}
			else {
				$out[$k] = Contents\Factory::getItemObject(['type' => 'container', 'child' => $child], $v, $this->subTypes);
			}

			$i++;
		}

		while (!is_null($count) && $i < $count) {
			$out[$i] = Contents\Factory::getItemObject(['type' => 'container', 'child' => $child], NULL, $this->subTypes);

			$i++;
		}

		return $out;
	}

	public function getRemovedItems()
	{
		if (!is_array($this->rawData)) {
			return $this->rawData;
		}
		else {
			$out = [];

			foreach ($this->data as $k => $v) {
				if (is_null($v)) {
					continue;
				}

				$tempSubRemoved = $v->getRemovedItems();

				if (!is_null($tempSubRemoved) && (!is_array($tempSubRemoved) || count($tempSubRemoved) > 0)) {
					$out[$k] = $tempSubRemoved;
				}
			}

			foreach ($this->removedData as $k => $v) {
				$out[$k] = $v;
			}

			return $out;
		}
	}

	/**
	 * @param Base|ListContainer                 $item
	 * @param Nette\Forms\Container              $formContainer
	 * @param                                    $name
	 * @param                                    $parentName
	 * @param Nette\Forms\Rules                  $togglingObject
	 * @param array                              $userOptions
	 */
	public function generateForm(Base $item, Nette\Forms\Container &$formContainer, $name, $parentName, $togglingObject, array $userOptions = [])
	{
		$container = $formContainer->addContainer($name);

		$newParent = NULL;
		if (!isset($item->configuration['count']) && (!isset($item->configuration['max']) || $item->configuration['max'] > count($item->getChild()))) {
			$newParent = $parentName . '__' . $name . Base::NEW_CONTAINER;

			$new = $container->addSubmit(Base::NEW_ITEM_BUTTON, $this->getConfigValue('addItemLabel', 'new', $userOptions));
			$new->setValidationScope(FALSE)
				->setAttribute('id', $newParent . Base::NEW_ITEM_BUTTON);

			$new->onClick[] = function (Nette\Forms\Controls\SubmitButton $button) use ($container) {
				if (count($container->getComponent(Base::NEW_ITEM_CONTENT)->getComponents()) < 1) {
					/** @var Nette\Forms\Controls\SelectBox $listSelect */
					$listSelect = $container->getComponent(self::LIST_BOX);
					$items = $listSelect->getItems();
					$items[$newItemId = count($items)] = Base::NEW_ITEM_BUTTON_LABEL;
					$listSelect->setItems($items);
					$listSelect->setValue($newItemId);

					$button->getParent()->getComponent(Base::NEW_ITEM_CONTENT)->createOne();

					$button->getForm()->onSuccess = [];
				}
			};

			$container->addDynamic($item::NEW_ITEM_CONTENT, function (Nette\Forms\Container $container) use ($item, $newParent, $togglingObject, $userOptions) {
				$child = isset($this->configuration['child'])
					? $this->configuration['child']
					: (isset($this->configuration['listItem']) ? $this->configuration['listItem'] : []);

				/** @var Nette\Forms\Controls\SelectBox $listSelect */
				$listSelect = $container->getParent()->getParent()->getComponent(self::LIST_BOX);
				$items = $listSelect->getItems();

				if (is_null($togglingObject)) {
					$subTogglingObject = $listSelect->addCondition(Nette\Application\UI\Form::EQUAL, count($items) - 1);
				}
				else {
					$subTogglingObject = $togglingObject->addConditionOn($listSelect, Nette\Application\UI\Form::EQUAL, count($items) - 1);
				}

				$newListItem = Contents\Factory::getItemObject(['type' => 'container', 'child' => $child], NULL, $item->subTypes);

				$newListItem->generateForm($newListItem, $container, NULL, $newParent, $subTogglingObject, $userOptions);
			});
		}

		if (!isset($item->configuration['count'])) {
			$deleteContainer = $container->addContainer(ListContainer::DELETE_ITEM);
		}

		$listSelect = new Contents\NoValidateSelectBox($this->getConfigValue('listLabel', 'list', $userOptions), NULL);
		$container[self::LIST_BOX] = $listSelect;
		$listSelect->setOption('id', $parentName . self::LIST_BOX . $name);
		if (!is_null($togglingObject)) {
			$togglingObject->toggle($listSelect->getOption('id'));
			if (!is_null($newParent)) {
				$togglingObject->toggle($newParent . Base::NEW_ITEM_BUTTON);
		}
		}

		$items = [];

		$listHead = $this->getConfigValue('listHead', NULL, $userOptions);

		foreach ($this->getChild() as $childName => $child) {
			if (is_null($togglingObject)) {
				/** @var Nette\Forms\Rules $subTogglingObject */
				$subTogglingObject = $listSelect->addCondition(Nette\Application\UI\Form::EQUAL, $childName);
			}
			else {
				/** @var Nette\Forms\Rules $subTogglingObject */
				$subTogglingObject = $togglingObject->addConditionOn($listSelect, Nette\Application\UI\Form::EQUAL, $childName);
			}

			if (!isset($item->configuration['count'])) {
				$removeButton = $deleteContainer->addCheckbox($childName, $this->getConfigValue('deleteLabel', 'remove item', $userOptions));
				$removeButton->setOption('id', $parentName . '__' . $name . ListContainer::DELETE_ITEM . $childName);
				$subTogglingObject->toggle($removeButton->getOption('id'));
			}

			$child->generateForm(
				$child,
				$container,
				$childName,
				$parentName . '__' . $name,
				$subTogglingObject,
				isset($userOptions['child']) && is_array($userOptions['child']) ? ['child' => $userOptions['child']] : []
			);

			try {
				$itemName = is_null($listHead) ? $childName : Trejjam\Utils\Utils::getValue($child->getContent(), $listHead);
			}
			catch (Trejjam\Utils\LogicException $e) {
				if ($e->getCode() & Trejjam\Utils\Exception::UTILS_KEY_NOT_FOUND) {
					$itemName = $childName;
				}
				else {
					throw $e;
				}
			}

			if (empty($itemName)) {
				$itemName = $childName;
			}

			$items[$childName] = $itemName;
		}

		$postedValue = (string)$listSelect->getRawValue();
		if ($postedValue != '' && !isset($items[$postedValue])) {
			$items[$postedValue] = '';
		}

		$listSelect->setItems($items);
		if (count($items) > 0) {
			$listSelect->setDefaultValue('0');
		}

		$item->applyUserOptions($container, $userOptions);
	}

	public function update($data)
	{
		if (isset($data[ListContainer::LIST_BOX])) {
			unset($data[ListContainer::LIST_BOX]);
		}
		if (isset($data[Base::NEW_ITEM_CONTENT])) {
			foreach ($data[Base::NEW_ITEM_CONTENT] as $newData) {
				$child = isset($this->configuration['child'])
					? $this->configuration['child']
					: (isset($this->configuration['listItem']) ? $this->configuration['listItem'] : NULL);

				$maxDataI = 0;
				for ($i = 0; $i < count($this->data); $i++) {
					if (isset($this->data[$i]) && $i > $maxDataI) {
						$maxDataI = $i;
					}
				}

				$this->data[$maxDataI + 1] = Contents\Factory::getItemObject(['type' => 'container', 'child' => $child], NULL, $this->subTypes);
				$data[$maxDataI + 1] = $newData;
			}

			unset($data[Base::NEW_ITEM_CONTENT]);
		}

		if (isset($data[ListContainer::DELETE_ITEM])) {
			foreach ($data[ListContainer::DELETE_ITEM] as $k => $v) {
				if ($v) {
					unset($data[$k]);
					$this->removedData[$k] = $this->data[$k]->getRawContent();
					$this->data[$k] = NULL;
				}
			}

			unset($data[ListContainer::DELETE_ITEM]);
		}

		parent::update($data);

		return;
	}
}
