<?php

namespace SerienLoader\Entities;

use Psc\DateTime\DateTime;
use Psc\Data\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class CompiledLink extends Entity {
  
  /**
   * @var integer
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  protected $id;
  
  /**
   * @var string
   * @ORM\Column(type="text")
   */
  protected $link;
  
  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  protected $decrypted;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $hoster;
  
  /**
   * @var Psc\DateTime\DateTime
   * @ORM\Column(type="PscDateTime")
   */
  protected $cdate;
  
  /**
   * @var SerienLoader\Entities\Episode
   * @ORM\ManyToOne(targetEntity="SerienLoader\Entities\Episode", inversedBy="links")
   * @ORM\JoinColumn(nullable=false, onDelete="cascade")
   */
  protected $episode;
  
  public function __construct($link) {
    $this->setLink($link);
  }
  
  /**
   * @return integer
   */
  public function getId() {
    return $this->id;
  }
  
  /**
   * Gibt den Primärschlüssel des Entities zurück
   * 
   * @return mixed meistens jedoch einen int > 0 der eine fortlaufende id ist
   */
  public function getIdentifier() {
    return $this->id;
  }
  
  /**
   * @param mixed $identifier
   * @chainable
   */
  public function setIdentifier($id) {
    $this->id = $id;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getLink() {
    return $this->link;
  }
  
  /**
   * @param string $link
   */
  public function setLink($link) {
    $this->link = $link;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getDecrypted() {
    return $this->decrypted;
  }
  
  /**
   * @param string $decrypted
   */
  public function setDecrypted($decrypted) {
    $this->decrypted = $decrypted;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getHoster() {
    return $this->hoster;
  }
  
  /**
   * @param string $hoster
   */
  public function setHoster($hoster) {
    $this->hoster = $hoster;
    return $this;
  }
  
  /**
   * @return Psc\DateTime\DateTime
   */
  public function getCdate() {
    return $this->cdate;
  }
  
  /**
   * @param Psc\DateTime\DateTime $cdate
   */
  public function setCdate(DateTime $cdate) {
    $this->cdate = $cdate;
    return $this;
  }
  
  /**
   * @return SerienLoader\Entities\Episode
   */
  public function getEpisode() {
    return $this->episode;
  }
  
  /**
   * @param SerienLoader\Entities\Episode $episode
   */
  public function setEpisode(Episode $episode) {
    $this->episode = $episode;
    $episode->addLink($this);

    return $this;
  }
  
  public function getEntityName() {
    return 'SerienLoader\Entities\CompiledLink';
  }
  
  public static function getSetMeta() {
    return new \Psc\Data\SetMeta(array(
      'id' => new \Psc\Data\Type\IdType(),
      'link' => new \Psc\Data\Type\TextType(),
      'decrypted' => new \Psc\Data\Type\TextType(),
      'hoster' => new \Psc\Data\Type\StringType(),
      'cdate' => new \Psc\Data\Type\DateTimeType(),
      'episode' => new \Psc\Data\Type\EntityType(new \Psc\Code\Generate\GClass('SerienLoader\\Entities\\Episode')),
    ));
  }
}
?>