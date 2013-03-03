<?php

namespace SerienLoader\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Psc\DateTime\DateTime;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="links")
 */
class Link extends CompiledLink {
  
  public function __construct($link) {
    parent::__construct($link);
    $this->cdate = new DateTime(time());
  }
  
  protected function getJSONFields() {
    return array('id'=>NULL,
                 'link'=>NULL,
                 'decrypted'=>NULL,
                 'hoster'=>NULL,
                 'cdate'=>'JSON<Psc\DateTime\DateTime>'
                );
  }
  
  public static function createFromJSON($json) {
    $link = new static($json->link);
    $link->setJSONFields($json);
    return $link;
  }
  
  public function setURL($url) {
    $this->link = $url;
    return $this;
  }
  
  public function getURL() {
    return $this->link;
  }

  public function getEntityName() {
    return 'SerienLoader\Entities\Link';
  }
  
  public function __toString() {
    return sprintf('[Entity\Link for hoster %s for Episode: %s]', $this->hoster, (string) $this->episode->getContextLabel());
  }
}
?>