<?PHP
namespace JSON;

class Schema {
  const TYPE_ARRAY = 1;
  const TYPE_OBJECT = 2;
  const TYPE_STRING = 4;
  const TYPE_NUMBER = 8;
  const TYPE_BOOLEAN = 16;
  const TYPE_NULL = 32;
  
  
  private $root_desc;
  
  
  public function describe($type, \Closure $describe = NULL) {
    if ($type == self::TYPE_ARRAY) {
      $this->root_desc = new Schema\ArrayDesc();
    }
    else if ($type == self::TYPE_OBJECT) {
      $this->root_desc = new Schema\ObjectDesc();
    }
    else {
      throw new \InvalidArgumentException("Root object should be an array or an object.");
    }
    
    if ($describe !== NULL) {
      $describe($this->root_desc);
    }
  }
  
  
  public function validate($data) {
    if (is_string($data)) {
      $data = json_decode($data, true);
    }
    
    $this->root_desc->validate($data);
  }
}
