<?PHP
namespace JSON\Schema;

class ObjectDesc extends AggregateDesc {
  private $excludes = array();
  private $includes = array();
  private $optional = array();
  
  private $allows_other_keys = false;
  
  
  public function allowsOtherKeys($allow) {
    $this->allows_other_keys = $allow;
  }
  
  
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
    
    $handled_keys = array();
    
    foreach ($this->excludes as $key_name) {
      if (array_key_exists($key_name, $data)) {
        $this->addValidationError(new ValidationError($node, sprintf("Expected '%s' to not be present.", $key_name)));
      }
      
      $handled_keys[$key_name] = true;
    }
    
    foreach ($this->includes as $key_name => $desc) {
      if (!array_key_exists($key_name, $data)) {
        $this->addValidationError(new ValidationError($node, sprintf("Expected '%s' to be present.", $key_name)));
        continue;
      }
      
      $this->validateValueForKey($desc, sprintf("%s.%s", $node, $key_name), $data, $key_name);
      
      $handled_keys[$key_name] = true;
    }
    
    foreach ($this->optional as $key_name => $desc) {
      if (!array_key_exists($key_name, $data)) {
        continue;
      }
      
      $this->validateValueForKey($desc, sprintf("%s.%s", $node, $key_name), $data, $key_name);
      
      $handled_keys[$key_name] = true;
    }
    
    if (!$this->allows_other_keys) {
      $unhandled_data = array_diff_key((array)$data, $handled_keys);
      if (count($unhandled_data) > 0) {
        foreach ($unhandled_data as $key_name => $value) {
          $this->addValidationError(new ValidationError($node, sprintf("Expected '%s' to not be present.", $key_name)));
        }
      }
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
