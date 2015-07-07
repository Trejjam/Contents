<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 19.6.15
 * Time: 20:17
 */

namespace Trejjam\Contents;


use Nette,
	Nette\Application\UI,
	Tracy,
	Trejjam;

class Contents
{
	/**
	 * @var  string
	 */
	protected $configurationDirectory;
	/**
	 * @var string
	 */
	protected $logDirectory;
	/**
	 * @var Tracy\ILogger
	 */
	protected $logger;

	/**
	 * @var SubTypes\SubType[]
	 */
	protected $subTypes = [];

	public function __construct($configurationDirectory, $logDirectory = NULL, Tracy\ILogger $logger = NULL)
	{
		$this->configurationDirectory = $configurationDirectory;
		$this->logDirectory = $logDirectory;
		$this->logger = $logger;
	}

	public function addSubType(SubTypes\SubType $subType)
	{
		$this->subTypes[$subType->getName()] = $subType;
	}

	protected function getFilePath($name)
	{
		return $this->configurationDirectory . '/' . $name . '.neon';
	}
	/**
	 * @param $name
	 * @return bool
	 */
	protected function hasConfiguration($name)
	{
		return is_file($this->getFilePath($name));
	}

	/**
	 * @param $name
	 * @return array
	 */
	protected function loadConfiguration($name)
	{
		if (!$this->hasConfiguration($name)) {
			throw new InvalidArgumentException("Configuration with name '$name' not exist.", Exception::MISSING_CONFIGURATION);
		}

		return Nette\Neon\Neon::decode(file_get_contents($this->getFilePath($name)));
	}

	public function getDataObject($name, $data)
	{
		$configuration = $this->loadConfiguration($name);

		if (!is_array($data) && !is_object($data)) {
			try {
				$data = Nette\Utils\Json::decode($data, Nette\Utils\Json::FORCE_ARRAY);
			}
			catch (Nette\Utils\JsonException $e) {
				throw new LogicException('Json::decode problem', Exception::JSON_DECODE, $e);
			}
		}

		$out = Factory::getItemObject(['type' => 'container', 'child' => $configuration], $data, $this->subTypes);

		$this->logObject($out->getRemovedItems(), $name);

		return $out;
	}

	/**
	 * @param Items\Base  $itemContainer
	 * @param array       $userOptions
	 * @param null|string $contentName
	 * @param array       $fields
	 * @return Nette\Application\UI\Form
	 */
	public function createForm(Items\Base $itemContainer, $userOptions = [], $contentName = NULL, array $fields = NULL)
	{
		$form = new UI\Form;

		if (is_null($fields)) {
			$this->createFieldContainer($itemContainer, $form, $userOptions, 'root', $contentName);
		}
		else if ($itemContainer instanceof Items\Container) {
			$itemContainerChild = $itemContainer->getChild();

			foreach ($fields as $v) {
				if (!isset($itemContainerChild[$v])) {
					throw new LogicException("Field '$v' not exist in given container", Exception::CHILD_NOT_EXIST);
				}

				$this->createFieldContainer($itemContainerChild[$v], $form, isset($userOptions['fields']) && isset($userOptions['fields'][$v]) ? $userOptions['fields'][$v] : [], $v, $contentName);
			}
		}

		$form->addProtection();

		return $form;
	}

	protected function createFieldContainer(Items\Base $itemContainer, UI\Form &$form, $userOptions = [], $field, $contentName = NULL)
	{
		$itemContainer->generateForm($itemContainer, $form, $field, '', NULL, $userOptions);

		$form->onSuccess[] = function (UI\Form $form) use ($itemContainer, $field, $contentName) {
			return $this->proceedEditForm($form, $itemContainer, $field, $contentName);
		};
	}

	public function proceedEditForm(UI\Form $form, Items\Base $itemContainer, $field, $contentName)
	{
		$values = $form->getValues(TRUE);

		$itemContainer->update($values[$field]);

		$this->logObject($itemContainer->getUpdated(), $contentName);

		return TRUE;
	}

	protected function logObject($object, $fileName = NULL)
	{
		if (!is_null($this->logDirectory) && !is_null($this->logger) && ((!is_array($object) && $object != '') || count($object) > 0)) {
			@mkdir($this->logger->directory . '/' . $this->logDirectory . '/', 0770);
			chmod($this->logger->directory . '/' . $this->logDirectory . '/', 0770);
			$fileName = is_null($fileName) ? __CLASS__ : str_replace('/', '__', $fileName);
			$this->logger->log(var_export($object, TRUE), $this->logDirectory . '/' . $fileName);
		}
	}
}
