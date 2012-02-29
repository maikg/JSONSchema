<?PHP
namespace JSON\Schema;

class ObjectDesc extends AggregateDesc {
  private $excludes = array();
  private $includes = array();
  private $optional = array();
  
  
  public function excludes($key_name) {
    $this->excludes[] = $key_name;
  }
  
  
  public function includes($key_name, $child_type, \Closure $describe = NULL) {
    $this->includes[$key_name] = $this->setupChildDesc($child_type, $describe);
  }
  
  
  public function optional($key_name, $child_type, \Closure $describe = NULL) {
    $this->optional[$key_name] = $this->setupChildDesc($child_type, $describe);
  }
  
  
  public function validate($node, $data) {
    if (!$this->validateType($data)) {
      $this->addValidationError(new ValidationError($node, "Expected an object."));
      return;
    }
    
    foreach ($this->excludes as $key_name) {
      if (array_key_exists($key_name, $data)) {
        $this->addValidationError(new ValidationError($node, sprintf("Expected '%s' to not be present.", $key_name)));
      }
    }
    
    foreach ($this->includes as $key_name => $desc) {
      if (!array_key_exists($key_name, $data)) {
        $this->addValidationError(new ValidationError($node, sprintf("Expected '%s' to be present.", $key_name)));
        continue;
      }
      
      $this->validateValueForKey($desc, sprintf("%s.%s", $node, $key_name), $data, $key_name);
    }
    
    foreach ($this->optional as $key_name => $desc) {
      if (!array_key_exists($key_name, $data)) {
        continue;
      }
      
      $this->validateValueForKey($desc, sprintf("%s.%s", $node, $key_name), $data, $key_name);
    }
  }
  
  
  private function validateValueForKey(Desc $desc, $node, $data, $key_name) {
    if (is_object($data)) {
      $desc->validate($node, $data->$key_name);
    }
    else {
      $desc->validate($node, $data[$key_name]);
    }
  }
  
  
  private function validateType($data) {
    return (is_object($data) || is_array($data));
  }
  
  
  public function getValidationErrors() {
    $validation_errors = parent::getValidationErrors();
    
    foreach ($this->includes as $desc) {
      $validation_errors = array_merge($validation_errors, $desc->getValidationErrors());
    }
    
    foreach ($this->optional as $desc) {
      $validation_errors = array_merge($validation_errors, $desc->getValidationErrors());
    }
    
    return $validation_errors;
  }
  
  
  public function clearValidationErrors() {
    parent::clearValidationErrors();
    
    foreach ($this->includes as $desc) {
      $desc->clearValidationErrors();
    }
    
    foreach ($this->optional as $desc) {
      $desc->clearValidationErrors();
    }
  }
}
