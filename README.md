# Slight Framework

Slight is an MVC framework that will assist you in the development of rest applications, containing tools for routing, authentication and validation for data models.


Example
========

<details><summary>src/router.php</summary>
<p>

```php
<?php
use fw\router\Router;
use src\controller\UserController;

Router::get('user', UserController::class, 'init', [
	'TEST_RULE'
]);

Router::post('user', UserController::class, 'insert');
Router::post('user/new', UserController::class, 'insertOnSession');

Router::put('user/:id/:name', UserController::class, 'update');
```

</p>
</details>

<details><summary>src/controller/UserController.php</summary>
<p>

```php
<?php
namespace src\controller;

use fw\ComponentController;
use src\model\User;

class UserController extends ComponentController {

	public function init() {
		$this->getSession()->destroy();
		
		return "Hello World!";
	}
	
	public function update($id, $name) {
		return (object) [
			'msg' => "User id($id) updated to name: $name"
		];
	}

	public function insert(User $user) {
		$msg;
		if ($this->validate($user)->hasError()) {
			$msg = 'Name is required.';
		} else {
			try {
				$msg = $user->insert() ? 'User inserted.' : 'Error on insert User.';
			} catch (\Exception $e) {
				$this->status(500);
				$msg = $e->getMessage();
			}
		}
		
		return (object) [
			'msg' => $msg
		];
	}
	
	public function insertOnSession(User $user) {
		$this->getSession()->setUserPrincipal($user);
	}
}
```

</p>
</details>

<details><summary>src/model/User.php</summary>
<p>

```php
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

	public static function validationSetup(ValidationSetup $setup): void {
		$setup->register('name', RequiredValidator::class);
	}

	public function getRules(): ?array {
		return [
			'TEST_RULE'
		];
	}
}
```

</p>
</details>

<details><summary>src/validator/RequiredValidator.php</summary>
<p>

```php
<?php
namespace src\validator;

use fw\ComponentController;
use fw\validator\Validator;

final class RequiredValidator implements Validator {

	public static function validate(ComponentController $controller, object $entity, string $name, $value, array $parameters, array &$sharedData): bool {
		return ! empty($value);
	}
}
```

</p>
</details>

<details><summary>view/index.html</summary>
<p>

```html
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SLIGHT</title>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script>
	$(function() {
		// ---======= TESTS =======---
		$.ajaxSetup({
			async : false
		}); // TO TEST

		var user = {
			name : 'Renato'
		};

		// INSERT USER IN DATABASE
		$.post('user', {
			user : user
		}, function(data) {
			console.log(data);
		}).fail(function(data) {
			console.log(data.responseText);
		});

		// UPDATE USER
		$.ajax({
			type : 'PUT',
			url : 'user/10/Gabriel',
			success : function(data) {
				console.log(data);
			}
		});

		// TEST RULE
		$.get('user').fail(function(data) { // UNAUTHORIZED
			console.log(data.responseText);
		});

		$.post('user/new', {
			user : user
		}, function() {
			$.get('user', function(data) {
				console.log(data);
			}); // AUTHORIZED
		});
	});
</script>
</head>
</html>
```

</p>
</details>

License
-------

Slight is licensed under the MIT license.
