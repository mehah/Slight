<?php
namespace Slight\validator;

final class Validator {

	public function validate(Validation $object, int $type = ValidationSetup::PARTIAL) {
		$validation = new ValidationSetup();
		$object->validationSetup($validation);
		$isPartial = $type === ValidationSetup::PARTIAL;
		
		$hasError = false;
		$sharedData = [];
		foreach ($validation as $nameProp => $validators) {
			foreach ($validators as $validator) {
				$res = ($validator->className)::validate($this, $object, $nameProp, $object->{$nameProp}, $validator->parameters, $sharedData);
				if (! $res) {
					$hasError = true;
					
					if ($isPartial) {
						goto ret;
					}
				}
			}
		}
		
		ret:
		return new \Slight\Validate\Validation($hasError, $sharedData);
	}
}
namespace Slight\Validate;

final class Validation {

	private $hasError;

	private $data;

	public function __construct(bool $hasError, array $data) {
		$this->hasError = $hasError;
		$this->data = $data;
	}

	public function getData(): array {
		return $this->data;
	}

	public function hasError(): bool {
		return $this->hasError;
	}
}