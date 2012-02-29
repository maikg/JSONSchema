<?PHP
namespace JSON\Schema\Tests;

use JSON\Schema;
use JSON\Schema\ValidationException;

class SchemaTest extends \PHPUnit_Framework_TestCase {
  private $json;
  
  
  public function setUp() {
    $this->json = new Schema();
  }
  
  
  /**
   * @expectedException \JSON\Schema\DescriptionException
   */
  public function testValidateWithoutDescription() {
    $this->json->validate(array());
  }
  
  
  public function testEmptyArray() {
    $this->json->describe(Schema::TYPE_ARRAY);
    $this->json->validate(array());
  }
  
  
  public function testEmptyObject() {
    $this->json->describe(Schema::TYPE_OBJECT);
    $this->json->validate(array());
    $this->json->validate(new \stdClass);
  }
  
  
  /**
   * @dataProvider invalidRootObjectTypeProvider
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidRootObjectType($type) {
    $this->json->describe($type);
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
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_STRING);
    });
    
    $data = array('string', 'value', '');
    $this->json->validate($data);
    
    try {
      $data = array('string', 14);
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array('string', NULL);
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array('string', false);
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testStringsWithCustomValidation() {
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_STRING, function($str) {
        return (strlen($str) > 3);
      });
    });
    
    $data = array('asdf', 'jkl;');
    $this->json->validate($data);
    
    try {
      $data = array('asdf', 'jkl');
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNumbers() {
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_NUMBER);
    });
    
    $data = array(15, 15.4);
    $this->json->validate($data);
    
    try {
      $data = array(15.4, '15');
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNumbersWithCustomValidation() {
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_NUMBER, function($num) {
        return ($num > 10);
      });
    });
    
    $data = array(10.1, 11);
    $this->json->validate($data);
    
    try {
      $data = array(10.1, 10);
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testBooleans() {
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_BOOLEAN);
    });
    
    $data = array(true, false);
    $this->json->validate($data);
    
    try {
      $data = array(true, 1);
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(true, 'true');
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNull() {
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_NULL);
    });
    
    $data = array(NULL);
    $this->json->validate($data);
    
    try {
      $data = array(NULL, false);
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(NULL, 'NULL');
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testSimpleObject() {
    $this->json->describe(Schema::TYPE_OBJECT, function($json) {
      $json->includes('first_name', Schema::TYPE_STRING);
      $json->includes('last_name', Schema::TYPE_STRING);
      $json->excludes('name');
      $json->optional('date_of_birth', Schema::TYPE_STRING, function($date) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
      });
    });
    
    $data = array(
      'first_name' => 'Maik',
      'last_name' => 'Gosenshuis'
    );
    $this->json->validate($data);
    
    $data = array(
      'first_name' => 'Maik',
      'last_name' => 'Gosenshuis',
      'date_of_birth' => '1986-09-02'
    );
    $this->json->validate($data);
    
    try {
      $data = array(
        'first_name' => 'Maik',
        'last_name' => 'Gosenshuis',
        'name' => 'Maik Gosenshuis'
      );
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(
        'first_name' => 'Maik'
      );
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testNestedAggregates() {
    $this->json->describe(Schema::TYPE_OBJECT, function($json) {
      $json->includes('name', Schema::TYPE_OBJECT, function($json) {
        $json->includes('first', Schema::TYPE_STRING);
        $json->includes('last', Schema::TYPE_STRING);
      });
      $json->includes('nicknames', Schema::TYPE_ARRAY, function($json) {
        $json->all(Schema::TYPE_STRING);
      });
    });
    
    $data = array(
      'name' => array(
        'first' => 'Maik',
        'last' => 'Gosenshuis'
      ),
      'nicknames' => array()
    );
    $this->json->validate($data);
    
    try {
      $data = array(
        'name' => 'Maik Gosenshuis',
        'nicknames' => array()
      );
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
    
    try {
      $data = array(
        'first' => 'Maik',
        'last' => 'Gosenshuis',
        'nicknames' => array()
      );
      $this->json->validate($data);
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
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testMultipleTypes() {
    $this->json->describe(Schema::TYPE_OBJECT, function($json) {
      $json->includes('name', Schema::TYPE_STRING | Schema::TYPE_NUMBER | Schema::TYPE_NULL);
    });
    
    $data = array(
      'name' => 'Maik Gosenshuis'
    );
    $this->json->validate($data);
    
    $data = array(
      'name' => 15
    );
    $this->json->validate($data);
    
    $data = array(
      'name' => NULL
    );
    $this->json->validate($data);
    
    try {
      $data = array();
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  public function testMultipleTypesWithCustomValidation() {
    $this->json->describe(Schema::TYPE_OBJECT, function($json) {
      $json->includes('name', Schema::TYPE_STRING | Schema::TYPE_NULL, function($data) {
        return (strlen($data) > 0);
      });
    });
    
    $data = array(
      'name' => 'Maik'
    );
    $this->json->validate($data);
    
    $data = array(
      'name' => NULL
    );
    $this->json->validate($data);
    
    try {
      $data = array(
        'name' => ''
      );
      $this->json->validate($data);
      $this->fail("Expected ValidationException to be thrown.");
    }
    catch (ValidationException $e) {}
  }
  
  
  /**
   * @expectedException \JSON\Schema\DescriptionException
   */
  public function testNullTypeWithCustomValidation() {
    $this->json->describe(Schema::TYPE_OBJECT, function($json) {
      $json->includes('name', Schema::TYPE_NULL, function($data) {
        return true;
      });
    });
  }
  
  
  public function testConvertsJSONStrings() {
    $this->json->describe(Schema::TYPE_OBJECT);
    
    $json_string = "{}";
    $this->json->validate($json_string);
  }
  
  
  public function testSupportsPHPObjects() {
    $this->json->describe(Schema::TYPE_OBJECT, function($json) {
      $json->includes('name', Schema::TYPE_STRING);
    });
    
    $json = new \stdClass;
    $json->name = 'Maik';
    
    $this->json->validate($json);
    
    $this->json->describe(Schema::TYPE_ARRAY, function($json) {
      $json->all(Schema::TYPE_OBJECT, function($json) {
        $json->includes('name', Schema::TYPE_STRING);
      });
    });
    
    $user1 = new \stdClass;
    $user1->name = 'Maik';
    
    $user2 = new \stdClass;
    $user2->name = 'Nadja';
    
    $json = array($user1, $user2);
    
    $this->json->validate($json);
  }
}
