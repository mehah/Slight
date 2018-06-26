<?php
namespace src\validator;

use Slight\ComponentController;
use Slight\validator\Validator;

final class RequiredValidator implements Validator {

	public static function validate(ComponentController $controller, object $entity, string $name, $value, array $parameters, array &$sharedData): bool {
		return ! empty($value);
	}
}