<?PHP
namespace JSON\Schema;

class ValidationError {
  public function __construct($node, $message) {
    $this->node = $node;
    $this->message = $message;
  }
  
  
  public function getNode() {
    return $this->node;
  }
  
  
  public function getMessage() {
    return $this->message;
  }
}
