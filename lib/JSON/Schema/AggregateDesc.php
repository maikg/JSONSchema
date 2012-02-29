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
    switch ($type) {
      case Schema::TYPE_ARRAY:
        return new ArrayDesc();
        break;
      
      case Schema::TYPE_OBJECT:
        return new ObjectDesc();
        break;
      
      default:
        return new PrimitiveDesc($type);
    }
  }
}
