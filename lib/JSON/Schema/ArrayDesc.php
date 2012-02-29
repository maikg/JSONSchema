<?PHP
namespace JSON\Schema;

class ArrayDesc extends AggregateDesc {
  private $all_desc;
  
  
  public function all($type, \Closure $describe = NULL) {
    $this->all_desc = $this->setupChildDesc($type, $describe);
  }
  
  
  public function validate($data) {
    if (!$this->validateType($data)) {
      throw new ValidationException("Expected an array.");
    }
    
    if ($this->all_desc !== NULL) {
      foreach ($data as $value) {
        $this->all_desc->validate($value);
      }
    }
  }
  
  
  private function validateType($data) {
    return (is_array($data) && (count($data) == 0 || array_keys($data) === range(0, count($data) - 1)));
  }
}
