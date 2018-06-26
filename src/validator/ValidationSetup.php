<?php
namespace fw\validator;

use IteratorAggregate;

final class ValidationSetup implements IteratorAggregate {

	public const PARTIAL = 0;

	public const TOTAL = 1;

	private $setup = [];

	public function register(String $propName, String $class, ...$parameters): ValidationSetup {
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