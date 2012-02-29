<?PHP
namespace JSON\Schema;

use JSON\Schema;

class PrimitiveDesc extends Desc {
  private $type;
  private $describe;
  
  
  public function __construct($type) {
    $this->type = $type;
  }
  
  
  public function setValueDescription(\Closure $describe) {
    if ($this->type == Schema::TYPE_NULL) {
      throw new DescriptionException("Got custom description for NULL type.");
    }
    
    $this->describe = $describe;
  }
  
  
  public function validate($node, $data) {
    if (!$this->validateType($data)) {
      $this->addValidationError(new ValidationError($node, "Got unexpected type."));
      return;
    }
    
    if ($this->describe !== NULL && $this->getType($data) != Schema::TYPE_NULL) {
      $describe = $this->describe;
      if (!$describe($data)) {
        $this->addValidationError(new ValidationError($node, "Value doesn't match description."));
      }
    }
  }
  
  
  private function validateType($data) {
    $result = false;
    $actual_type = $this->getType($data);
    
    return (
      ($actual_type == Schema::TYPE_STRING    && ($this->type & Schema::TYPE_STRING))   ||
      ($actual_type == Schema::TYPE_NUMBER    && ($this->type & Schema::TYPE_NUMBER))   ||
      ($actual_type == Schema::TYPE_BOOLEAN   && ($this->type & Schema::TYPE_BOOLEAN))  ||
      ($actual_type == Schema::TYPE_NULL      && ($this->type & Schema::TYPE_NULL))
    );
  }
  
  
  private function getType($data) {
    if (is_string($data)) {
      return Schema::TYPE_STRING;
    }
    else if (is_int($data) || is_float($data)) {
      return Schema::TYPE_NUMBER;
    }
    else if (is_bool($data)) {
      return Schema::TYPE_BOOLEAN;
    }
    else if (is_null($data)) {
      return Schema::TYPE_NULL;
    }
    
    return 0;
  }
}
