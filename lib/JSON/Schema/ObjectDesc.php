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
    $this->includes[$key_name] = $this->setupChildDesc($child_type);
  }
  
  
  public function optional($key_name, $child_type, \Closure $describe = NULL) {
    $this->optional[$key_name] = $this->setupChildDesc($child_type);
  }
  
  
  public function validate($data) {
    if (!$this->validateType($data)) {
      throw new ValidationException("Expected an object.");
    }
    
    foreach ($this->excludes as $key_name) {
      if (array_key_exists($key_name, $data)) {
        throw new ValidationException(sprintf("Expected '%s' to not be present.", $key_name));
      }
    }
    
    foreach ($this->includes as $key_name => $desc) {
      if (!array_key_exists($key_name, $data)) {
        throw new ValidationException(sprintf("Expected '%s' to be present.", $key_name));
      }
      
      $desc->validate($data[$key_name]);
    }
    
    foreach ($this->optional as $key_name => $desc) {
      if (!array_key_exists($key_name, $data)) {
        continue;
      }
      
      $desc->validate($data[$key_name]);
    }
  }
  
  
  private function validateType($data) {
    return (is_object($data) || is_array($data));
  }
}
