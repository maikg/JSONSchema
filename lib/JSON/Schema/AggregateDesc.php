<?PHP
namespace JSON\Schema;

use JSON\Schema;

abstract class AggregateDesc extends Desc {
  protected function setupChildDesc($type, \Closure $describe = NULL) {
    $child = $this->getDescForType($type);
    
    if ($describe !== NULL) {
      if ($child instanceof AggregateDesc) {
        $describe($child);
      }
      else {
        $child->setValueDescription($describe);
      }
    }
    
    return $child;
  }
  
  
  protected function getDescForType($type) {
    if ($type == Schema::TYPE_ARRAY) {
      return new ArrayDesc();
    }
    else if ($type == Schema::TYPE_OBJECT) {
      return new ObjectDesc();
    }
    
    return new PrimitiveDesc($type);
  }
}
