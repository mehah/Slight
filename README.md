# Slight Framework

Slight is an MVC framework that will assist you in the development of rest applications, containing tools for routing, authentication and validation for data models.

## Installation

You can use an already ready structure that is found here: **[Sample Project](https://github.com/mehah/Slight-project)**

Or

If you want to build your own structure, just sweat the [Composer](https://getcomposer.org/).
```shell
composer require slight.mvc/framework:dev-master
```

#### Project Structure (folders)
                
+ src
    * config.php
    * router.php

+ vendor
    + ...
+ view
    * index.html
 <details><summary>.htaccess</summary>
<p>

```.htaccess
RewriteEngine On
RewriteCond %{REQUEST_URI} ^((?!\.).)*$ [NC]
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?$url=$1 [QSA,L]

RewriteEngine On
RewriteCond %{REQUEST_URI} \.*$
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ view/$1 [QSA,L]

RewriteEngine on
RewriteCond %{REQUEST_URI} (/src/|/vendor/|/build/)
RewriteRule ^.*$ /404 [L]
```

</p>
</details>

 <details><summary>index.php</summary>
<p>

```php
<?php
require 'vendor/autoload.php';

Slight\Core::init();
```

</p>
</details>

License
-------

Slight is licensed under the MIT license.
