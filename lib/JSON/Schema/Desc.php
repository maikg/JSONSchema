<?PHP
namespace JSON\Schema;

abstract class Desc {
  private $validation_errors = array();
  
  
  abstract public function validate($node, $data);
  
  
  public function getValidationErrors() {
    return $this->validation_errors;
  }
  
  
  protected function addValidationError(ValidationError $error) {
    $this->validation_errors[] = $error;
  }
  
  
  public function clearValidationErrors() {
    $this->validation_errors = array();
  }
}
