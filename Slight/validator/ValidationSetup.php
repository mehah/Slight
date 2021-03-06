<?php
namespace Slight\validator;

use IteratorAggregate;

final class ValidationSetup implements IteratorAggregate {

	public const PARTIAL = 0;

	public const TOTAL = 1;

	private $setup = [];

	public function register(String $propName, String $class, ...$parameters): ValidationSetup {
		if (! in_array(ValidatorImpl::class, class_implements($class))) {
			throw new \Exception('The ' . $class . ' need to implement ValidatorImpl.');
		}
		
		$setup = new \stdClass();
		$setup->className = $class;
		$setup->parameters = $parameters;
		
		$this->setup[$propName][] = $setup;
		
		return $this;
	}

	public function getIterator() {
		return new \ArrayIterator($this->setup);
	}
}