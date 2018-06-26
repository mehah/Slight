<?php
namespace src\model;

use fw\UserPrincipal;
use fw\database\Entity;
use fw\validator\Validation;
use fw\validator\ValidationSetup;
use src\validator\RequiredValidator;

class User extends Entity implements Validation, UserPrincipal {

	public static $table = 'users';

	public static $primaryKey = 'id';

	public $id;

	public $name;

	public function getRules(): ?array {
		return [
			'TEST_RULE'
		];
	}

	public static function validationSetup(ValidationSetup $setup): void {
		$setup->register('name', RequiredValidator::class);
	}
}