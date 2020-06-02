# Simple server side processing for datatables

[![Latest Version on Packagist](https://img.shields.io/packagist/v/esclaudio/datatables.svg?style=flat-square)](https://packagist.org/packages/esclaudio/datatables)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/esclaudio/datatables/run-tests?label=tests)](https://github.com/esclaudio/datatables/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/esclaudio/datatables.svg?style=flat-square)](https://packagist.org/packages/esclaudio/datatables)

## Installation

You can install the package via composer:

```bash
composer require esclaudio/datatables
```

## Usage

``` php
use Esclaudio\Datatables\Datatables;
use Esclaudio\Datatables\Options;
use Esclaudio\Datatables\Database\Connection;

$connection = new Connection(new \PDO(...));
$options = new Options($_GET);

header('Content-Type: application/json');
echo (new Datatables($connection, $options))
    ->from('posts')
    ->join('users', 'users.id', '=', 'posts.created_by')
    ->select([
        'posts.id as id',
        'posts.title as title',
        'users.name as creator',
    ])
    ->toJson(); // {"draw": 1, "recordsTotal": 1, "recordsFiltered": 1, "data": {...}}
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
