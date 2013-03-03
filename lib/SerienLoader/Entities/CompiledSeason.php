<?php

namespace SerienLoader\Entities;

use Doctrine\Common\Collections\Collection;
use Psc\Data\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class CompiledSeason extends Entity {
  
  /**
   * @var integer
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  protected $id;
  
  /**
   * @var integer
   * @ORM\Column(type="integer")
   */
  protected $num;
  
  /**
   * @var string
   * @ORM\Column
   */
  protected $language;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $link;
  
  /**
   * @var bool
   * @ORM\Column(type="boolean")
   */
  protected $active = false;
  
  /**
   * @var Doctrine\Common\Collections\Collection<SerienLoader\Entities\Episode>
   * @ORM\OneToMany(mappedBy="season", targetEntity="SerienLoader\Entities\Episode")
   */
  protected $episodes;
  
  /**
   * @var SerienLoader\Entities\TvShow
   * @ORM\ManyToOne(targetEntity="SerienLoader\Entities\TvShow", inversedBy="seasons")
   * @ORM\JoinColumn(nullable=false, onDelete="cascade")
   */
  protected $tvShow;
  
  public function __construct(TvShow $tvShow, $num, $language) {
    $this->episodes = new \Psc\Data\ArrayCollection();
    $this->setTvShow($tvShow);
    $this->setNum($num);
    $this->setLanguage($language);
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
   * @return integer
   */
  public function getNum() {
    return $this->num;
  }
  
  /**
   * @param integer $num
   */
  public function setNum($num) {
    $this->num = $num;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getLanguage() {
    return $this->language;
  }
  
  /**
   * @param string $language
   */
  public function setLanguage($language) {
    $this->language = $language;
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
   * @return bool
   */
  public function getActive() {
    return $this->active;
  }
  
  /**
   * @param bool $active
   */
  public function setActive($active) {
    $this->active = $active;
    return $this;
  }
  
  /**
   * @return Doctrine\Common\Collections\Collection<SerienLoader\Entities\Episode>
   */
  public function getEpisodes() {
    return $this->episodes;
  }
  
  /**
   * @param Doctrine\Common\Collections\Collection<SerienLoader\Entities\Episode> $episodes
   */
  public function setEpisodes(Collection $episodes) {
    $this->episodes = $episodes;
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Episode $episode
   * @chainable
   */
  public function addEpisode(Episode $episode) {
    if (!$this->episodes->contains($episode)) {
      $this->episodes->add($episode);
    }
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Episode $episode
   * @chainable
   */
  public function removeEpisode(Episode $episode) {
    if ($this->episodes->contains($episode)) {
      $this->episodes->removeElement($episode);
    }
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Episode $episode
   * @return bool
   */
  public function hasEpisode(Episode $episode) {
    return $this->episodes->contains($episode);
  }
  
  /**
   * @return SerienLoader\Entities\TvShow
   */
  public function getTvShow() {
    return $this->tvShow;
  }
  
  /**
   * @param SerienLoader\Entities\TvShow $tvShow
   */
  public function setTvShow(TvShow $tvShow) {
    $this->tvShow = $tvShow;
    $tvShow->addSeason($this);

    return $this;
  }
  
  public function getEntityName() {
    return 'SerienLoader\Entities\CompiledSeason';
  }
  
  public static function getSetMeta() {
    return new \Psc\Data\SetMeta(array(
      'id' => new \Psc\Data\Type\IdType(),
      'num' => new \Psc\Data\Type\IntegerType(),
      'language' => new \Psc\Data\Type\StringType(),
      'link' => new \Psc\Data\Type\StringType(),
      'active' => new \Psc\Data\Type\BooleanType(),
      'episodes' => new \Psc\Data\Type\PersistentCollectionType(new \Psc\Code\Generate\GClass('SerienLoader\\Entities\\Episode')),
      'tvShow' => new \Psc\Data\Type\EntityType(new \Psc\Code\Generate\GClass('SerienLoader\\Entities\\TvShow')),
    ));
  }
}
?>