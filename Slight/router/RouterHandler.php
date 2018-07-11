<?php
namespace Slight\router;

use Slight\http\HttpAuth;

final class RouterHandler {

	private $controllerClass;

	private $methodName;

	private $accessRule;

	private $authClass = null;

	public function __construct(string $controllerClass, string $methodName, ?array $accessRule) {
		$this->controllerClass = $controllerClass;
		$this->methodName = $methodName;
		$this->accessRule = $accessRule;
	}

	public function setAuthClass(string $class): self {
		if ($this->authClass !== null) {
			throw new \Exception('Unable to overwrite authentication class.');
		}
		
		if (! ($parents = class_implements($class)) || ! in_array(HttpAuth::class, $parents)) {
			throw new \Exception('Only classes that implement HttpAuth are allowed.');
		}
		
		$this->authClass = $class;
		
		return $this;
	}

	public function getAuthClass(): ?string {
		return $this->authClass;
	}

	public function getControllerClass(): string {
		return $this->controllerClass;
	}

	public function getMethodName(): string {
		return $this->methodName;
	}

	public function getRules(): ?array {
		return $this->accessRule;
	}
}