<?php
namespace fw;

use fw\http\HttpSession;
use fw\validator\Validation;
use fw\validator\ValidationSetup;

abstract class ComponentController {
	
	public function getSession(): HttpSession {
		return Core::getSessionInstance();
	}

	public function validate(Validation $object, int $type = ValidationSetup::PARTIAL) {
		$validation = new ValidationSetup();
		$object->validationSetup($validation);
		$isPartial = $type === ValidationSetup::PARTIAL;
		
		$hasError = false;
		$sharedData = array();
		foreach ($validation as $nameProp => $validators) {
			foreach ($validators as $validator) {
				$res = ($validator->className)::validate($this, $object, $nameProp, $object->{$nameProp}, $validator->parameters, $sharedData);
				if (! $res) {
					$hasError = true;
					
					if ($isPartial) {
						break;
					}
				}
			}
			
			if ($hasError && $isPartial) {
				break;
			}
		}
		
		return new \fw\ComponentController\Validation($hasError, $sharedData);
	}
	
	protected function status(int $status) {
		http_response_code($status);
	}
}

namespace fw\ComponentController;

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