# FormSynergy.com File Storage for WordPress

This package enables storing, updating, and retrieving stored data using WordPress options API.

## Install using composer
```bash
composer require form-synergy/fs-storage-wp
```

## Include the library
```php
require '/vendor/autoload.php';
```
 
##  Create a new storage
```PHP
$wp_storage = new \FormSynergy\Option_Storage( 'fs-wp' )
```

## Storing data
```PHP
$data = [
    'key','value'
];
$wp_storage->Store('exampleData')->Data($data);
```

## Updating data
```PHP
$data = [
    'key','new value'
];
$wp_storage->Update('exampleData')->Data($data);
```

## Retrieving data
```PHP
$wp_storage->Find('exampleData', 'key')->In('fs-demo');
```
 
