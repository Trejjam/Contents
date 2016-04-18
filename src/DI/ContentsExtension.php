<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 26. 10. 2014
 * Time: 17:38
 */

namespace Trejjam\Contents\DI;

use Nette;
use Trejjam\Utils\Contents\Contents;

class ContentsExtension extends Nette\DI\CompilerExtension
{
	const TAG_CONTENTS_SUBTYPES = 'trejjam.contents.subtypes';

	protected $defaults = [
		'configurationDirectory' => '%appDir%/config/contents',
		'logDirectory'           => NULL,
		'subTypes'               => [],
	];

	public function loadConfiguration()
	{
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		Nette\Utils\Validators::assert($config, 'array');

		$contentsArguments = [
			$config['configurationDirectory'],
			$config['logDirectory'],
		];
		if ( !is_null($config['logDirectory'])) {
			$contentsArguments[2] = '@tracy.logger';
		}

		$builder->addDefinition($this->prefix('contents'))
				->setClass('Trejjam\Contents\Contents')
				->setArguments($contentsArguments);

		foreach ($config['subTypes'] as $subTypeName => $subType) {
			$def = $builder->addDefinition($this->prefix('contents.' . md5(Nette\Utils\Json::encode($subType))));
			$def->addSetup('setName', [$subTypeName]);
			$def->setFactory(
				Nette\DI\Compiler::filterArguments(
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
