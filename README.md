# JSONSchema

JSONSchema is a simple schema validator for JSON formats.

JSONSchema requires PHP 5.3+.

## Installation

JSONSchema uses [Composer](http://packagist.org/about-composer) to manage its description and dependencies (which,
currently, are none). You can [install](https://github.com/composer/composer/blob/master/README.md) Composer system-wide
or just add `composer.phar` to the root of this project after cloning. Afterwards, you simply call `php composer.phar
install` in the project root to install the necessary dependencies.

JSONSchema is not up at [Packagist](http://packagist.org/) at the moment, but you can easily specify JSONSchema as a
dependency in your own `composer.json` by adding the project's GitHub repository to your own `composer.json`.

```json
{
    // ...
    "repositories": [
    {
      "type": "vcs",
      "url": "git://github.com/maikg/JSONSchema.git"
    }
    ],
    "require": {
      "maikg/jsonschema": "*"
    }
    // ...
}
```

## Usage

```php
<?PHP
use JSON\Schema;

$json = ...; // JSON string, array or object instance.

// Note that you specify the type of the root object, which should be an object
// or an array.
$schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
    $obj->includes('name', Schema::TYPE_OBJECT, function($obj) {
        $obj->includes('first', Schema::TYPE_STRING);
        $obj->includes('last', Schema::TYPE_STRING);
    });.
    $obj->excludes('full_name');
    $obj->optional('date_of_birth', Schema::TYPE_STRING, function($dob) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob);
    });
    $obj->includes('nicknames', Schema::TYPE_ARRAY, function($arr) {
        $arr->all(Schema::TYPE_STRING, function($nickname) {
            return (strlen($nickname) > 0);
        });
    });
});

try {
    $schema->validate($json);
}
catch (\JSON\Schema\ValidationException $e) {
    // Handle the exception.
}


// It's also possible to specify multiple types for a single key.
$schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
    // This requires the 'name' key to be present, but it can be either NULL or a string.
    // When specifying a custom validation when using this syntax, it is called always
    // whenever the actual type matches one of the expected types, except when the actual
    // type is NULL.
    $obj->includes('name', Schema::TYPE_STRING | Schema::TYPE_NULL, function($str) {
        // Only called when $root['name'] is a string.
    });
});
?>
```

## TODO

*   Make certain logical operations possible, such as "either include `first_name` and `last_name` **OR** include
    `full_name`".
*   Allow regular expressions for the string type to be specified directly, without wrapping them up in a function.
*   Add more validation methods for the array type:
    *   Specify the expected amount of items in the array (either exact or through a minimum and/or maximum).
    *   Specify the expected type/value at a certain index.
*   Better error messages.