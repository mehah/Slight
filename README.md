# Slight Framework

Slight is an MVC framework that will assist you in the development of rest applications, containing tools for routing, authentication and validation for data models.


Example
========

<details><summary>src/router.php</summary>
<p>

```php
<?php
use Slight\router\Router;
use src\controller\UserController;

Router::get('user', UserController::class, 'init', [
	'TEST_RULE'
]);

Router::post('user', UserController::class, 'insert');
Router::post('user/put/session', UserController::class, 'putOnSession');

Router::put('user/:id/:name', UserController::class, 'update');
```

</p>
</details>

<details><summary>src/controller/UserController.php</summary>
<p>

```php
<?php
namespace src\controller;

use Slight\ComponentController;
use src\model\User;

class UserController extends ComponentController {

	public function init() {
		$this->getSession()->destroy();
		
		return "Hello World!";
	}

	public function update($id, $name) {
		return "User id($id) updated to name: $name";
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
		
		return $msg;
	}

	public function putOnSession(User $user) {
		$this->getSession()->setUserPrincipal($user);
		
		return "User inserted on session.";
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
```

</p>
</details>

<details><summary>src/validator/RequiredValidator.php</summary>
<p>

```php
<?php
namespace src\validator;

use Slight\ComponentController;
use Slight\validator\Validator;

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
	$(() => {
		const addText = (data) => $('body').append(`<div>${data.responseJSON || data.responseText || data}</div>`);
		const user = {user: {name : 'Renato'}};
		$.ajaxSetup({ async : false });
		
		// ---======= TESTS =======---
		
		// INSERT USER IN DATABASE
		$.post('user', user).then(addText, addText);

		// UPDATE USER
		$.ajax({type: 'PUT', url: 'user/10/Gabriel'}).then(addText, addText);

		// TEST RULE
		{
			$.get('user') // UNAUTHORIZED
				.then(addText, addText);
	
			$.post('user/put/session', user) // PUT USER ON SESSION
				.then(addText, addText);
	
			$.get('user') // NOW IS AUTHORIZED
				.then(addText,addText);
		}
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
