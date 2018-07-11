<?php
namespace Slight\validator;

interface ValidatorImpl {

	public static function validate(object $controller, object $entity, string $name, $value, array $parameters, array &$sharedData): bool;
}