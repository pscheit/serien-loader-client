<?php

namespace SerienLoader\Entities;

use Webforge\Common\Preg;
use stdClass;

abstract class Entity extends \Psc\CMS\AbstractEntity {
  
  public function setJSONFields(stdClass $json) {
    foreach ($this->getJSONFields() as $field => $meta) {
      if ($meta === NULL) {
        $this->$field = $json->$field;
      } elseif (mb_substr($meta,0,4) === 'JSON') {
        if ($json->$field === NULL) {
          $this->$field = NULL;
        } elseif (($class = Preg::qmatch($meta,'/^JSON<(.*)>$/', 1)) !== NULL) {
          $this->$field = $class::createFromJSON($json->$field);
        }
      }
    }
    return $this;
  }

}
?>