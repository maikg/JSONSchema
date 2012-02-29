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
    $this->describe = $describe;
  }
  
  
  public function validate($data) {
    if (!$this->validateType($data)) {
      throw new ValidationException(sprintf("Expected %s.", $this->type));
    }
    
    if ($this->describe !== NULL) {
      $describe = $this->describe;
      if (!$describe($data)) {
        throw new ValidationException("Value doesn't match description.");
      }
    }
  }
  
  
  private function validateType($data) {
    switch ($this->type) {
      case Schema::TYPE_STRING:
        return is_string($data);
      case Schema::TYPE_NUMBER:
        return (is_int($data) || is_float($data));
      case Schema::TYPE_BOOLEAN:
        return is_bool($data);
      case Schema::TYPE_NULL:
        return is_null($data);
      default:
        throw new \RuntimeException(sprintf("Got unknown type: %s", $this->type));
    }
  }
}
