<?php
namespace src\model;

use Slight\UserPrincipal;
use Slight\database\Entity;
use Slight\validator\Validation;
use Slight\validator\ValidationSetup;
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