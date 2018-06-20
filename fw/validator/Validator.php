<?php
namespace fw\validator;

use fw\ComponentController;

interface Validator {

	public static function validate(ComponentController $controller, object $entity, string $name, $value, array $parameters, array &$sharedData): bool;
}

