<?PHP
namespace JSON\Schema;

class ArrayDesc extends AggregateDesc {
  private $all_desc;
  
  
  public function all($type, \Closure $describe = NULL) {
    $this->all_desc = $this->setupChildDesc($type, $describe);
  }
  
  
  public function validate($node, $data) {
    if (!$this->validateType($data)) {
      $this->addValidationError(new ValidationError($node, "Expected an array."));
      return;
    }
    
    if ($this->all_desc !== NULL) {
      foreach ($data as $value) {
        $this->all_desc->validate($node, $value);
      }
    }
  }
  
  
  private function validateType($data) {
    return (is_array($data) && (count($data) == 0 || array_keys($data) === range(0, count($data) - 1)));
  }
  
  
  public function getValidationErrors() {
    $validation_errors = parent::getValidationErrors();
    
    if ($this->all_desc !== NULL) {
      $validation_errors = array_merge($validation_errors, $this->all_desc->getValidationErrors());
    }
    
    return $validation_errors;
  }
  
  
  public function clearValidationErrors() {
    parent::clearValidationErrors();
    
    if ($this->all_desc !== NULL) {
      $this->all_desc->clearValidationErrors();
    }
  }
}
