<?php

namespace Test;

use Nette,
	Tester,
	Tester\Assert,
	Trejjam,
	Trejjam\Contents,
	PresenterTester\PresenterTester;

$container = require __DIR__ . '/bootstrap.php';


class ContentsTest extends Tester\TestCase
{
	/**
	 * @var Nette\DI\Container
	 */
	private $container;
	/**
	 * @var Contents\Contents
	 */
	private $contents;

	function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	public function setUp()
	{
		$this->contents = $this->container->getService("contents.contents");
	}

	function testContents1()
	{
		/** @var Contents\Items\Container $dataObject */
		$dataObject = $this->contents->getDataObject('testContent', NULL);

		Assert::same([
			'a' => [
				'a' => '#',
				'b' => '',
				'c' => [
					['a' => '', 'b' => ['a' => '']],
					['a' => '', 'b' => ['a' => '']],
				],
			],
			'b' => [],
			'c' => FALSE,
		], $dataObject->getContent());

		Assert::equal(Nette\Utils\ArrayHash::from([
			'a' => [
				'a' => '#',
				'b' => '',
				'c' => [
					['a' => '', 'b' => ['a' => '']],
					['a' => '', 'b' => ['a' => '']],
				],
			],
			'b' => [],
			'c' => FALSE,
		]), $dataObject->getContent(TRUE));

		Assert::throws(function () {
			$this->contents->getDataObject('testContent', 'abcd');
		}, \Trejjam\Contents\LogicException::class, NULL, Trejjam\Contents\Exception::CONTENTS_JSON_DECODE);

		Assert::throws(function () {
			$this->contents->getDataObject('notExistTestContent', 'abcd');
		}, \Trejjam\Contents\InvalidArgumentException::class, NULL, Trejjam\Contents\Exception::CONTENTS_MISSING_CONFIGURATION);

		/**
		 *
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form = $this->contents->createForm($dataObject, [
			'child' => [
				'a' => [
					'child' => [
						'a' => [
							'class' => 'foo',
						],
					],
				],
			],
		], 'testContent.update');
		$presenterPost->addComponent($form, 'contentsForm');

		$tester->run();

		ob_start();
		$form->render();
		$formHtml = ob_get_clean();

		$formDom = Tester\DomQuery::fromHtml($formHtml);

		Assert::true($formDom->has('form input.foo[name=\'root[a][a]\']'));

		Assert::same('{"a":{"a":null,"b":null,"c":[{"a":null,"b":{"a":null}},{"a":null,"b":{"a":null}}]},"b":[],"c":null}', $dataObjectJson = (string)$dataObject);

		/** @var Contents\Items\Container $dataObject */
		$dataObject2 = $this->contents->getDataObject('testContent', $dataObjectJson);

		Assert::same($dataObjectJson, (string)$dataObject2);
	}
	function testContents2()
	{
		/** @var Contents\Items\Container $dataObject */
		$dataObject = $this->contents->getDataObject('testContent', Nette\Utils\Json::encode([
			'a' => [
				'a' => 'bcd',
				'c' => [
					'abc' => 'foo',
					0     => 'foo2',
					1     => [
						'b' => ['a' => 'de', 'b' => 'foo']
					],
				],
			],
			'c' => 'foo',
		]));

		Assert::same([
			'a' => [
				'a' => 'bcd',
				'b' => '',
				'c' => [
					0 => ['a' => '', 'b' => ['a' => '']],
					1 => ['a' => '', 'b' => ['a' => 'de']],
				],
			],
			'b' => [],
			'c' => FALSE,
		], $dataObject->getContent());

		Assert::same([
			'a' => [
				'a' => 'bcd',
				'b' => NULL,
				'c' => [
					['a' => NULL, 'b' => ['a' => NULL]],
					['a' => NULL, 'b' => ['a' => 'de']],
				],
			],
			'b' => [],
			'c' => 'foo',
		], $dataObject->getRawContent());

		Assert::same([
			'a' => [
				'c' => [
					'foo2',
					['b' => ['b' => 'foo']],
					'abc' => 'foo', //non numeric key removed directly!
				],
			],
			'c' => 'foo',
		], $dataObject->getRemovedItems());
	}
	function testContents3()
	{
		/** @var Contents\Items\Container $dataObject */
		$dataObject = $this->contents->getDataObject('testContent', [
			'a' => ['a' => 'Homepage:default#abcd, {"a":"b"}', 'c' => [['b' => ['a' => ['a' => 'de'], 'b' => 'foo']], 'abcd']],
			'b' => [
				['a' => '#'],
				['a' => ''],
				['a' => 'Homepage:default#abcd'],
				['a' => 'Homepage:default, {"a":"b"}'],
				['a' => 'Homepage:default'],
				['a' => 'Homepage:'],
			],
			'c' => TRUE,
		]);

		Assert::same([
			'a' => [
				'a' => 'http://localhost.tld/?a=b#abcd',
				'b' => '',
				'c' => [
					['a' => '', 'b' => ['a' => '']],
					['a' => '', 'b' => ['a' => '']],
				],
			],
			'b' => [
				['a' => '#'],
				['a' => '#'],
				['a' => 'http://localhost.tld/#abcd'],
				['a' => 'http://localhost.tld/?a=b'],
				['a' => 'http://localhost.tld/'],
				['a' => 'http://localhost.tld/'],
			],
			'c' => TRUE,
		], $dataObject->getContent());

		Assert::same([
			'a' => [
				'a' => 'Homepage:default#abcd, {"a":"b"}',
				'b' => NULL,
				'c' => [
					['a' => NULL, 'b' => ['a' => ['a' => 'de']]],
					['a' => NULL, 'b' => ['a' => NULL]],
				],
			],
			'b' => [
				['a' => '#'],
				['a' => ''],
				['a' => 'Homepage:default#abcd'],
				['a' => 'Homepage:default, {"a":"b"}'],
				['a' => 'Homepage:default'],
				['a' => 'Homepage:'],
			],
			'c' => TRUE,
		], $dataObject->getRawContent());

		Assert::same([
			'a' => [
				'c' => [
					[
						'b' => [
							'a' => ['a' => 'de'], 'b' => 'foo'
						],
					],
					'abcd',
				],
			],
			'b' => [
				1 => ['a' => ''],
			],
		], $dataObject->getRemovedItems());

		Assert::same('{"a":{"a":"Homepage:default#abcd, {\"a\":\"b\"}","b":null,"c":[{"a":null,"b":{"a":{"a":"de"}}},{"a":null,"b":{"a":null}}]},"b":[{"a":"#"},{"a":""},{"a":"Homepage:default#abcd"},{"a":"Homepage:default, {\"a\":\"b\"}"},{"a":"Homepage:default"},{"a":"Homepage:"}],"c":true}', $dataObjectJson = (string)$dataObject);

		/** @var Contents\Items\Container $dataObject */
		$dataObject2 = $this->contents->getDataObject('testContent', $dataObjectJson);

		Assert::same($dataObjectJson, (string)$dataObject2);
	}
	function testContents4()
	{
		/** @var Contents\Items\Container $dataObject */
		$dataObject = $this->contents->getDataObject('testContent', [
			'a' => [
				'a' => 'Homepage:default#abcd, {"a":"b"}',
				'c' => [
					['b' => ['a' => ['a' => 'de'], 'b' => 'foo']],
					'abcd',
				],
			],
			'b' => [
				['a' => '#'],
				['a' => ''],
				['a' => 'Homepage:default#abcd'],
				['a' => 'Homepage:default, {"a":"b"}'],
				['a' => 'Homepage:default'],
				['a' => 'Homepage:'],
			],
			'c' => TRUE,
		]);

		Assert::same([
			'a' => [
				'a' => 'http://localhost.tld/?a=b#abcd',
				'b' => '',
				'c' => [
					['a' => '', 'b' => ['a' => '']],
					['a' => '', 'b' => ['a' => '']],
				],
			],
			'b' => [
				['a' => '#'],
				['a' => '#'],
				['a' => 'http://localhost.tld/#abcd'],
				['a' => 'http://localhost.tld/?a=b'],
				['a' => 'http://localhost.tld/'],
				['a' => 'http://localhost.tld/'],
			],
			'c' => TRUE,
		], $dataObject->getContent());

		Assert::same([
			'a' => [
				'a' => 'Homepage:default#abcd, {"a":"b"}',
				'b' => NULL,
				'c' => [
					['a' => NULL, 'b' => ['a' => ['a' => 'de']]],
					['a' => NULL, 'b' => ['a' => NULL]],
				],
			],
			'b' => [
				['a' => '#'],
				['a' => ''],
				['a' => 'Homepage:default#abcd'],
				['a' => 'Homepage:default, {"a":"b"}'],
				['a' => 'Homepage:default'],
				['a' => 'Homepage:'],
			],
			'c' => TRUE,
		], $dataObject->getRawContent());

		Assert::same([
			'a' => [
				'c' => [
					[
						'b' => [
							'a' => ['a' => 'de'], 'b' => 'foo'
						],
					],
					'abcd',
				],
			],
			'b' => [
				1 => ['a' => ''],
			],
		], $dataObject->getRemovedItems());

		Assert::same('{"a":{"a":"Homepage:default#abcd, {\"a\":\"b\"}","b":null,"c":[{"a":null,"b":{"a":{"a":"de"}}},{"a":null,"b":{"a":null}}]},"b":[{"a":"#"},{"a":""},{"a":"Homepage:default#abcd"},{"a":"Homepage:default, {\"a\":\"b\"}"},{"a":"Homepage:default"},{"a":"Homepage:"}],"c":true}', $dataObjectJson = (string)$dataObject);

		/** @var Contents\Items\Container $dataObject */
		$dataObject2 = $this->contents->getDataObject('testContent', $dataObjectJson);

		Assert::same($dataObjectJson, (string)$dataObject2);

		/**
		 *
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form = $this->contents->createForm($dataObject, [
			'fields' => [
				'a' => [
					'child' => [
						'a' => [
							'class' => 'foo',
						],
					],
				],
			],
		], 'testContent.update', ['a', 'c']);
		$presenterPost->addComponent($form, 'contentsForm');

		$tester->run();

		ob_start();
		$form->render();
		$formHtml = ob_get_clean();

		$formDom = Tester\DomQuery::fromHtml($formHtml);

		Assert::true($formDom->has('form input.foo[name=\'a[a]\']'));
		Assert::false($formDom->has('form input[name=\'b\']'));
		Assert::true($formDom->has('form input[name=\'c\']'));
	}

	/**
	 * @return Contents\Items\ListContainer $listItem
	 */
	function createList()
	{

		return Contents\Factory::getItemObject([
			'type'     => 'list',
			'listHead' => 'name',
			'listItem' => [
				'name'    => 'text',
				'content' => 'text',
			],
		], [
			['content' => 'abcd'],
			['content' => 'abcd', 'name' => 'abcdef'],
			['content' => 'abcd', 'name' => 1],
		]);
	}

	function testList1()
	{
		$listItem = $this->createList();

		Assert::same([0, 1, 2], array_keys($listItem->getChild()));
		Assert::same([
			['name' => '', 'content' => 'abcd'],
			['name' => 'abcdef', 'content' => 'abcd'],
			['name' => 1, 'content' => 'abcd'],
		], $listItem->getContent());

		Assert::throws(function () {
			/** @var Contents\Items\ListContainer $containerItem */
			$containerItem = Contents\Factory::getItemObject([
				'type' => 'list',
			], [['content' => 'abcd']]);
		}, Trejjam\Contents\DomainException::class, NULL, Trejjam\Contents\Exception::INCOMPLETE_CONFIGURATION);


		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form = $this->contents->createForm($listItem, [], 'testContent.update');
		$presenterPost->addComponent($form, 'contentsForm');

		$tester->run();

		$form->setValues([
			'root' => [
				['name' => 'new_value', 'content' => 'new_value2'],
				2 => ['name' => '1', 'content' => 'abcd'],
			],
		]);
		$form->onSuccess($form);

		ob_start();
		$form->render();
		$formHtml = ob_get_clean();

		$formDom = Tester\DomQuery::fromHtml($formHtml);

		Assert::true($formDom->has('form'));
		Assert::true($formDom->has('form input[name=\'root[' . Contents\Items\ListContainer::NEW_ITEM_BUTTON . ']\']'));
		Assert::true($formDom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']'));
		Assert::true($formDom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']'));
		Assert::same('abcdef', (string)$formDom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']')[0]);
		Assert::true($formDom->has('form input[id=__root__new_container__button__]'));

		Assert::same([
			['name' => 'new_value', 'content' => 'new_value2',],
			['name' => 'abcdef', 'content' => 'abcd'],
			['name' => '1', 'content' => 'abcd'],
		], $listItem->getRawContent());
		Assert::same([
			['name' => 'new_value', 'content' => 'new_value2',],
			['name' => 'abcdef', 'content' => 'abcd'],
			['name' => '1', 'content' => 'abcd'],
		], $listItem->getContent());

		Assert::same([
			['name' => Contents\Items\Base::EMPTY_VALUE, 'content' => 'abcd'],
		], $listItem->getUpdated());

	}
	function testList12()
	{
		$listItem2 = $this->createList();
		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form2 = $this->contents->createForm($listItem2, [], 'testContent.update');
		$presenterPost->addComponent($form2, 'contentsForm2');

		$tester->run();

		/** @var Nette\Forms\Controls\SubmitButton $newButton */
		$newButton = $form2->getComponent('root')
						   ->getComponent(Contents\Items\Base::NEW_ITEM_BUTTON);

		$onSuccess = $form2->onSuccess;
		$newButton->onClick($newButton);
		$form2->onSuccess = $onSuccess;

		ob_start();
		$form2->render();
		$form2Html = ob_get_clean();

		$form2Dom = Tester\DomQuery::fromHtml($form2Html);

		Assert::true($form2Dom->has('form'));
		Assert::true($form2Dom->has('form input[name=\'root[' . Contents\Items\ListContainer::NEW_ITEM_BUTTON . ']\']'));
		Assert::true($form2Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']'));
		Assert::same('0', (string)$form2Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']')[0]);
		Assert::true($form2Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']'));
		Assert::same('abcdef', (string)$form2Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']')[0]);
		Assert::true($form2Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'2\']'));
		Assert::same(Contents\Items\ListContainer::NEW_ITEM_BUTTON_LABEL, (string)$form2Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'3\']')[0]);

		$form2->setValues([
			'root' => [
				Contents\Items\ListContainer::LIST_BOX         => '0',
				Contents\Items\ListContainer::NEW_ITEM_CONTENT => [
					[
						'name'    => 'new_item_name',
						'content' => 'new_item_content',
					],
				],
			],
		]);

		Assert::same([
			['name' => '', 'content' => 'abcd',],
			['name' => 'abcdef', 'content' => 'abcd'],
			['name' => 1, 'content' => 'abcd'],
		], $listItem2->getContent());

		$form2->onSuccess($form2);

		Assert::same([
			['name' => '', 'content' => 'abcd',],
			['name' => 'abcdef', 'content' => 'abcd'],
			['name' => 1, 'content' => 'abcd'],
			['name' => 'new_item_name', 'content' => 'new_item_content'],
		], $listItem2->getContent());

		Assert::same([
			['name' => Contents\Items\Base::EMPTY_VALUE],
			3 => ['name' => Contents\Items\Base::EMPTY_VALUE, 'content' => Contents\Items\Base::EMPTY_VALUE],
		], $listItem2->getUpdated());

		$form22 = $this->contents->createForm($listItem2, [], 'testContent.update');
		$presenterPost->addComponent($form22, 'contentsForm22');

		ob_start();
		$form22->render();
		$form22Html = ob_get_clean();

		$form22Dom = Tester\DomQuery::fromHtml($form22Html);

		Assert::true($form22Dom->has('form'));
		Assert::true($form22Dom->has('form input[name=\'root[' . Contents\Items\ListContainer::NEW_ITEM_BUTTON . ']\']'));
		Assert::true($form22Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']'));
		Assert::same('0', (string)$form2Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']')[0]);
		Assert::true($form22Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']'));
		Assert::same('abcdef', (string)$form2Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']')[0]);
		Assert::true($form22Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'2\']'));
		Assert::same(Contents\Items\ListContainer::NEW_ITEM_BUTTON_LABEL, (string)$form2Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'3\']')[0]);

	}
	function testList13()
	{
		$listItem3 = $this->createList();
		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();

		$form3 = $this->contents->createForm($listItem3, [
			'listHead' => 'not.exist.key',
		], 'testContent.update');
		$presenterPost->addComponent($form3, 'contentsForm3');

		$tester->run();

		ob_start();
		$form3->render();
		$form3Html = ob_get_clean();

		$form3Dom = Tester\DomQuery::fromHtml($form3Html);

		Assert::true($form3Dom->has('form'));
		Assert::true($form3Dom->has('form input[name=\'root[' . Contents\Items\ListContainer::NEW_ITEM_BUTTON . ']\']'));
		Assert::true($form3Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']'));
		Assert::true($form3Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']'));

		Assert::throws(function () use ($listItem3) {
			$this->contents->createForm($listItem3, [], 'testContent.update', [
				'notExistKey'
			]);
		}, Contents\LogicException::class, NULL, Contents\Exception::CHILD_NOT_EXIST);

	}
	function testList14()
	{
		$listItem4 = $this->createList();

		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form4 = $this->contents->createForm($listItem4, [], 'testContent.update');
		$presenterPost->addComponent($form4, 'contentsForm');

		$tester->run();

		$form4->setValues([
			'root' => [
				Contents\Items\ListContainer::DELETE_ITEM => [
					0 => FALSE,
					1 => TRUE,
				],
			],
		]);
		$form4->onSuccess($form4);

		ob_start();
		$form4->render();
		$form4Html = ob_get_clean();

		$form4Dom = Tester\DomQuery::fromHtml($form4Html);

		Assert::true($form4Dom->has('form'));
		Assert::true($form4Dom->has('form input[name=\'root[' . Contents\Items\ListContainer::NEW_ITEM_BUTTON . ']\']'));
		Assert::true($form4Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']'));
		Assert::true($form4Dom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']'));
		Assert::same('abcdef', (string)$form4Dom->find('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'1\']')[0]);
		Assert::true($form4Dom->has('form input[id=__root__new_container__button__]'));

		Assert::same([
			['name' => '', 'content' => 'abcd'],
			2 => ['name' => 1, 'content' => 'abcd'],
		], $listItem4->getRawContent());
		Assert::same([
			['name' => '', 'content' => 'abcd'],
			2 => ['name' => 1, 'content' => 'abcd'],
		], $listItem4->getContent());
		Assert::same([
			1 => ['name' => 'abcdef', 'content' => 'abcd'],
		], $listItem4->getRemovedItems());

		Assert::same([
			['name' => Contents\Items\Base::EMPTY_VALUE],
		], $listItem4->getUpdated());
	}
	function testList2()
	{
		/** @var Contents\Items\ListContainer $listItem */
		$listItem = Contents\Factory::getItemObject([
			'type'  => 'list',
			'count' => 3,
			'child' => [
				'name'    => 'text',
				'content' => 'text',
			],
		], [
			['content' => 'abcd'],
			['content' => 'abcd', 'name' => 'abcdef'],
		]);

		Assert::same([0, 1, 2], array_keys($listItem->getChild()));
		Assert::same([
			['name' => '', 'content' => 'abcd'],
			['name' => 'abcdef', 'content' => 'abcd'],
			['name' => '', 'content' => ''],
		], $listItem->getContent());

		Assert::throws(function () {
			/** @var Contents\Items\ListContainer $containerItem */
			$containerItem = Contents\Factory::getItemObject([
				'type' => 'list',
			], [['content' => 'abcd']]);
		}, Contents\DomainException::class, NULL, Contents\Exception::INCOMPLETE_CONFIGURATION);
	}
	function testList3()
	{
		/** @var Contents\Items\ListContainer $listItem */
		$listItem = Contents\Factory::getItemObject([
			'type'     => 'list',
			'max'      => 1,
			'listItem' => [
				'name'    => 'text',
				'content' => 'text',
			],
		], [
			['content' => 'abcd'],
			['content' => 'abcd', 'name' => 'abcdef'],
		]);

		Assert::same([0], array_keys($listItem->getChild()));
		Assert::same([
			['name' => '', 'content' => 'abcd'],
		], $listItem->getContent());

		Assert::same([
			1 => ['content' => 'abcd', 'name' => 'abcdef'],
		], $listItem->getRemovedItems());

		Assert::throws(function () {
			/** @var Contents\Items\ListContainer $containerItem */
			$containerItem = Contents\Factory::getItemObject([
				'type'     => 'list',
				'max'      => 2,
				'count'    => 3,
				'listItem' => [

				],
			], [['content' => 'abcd']]);
		}, Contents\DomainException::class, NULL, Contents\Exception::COLLISION_CONFIGURATION);

		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form = $this->contents->createForm($listItem, [
			'listHead' => 'name',
		], 'testContent.update');
		$presenterPost->addComponent($form, 'contentsForm');

		$tester->run();

		ob_start();
		$form->render();
		$formHtml = ob_get_clean();

		$formDom = Tester\DomQuery::fromHtml($formHtml);

		Assert::true($formDom->has('form'));
		Assert::false($formDom->has('form input[name=\'root[' . Contents\Items\ListContainer::NEW_ITEM_BUTTON . ']\']'));
		Assert::true($formDom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'0\']'));
		Assert::false($formDom->has('form select[name=\'root[' . Contents\Items\ListContainer::LIST_BOX . ']\'] option[value=\'abcd\']'));

		$form->setValues([
			'root' => [
				['name' => 'new_value', 'content' => 'new_value2']
			],
		]);
		$form->onSuccess($form);

		Assert::same([
			['name' => 'new_value', 'content' => 'new_value2',]
		], $listItem->getRawContent());
		Assert::same([
			['name' => 'new_value', 'content' => 'new_value2',]
		], $listItem->getContent());

		Assert::same('[{"name":"new_value","content":"new_value2"}]', (string)$listItem);
	}

	function testContainer1()
	{
		/** @var Contents\Items\Container $containerItem */
		$containerItem = Contents\Factory::getItemObject([
			'type'  => 'container',
			'child' => [
				'name'    => 'text',
				'content' => 'text',
			],
		], ['content' => 'abcd', 'foo' => 'abcd']);

		Assert::same(['name', 'content'], array_keys($containerItem->getChild()));
		Assert::same(['name' => '', 'content' => 'abcd'], $containerItem->getContent());
		Assert::same(['foo' => 'abcd'], $containerItem->getRemovedItems());

		Assert::throws(function () {
			/** @var Contents\Items\Container $containerItem */
			$containerItem = Contents\Factory::getItemObject([
				'type' => 'container',
			], ['content' => 'abcd']);
		}, Contents\DomainException::class, NULL, Contents\Exception::INCOMPLETE_CONFIGURATION);
	}
	function testContainer2()
	{
		/** @var Contents\Items\Container $containerItem */
		$containerItem = Contents\Factory::getItemObject([
			'type'  => 'container',
			'child' => [
				'name'    => 'text',
				'content' => [
					'type'  => 'container',
					'child' => [
						'foo'  => 'text',
						'text' => 'text',
					],
				],
			],
		], ['content' => ['text' => 'abcd', 'oldFoo' => 'efgh']]);

		Assert::same(['name', 'content'], array_keys($containerItem->getChild()));
		Assert::same(['name' => '', 'content' => ['foo' => '', 'text' => 'abcd']], $containerItem->getContent());
		Assert::same(['content' => ['oldFoo' => 'efgh']], $containerItem->getRemovedItems());

		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$form = $this->contents->createForm($containerItem, [], 'testContent.update');
		$presenterPost->addComponent($form, 'contentsForm');

		$tester->run();

		$form->setValues([
			'root' => [
				'name'    => 'new_value',
				'content' => [
					'text' => 'new_value_3',
				],
			],
		]);
		$form->onSuccess($form);

		Assert::same([
			'name'    => 'new_value',
			'content' => [
				'foo'  => '',
				'text' => 'new_value_3',
			],
		], $containerItem->getRawContent());
		Assert::same([
			'name'    => 'new_value',
			'content' => [
				'foo'  => '',
				'text' => 'new_value_3',
			],
		], $containerItem->getContent());

		Assert::same('{"name":"new_value","content":{"foo":"","text":"new_value_3"}}', (string)$containerItem);
	}

	function testText1()
	{
		/** @var Contents\Items\Text $textItem */
		$textItem = Contents\Factory::getItemObject('text', 'abd');

		Assert::same('abd', $textItem->getContent());

		Assert::throws(function () {
			Contents\Factory::getItemObject('', ['abd']);
		}, Contents\InvalidArgumentException::class, NULL, Contents\Exception::UNKNOWN_ITEM_TYPE);

		$tester = new PresenterTester($this->container->getByType('\Nette\Application\IPresenterFactory'));
		$tester->setPresenter('Homepage');
		/** @var  $presenter */
		$presenter = $tester->getPresenterComponent();
		$form = $this->contents->createForm($textItem, [], 'testContent.update');

		Assert::true($form instanceof Nette\Application\UI\Form);
		$presenter->addComponent($form, 'contentsForm');
		$tester->run();

		ob_start();
		$form->render();
		$formHtml = ob_get_clean();

		$formDom = Tester\DomQuery::fromHtml($formHtml);

		Assert::true($formDom->has('form'));
		Assert::true($formDom->has('form input[name="root"]'));


		$form = $this->contents->createForm($textItem, [], 'testContent.update');
		$form->addSubmit('send', 'send');
		Assert::true($form instanceof Nette\Application\UI\Form);

		/**
		 * @var $tester        PresenterTester
		 * @var $presenterPost Nette\Application\UI\Presenter
		 */
		list($tester, $presenterPost) = $this->getPresenter();
		$tester->setHandle('contentsForm-submit');
		$tester->setParams(['submit' => 'send']);
		$tester->setPost([
			'do'     => 'contentsForm-submit',
			'root'   => 'new_value',
			'send'   => 'send',
			'submit' => '',
			//Nette\Application\UI\Form::PROTECTOR_ID => '',//$protector->getControl()->attrs['value'],
		]);
		$presenterPost->addComponent($form, 'contentsForm');

		$tester->run();

		//Assert::truthy($form->isSubmitted());

		$form->setValues([
			'root' => 'new_value',
			//Nette\Application\UI\Form::PROTECTOR_ID => $protector->getControl()->attrs['value'],
			'send' => 'send',
		]);
		$form->onSuccess($form);

		Assert::same('new_value', $textItem->getRawContent());
		Assert::same('new_value', $textItem->getContent());

		Assert::same('new_value', (string)$textItem);
	}
	function testText2()
	{
		$textItem = Contents\Factory::getItemObject(['type' => 'text'], ['abd']);
		Assert::same('', $textItem->getContent());
		Assert::same(['abd'], $textItem->getRemovedItems());
	}

	/**
	 * @param string $presenterName
	 * @return array(PresenterTester, Nette\Application\UI\Presenter)
	 */
	function getPresenter($presenterName = 'Homepage')
	{
		$tester = new PresenterTester($this->container->getByType('\Nette\Application\IPresenterFactory'));
		$tester->clean();
		$tester->setPresenter($presenterName);

		$presenter = $tester->getPresenterComponent();

		//$presenter->invalidLinkMode = 0;

		return [$tester, $presenter];
	}
}

$test = new ContentsTest($container);
$test->run();
