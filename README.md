# Slight Framework

Slight is a micro framework MVC that will assist you in the development of restful app.


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
