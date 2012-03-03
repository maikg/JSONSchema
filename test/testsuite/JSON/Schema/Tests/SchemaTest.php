<?PHP
namespace JSON\Schema\Tests;

use JSON\Schema;
use JSON\Schema\ValidationException;

class SchemaTest extends \PHPUnit_Framework_TestCase {
  private function assertValid(Schema $schema, $data) {
    $this->assertTrue($schema->validate($data));
    $this->assertCount(0, $schema->getValidationErrors());
  }
  
  
  private function assertNotValid(Schema $schema, $data, $validation_error_count) {
    $this->assertFalse($schema->validate($data));
    $this->assertCount($validation_error_count, $schema->getValidationErrors());
  }
  
  
  public function testEmptyArray() {
    $schema = new Schema(Schema::TYPE_ARRAY);
    $this->assertValid($schema, array());
  }
  
  
  public function testEmptyObject() {
    $schema = new Schema(Schema::TYPE_OBJECT);
    $this->assertValid($schema, array());
    $this->assertValid($schema, new \stdClass);
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
    $this->assertValid($schema, $data);
    
    $data = array('string', 14);
    $this->assertNotValid($schema, $data, 1);
    
    $data = array('string', NULL);
    $this->assertNotValid($schema, $data, 1);
    
    $data = array('string', false);
    $this->assertNotValid($schema, $data, 1);
  }
  
  
  public function testStringsWithCustomValidation() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_STRING, function($str) {
        return (strlen($str) > 3);
      });
    });
    
    $data = array('asdf', 'jkl;');
    $this->assertValid($schema, $data);
    
    $data = array('asdf', 'jkl');
    $this->assertNotValid($schema, $data, 1);
  }
  
  
  public function testNumbers() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_NUMBER);
    });
    
    $data = array(15, 15.4);
    $this->assertValid($schema, $data);
    
    $data = array(15.4, '15');
    $this->assertNotValid($schema, $data, 1);
  }
  
  
  public function testNumbersWithCustomValidation() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_NUMBER, function($num) {
        return ($num > 10);
      });
    });
    
    $data = array(10.1, 11);
    $this->assertValid($schema, $data);
    
    $data = array(10.1, 10);
    $this->assertNotValid($schema, $data, 1);
  }
  
  
  public function testBooleans() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_BOOLEAN);
    });
    
    $data = array(true, false);
    $this->assertValid($schema, $data);
    
    $data = array(true, 1);
    $this->assertNotValid($schema, $data, 1);
    
    $data = array(true, 'true');
    $this->assertNotValid($schema, $data, 1);
  }
  
  
  public function testNull() {
    $schema = new Schema(Schema::TYPE_ARRAY, function($arr) {
      $arr->all(Schema::TYPE_NULL);
    });
    
    $data = array(NULL);
    $this->assertValid($schema, $data);
    
    $data = array(NULL, false);
    $this->assertNotValid($schema, $data, 1);
    
    $data = array(NULL, 'NULL');
    $this->assertNotValid($schema, $data, 1);
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
    $this->assertValid($schema, $data);
    
    $data = array(
      'first_name' => 'Maik',
      'last_name' => 'Gosenshuis',
      'date_of_birth' => '1986-09-02'
    );
    $this->assertValid($schema, $data);
    
    $data = array(
      'first_name' => 'Maik',
      'last_name' => 'Gosenshuis',
      'name' => 'Maik Gosenshuis'
    );
    $this->assertNotValid($schema, $data, 1);
    
    $data = array(
      'first_name' => 'Maik'
    );
    $this->assertNotValid($schema, $data, 1);
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
    $this->assertValid($schema, $data);
    
    $data = array(
      'name' => 'Maik Gosenshuis',
      'nicknames' => array()
    );
    $this->assertNotValid($schema, $data, 1);
    
    $data = array(
      'name' => array(
        'first' => 'Maik',
        'last' => 'Gosenshuis'
      ),
      'nicknames' => ''
    );
    $this->assertNotValid($schema, $data, 1);
  }
  
  
  public function testMultipleTypes() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING | Schema::TYPE_NUMBER | Schema::TYPE_NULL);
    });
    
    $data = array(
      'name' => 'Maik Gosenshuis'
    );
    $this->assertValid($schema, $data);
    
    $data = array(
      'name' => 15
    );
    $this->assertValid($schema, $data);
    
    $data = array(
      'name' => NULL
    );
    $this->assertValid($schema, $data);
    
    $data = array(
      'name' => true
    );
    $this->assertNotValid($schema, $data, 1);
    
    $data = array();
    $this->assertNotValid($schema, $data, 1);
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
    $this->assertValid($schema, $data);
    
    $data = array(
      'name' => NULL
    );
    $this->assertValid($schema, $data);
    
    $data = array(
      'name' => ''
    );
    $this->assertNotValid($schema, $data, 1);
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
    $this->assertValid($schema, $json_string);
  }
  
  
  public function testSupportsPHPObjects() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING);
    });
    
    $json = new \stdClass;
    $json->name = 'Maik';
    
    $this->assertValid($schema, $json);
    
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
    
    $this->assertValid($schema, $json);
  }
  
  
  public function testOtherKeysInObjects() {
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING);
    });
    
    $this->assertNotValid($schema, array('name' => 'maik', 'email' => 'email@example.com'), 1);
    $this->assertNotValid($schema, array('email' => 'email@example.com'), 2);
    
    $schema = new Schema(Schema::TYPE_OBJECT, function($obj) {
      $obj->includes('name', Schema::TYPE_STRING);
      $obj->allowsOtherKeys(true);
    });
    
    $this->assertValid($schema, array('name' => 'maik', 'email' => 'email@example.com'));
    $this->assertNotValid($schema, array('email' => 'email@example.com'), 1);
  }
}
