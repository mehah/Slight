# Slight Framework

Slight is an MVC framework that will assist you in the development of rest applications, containing tools for routing, authentication, and validation for data models.


Example
========
src/router.php
```php
<?php
use fw\router\Router;
use src\controller\TesteController;

Router::get('teste/:id', TesteController::class, 'teste');
```
src/controller/TesteController.php
```php
<?php
namespace src\controller;

use fw\ComponentController;

class TesteController extends ComponentController {

	public function teste(int $id) {
	// Content
	}
}
```

License
-------

Slight is licensed under the MIT license.
