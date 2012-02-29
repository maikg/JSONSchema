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

$schema = new Schema();
// Note that you specify the type of the root object, which should be an object
// or an array.
$schema->describe(Schema::TYPE_OBJECT, function($schema) {
    $schema->includes('name', Schema::TYPE_OBJECT, function($schema) {
        $schema->includes('first', Schema::TYPE_STRING);
        $schema->includes('last', Schema::TYPE_STRING);
    });.
    $schema->excludes('full_name');
    $schema->optional('date_of_birth', Schema::TYPE_STRING, function($dob) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob);
    });
    $schema->includes('nicknames', Schema::TYPE_ARRAY, function($schema) {
        $schema->all(Schema::TYPE_STRING);
    });
});

try {
    $schema->validate($json);
}
catch (ValidationException $e) {
    // Handle the exception.
}


// It's also possible to specify multiple types for a single key.
$schema->describe(Schema::TYPE_OBJECT, function($schema) {
    // This requires the 'name' key to be present, but it can be either NULL or a string.
    $schema->includes('name', Schema::TYPE_STRING | Schema::TYPE_NULL);
});
?>
```

## TODO

*   Make certain logical operations possible, such as "either include `first_name` and `last_name` **OR** include
    `full_name`".
*   Less typing for type constants. Maybe wrap them in `JSON\Schema\Types` and remove the `TYPE_` prefix?). That would
    allow you to do `use JSON\Schema\Types` and then use the types as `Types::STRING`.
*   Allow regular expressions for the string type to be specified directly, without wrapping them up in a function.
