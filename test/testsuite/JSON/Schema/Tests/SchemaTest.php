<?PHP
namespace JSON\Schema\Tests;

use JSON\Schema;
use JSON\Schema\ValidationException;

class SchemaTest extends \PHPUnit_Framework_TestCase {
  public function testEmptyArray() {
    $schema = new Schema(Schema::TYPE_ARRAY);
    $schema->validate(array());
  }
  
  
  public function testEmptyObject() {
    $schema = new Schema(Schema::TYPE_OBJECT);
    $schema->validate(array());
    $schema->validate(new \stdClass);
  }
  
  
  /**
   * @dataProvider invalidRootObjectTypeProvider
   * @expectedException \JSON\Schema\DescriptionException
   */
  public function testInvalidRootObjectType($type) {
    $schema = new Schema($type);
  }
  
  
  public static function invalidRootObjectTypeProvider() {
    return array(
      array(Schema::TYPE_STRING),
      array(Schema::TYPE_NUMBER),
      array(Schema::TYPE_BOOLEAN),
      array(Schema::TYPE_NULL)
    );
  }
  
  
  public function testStrings() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_STRING);
    });
    
    $data = array('string', 'value', '');
    $schema->validate($data);
    
    try {
      $data = array('string', 14);
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array('string', NULL);
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array('string', false);
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testStringsWithCustomValidation() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_STRING, function($str) {
        return (strlen($str) > 3);
      });
    });
    
    $data = array('asdf', 'jkl;');
    $schema->validate($data);
    
    try {
      $data = array('asdf', 'jkl');
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNumbers() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_NUMBER);
    });
    
    $data = array(15, 15.4);
    $schema->validate($data);
    
    try {
      $data = array(15.4, '15');
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNumbersWithCustomValidation() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_NUMBER, function($num) {
        return ($num > 10);
      });
    });
    
    $data = array(10.1, 11);
    $schema->validate($data);
    
    try {
      $data = array(10.1, 10);
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testBooleans() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_BOOLEAN);
    });
    
    $data = array(true, false);
    $schema->validate($data);
    
    try {
      $data = array(true, 1);
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(true, 'true');
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNull() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_NULL);
    });
    
    $data = array(NULL);
    $schema->validate($data);
    
    try {
      $data = array(NULL, false);
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(NULL, 'NULL');
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testSimpleObject() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('first_name', Schema::TYPE_STRING);
      $obj->includes('last_name', Schema::TYPE_STRING);
      $obj->excludes('name');
      $obj->optional('date_of_birth', Schema::TYPE_STRING, function($date) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
      });
    });
    
    $data = array(
      'first_name' => 'Maik',
      'last_name' => 'Gosenshuis'
    );
    $schema->validate($data);
    
    $data = array(
      'first_name' => 'Maik',
      'last_name' => 'Gosenshuis',
      'date_of_birth' => '1986-09-02'
    );
    $schema->validate($data);
    
    try {
      $data = array(
        'first_name' => 'Maik',
        'last_name' => 'Gosenshuis',
        'name' => 'Maik Gosenshuis'
      );
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(
        'first_name' => 'Maik'
      );
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNestedAggregates() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_OBJECT, function($obj) {
        $obj->includes('first', Schema::TYPE_STRING);
        $obj->includes('last', Schema::TYPE_STRING);
      });
      $obj->includes('nicknames', Schema::TYPE_ARRAY, function($arr) {
        $arr->all(Schema::TYPE_STRING);
      });
    });
    
    $data = array(
      'name' => array(
        'first' => 'Maik',
        'last' => 'Gosenshuis'
      ),
      'nicknames' => array()
    );
    $schema->validate($data);
    
    try {
      $data = array(
        'name' => 'Maik Gosenshuis',
        'nicknames' => array()
      );
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(
        'first' => 'Maik',
        'last' => 'Gosenshuis',
        'nicknames' => array()
      );
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(
        'name' => array(
          'first' => 'Maik',
          'last' => 'Gosenshuis'
        ),
        'nicknames' => ''
      );
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testMultipleTypes() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING | Schema::TYPE_NUMBER | Schema::TYPE_NULL);
    });
    
    $data = array(
      'name' => 'Maik Gosenshuis'
    );
    $schema->validate($data);
    
    $data = array(
      'name' => 15
    );
    $schema->validate($data);
    
    $data = array(
      'name' => NULL
    );
    $schema->validate($data);
    
    try {
      $data = array();
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testMultipleTypesWithCustomValidation() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING | Schema::TYPE_NULL, function($str) {
        return (strlen($str) > 0);
      });
    });
    
    $data = array(
      'name' => 'Maik'
    );
    $schema->validate($data);
    
    $data = array(
      'name' => NULL
    );
    $schema->validate($data);
    
    try {
      $data = array(
        'name' => ''
      );
      $schema->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  /**
   * @expectedException \JSON\Schema\DescriptionException
   */
  public function testNullTypeWithCustomValidation() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_NULL, function() {
        return true;
      });
    });
  }
  
  
  public function testConvertsJSONStrings() {
    $schema = new Schema(Schema::TYPE_OBJECT);
    
    $json_string = "{}";
    $schema->validate($json_string);
  }
  
  
  public function testSupportsPHPObjects() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING);
    });
    
    $json = new \stdClass;
    $json->name = 'Maik';
    
    $schema->validate($json);
    
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_OBJECT, function($obj) {
        $obj->includes('name', Schema::TYPE_STRING);
      });
    });
    
    $user1 = new \stdClass;
    $user1->name = 'Maik';
    
    $user2 = new \stdClass;
    $user2->name = 'Nadja';
    
    $json = array($user1, $user2);
    
    $schema->validate($json);
  }
}
