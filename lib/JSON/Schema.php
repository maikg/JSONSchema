<?PHP
namespace JSON;

class Schema {
  const TYPE_ARRAY = 'array';
  const TYPE_OBJECT = 'object';
  const TYPE_STRING = 'string';
  const TYPE_NUMBER = 'number';
  const TYPE_BOOLEAN = 'boolean';
  const TYPE_NULL = 'null';
  
  
  private $root_desc;
  
  
  public function describe($type, \Closure $describe = NULL) {
    switch ($type) {
      case self::TYPE_ARRAY:
        $this->root_desc = new Schema\ArrayDesc();
        break;
        
      case self::TYPE_OBJECT:
        $this->root_desc = new Schema\ObjectDesc();
        break;
      
      default:
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
