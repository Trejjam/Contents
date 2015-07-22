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
	Trejjam\Contents\Items;

class UrlSubType extends SubType
{
	/**
	 * @var Nette\Application\LinkGenerator
	 */
	protected $linkGenerator;
	/**
	 * @var Nette\Http\Request
	 */
	protected $request;
	/**
	 * @var Nette\Application\IRouter
	 */
	protected $router;

	public function __construct(Nette\Application\LinkGenerator $linkGenerator, Nette\Http\Request $request, Nette\Application\IRouter $router)
	{
		$this->linkGenerator = $linkGenerator;
		$this->request = $request;
		$this->router = $router;
	}

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
		if ($data == '') {
			$data = '#';
		}
		else {
			$presenterString = $this->getUrl($data);

			if ($presenterString !== FALSE) {
				$data = $presenterString;
			}
		}

		return $data;
	}

	public function removedContent($rawData, $data)
	{
		$presenterString = $this->getUrl($rawData);

		if ($presenterString !== FALSE && $presenterString === $data) {
			return FALSE;
		}

		return parent::removedContent($rawData, $data);
	}

	protected function getUrl($presenterString)
	{
		if (preg_match('~^([\w:]+):(\w*+)(#[a-zA-Z][\w:.-]*)?(?:(?:,[ ]+\{)([a-zA-Z0-9=>\{\},.:\-_ \'"]+)(?:\}))?()\z~', $presenterString, $m)) {
			list(, $presenter, $action, $frag, $rawParameters) = $m;
			if (strlen($frag) > 0 && $frag[0] != '#') {
				$rawParameters = $frag;
				$frag = '';
			}

			try {
				$parameters = Nette\Utils\Json::decode('{' . $rawParameters . '}', Nette\Utils\Json::FORCE_ARRAY);
			}
			catch (Nette\Utils\JsonException $e) {
				$parameters = [];
			}
			if (is_null($parameters)) {
				$parameters = [];
			}

			return $this->linkGenerator->link(
				(empty($presenter) ? '' : $presenter . ':') . $action . $frag, $parameters
			);
		}

		return FALSE;
	}

	public function update(Items\Base $item = NULL, $data)
	{
		try {
			$actualUrl = $this->request->getUrl();
			$urlScript = new Nette\Http\UrlScript($data);
			$urlScript->setScriptPath($actualUrl->getScriptPath());
			if ($urlScript->getHost() == $actualUrl->getHost()) {
				$request = new Nette\Http\Request($urlScript);
				$appRequest = $this->router->match($request);

				if (!is_null($appRequest)) {
					$data = $appRequest->getPresenterName();
					$data .= ':' . $appRequest->getParameter('action');

					$fragment = $urlScript->getFragment();
					if ($fragment != '') {
						$data .= '#' . $fragment;
					}

					$parameters = $appRequest->getParameters();
					unset($parameters['action']);
					if (count($parameters) > 0) {
						$data .= ', ' . Nette\Utils\Json::encode($parameters);
					}
				}
			}
		}
		catch (Nette\InvalidArgumentException $e) {

		}

		return $data;
	}
}
