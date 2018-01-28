<?php
declare(strict_types=1);

namespace Trejjam\Contents\DI;

use Nette;

class ContentsExtension extends Nette\DI\CompilerExtension
{
	const TAG_CONTENTS_SUBTYPES = 'trejjam.contents.subtypes';

	protected $default = [
		'configurationDirectory' => 'config/contents',
		'logDirectory'           => NULL,
		'subTypes'               => [],
	];

	public function loadConfiguration() : void
	{
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();

		$this->default['configurationDirectory'] = $this->getContainerBuilder()->parameters['appDir'] . DIRECTORY_SEPARATOR . $this->default['configurationDirectory'];
		$this->validateConfig($this->default);

		Nette\Utils\Validators::assert($this->config, 'array');

		$contentsArguments = [
			$this->config['configurationDirectory'],
			$this->config['logDirectory'],
		];
		if ( !is_null($this->config['logDirectory'])) {
			$contentsArguments[2] = '@tracy.logger';
		}

		$builder->addDefinition($this->prefix('contents'))
				->setType('Trejjam\Contents\Contents')
				->setArguments($contentsArguments);

		foreach ($this->config['subTypes'] as $subTypeName => $subType) {
			$def = $builder->addDefinition($this->prefix('contents.' . md5(Nette\Utils\Json::encode($subType))));
			$def->addSetup('setName', [$subTypeName]);
			$def->setFactory(
				Nette\DI\Helpers::filterArguments(
					[
						is_string($subType) ? new Nette\DI\Statement($subType) : $subType,
					]
				)[0]
			);
			$def->setAutowired(FALSE);
			$def->setInject(FALSE);
			$def->addTag(self::TAG_CONTENTS_SUBTYPES);
		}
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$contents = $builder->getDefinition($this->prefix('contents'));
		foreach (array_keys($builder->findByTag(self::TAG_CONTENTS_SUBTYPES)) as $serviceName) {
			$contents->addSetup('addSubType', ['@' . $serviceName]);
		}
	}
}
