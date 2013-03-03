<?php

namespace SerienLoader\Entities;

use Doctrine\Common\Collections\Collection;
use Psc\Data\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class CompiledTvShow extends Entity {
  
  /**
   * @var integer
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  protected $id;
  
  /**
   * @var string
   * @ORM\Column
   */
  protected $title;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $link;
  
  /**
   * @var array
   * @ORM\Column(type="array", nullable=true)
   */
  protected $languages;
  
  /**
   * @var array
   * @ORM\Column(type="array", nullable=true)
   */
  protected $formats;
  
  /**
   * @var bool
   * @ORM\Column(type="boolean")
   */
  protected $active = false;
  
  /**
   * @var Doctrine\Common\Collections\Collection<SerienLoader\Entities\Season>
   * @ORM\OneToMany(mappedBy="tvShow", targetEntity="SerienLoader\Entities\Season")
   */
  protected $seasons;
  
  public function __construct($title, $active, $link = NULL, Array $languages = array(), Array $formats = array()) {
    $this->seasons = new \Psc\Data\ArrayCollection();
    $this->setTitle($title);
    $this->setActive($active);
    if (isset($link)) {
      $this->setLink($link);
    }
    if (isset($languages)) {
      $this->setLanguages($languages);
    }
    if (isset($formats)) {
      $this->setFormats($formats);
    }
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
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * @param string $title
   */
  public function setTitle($title) {
    $this->title = $title;
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
   * @return array
   */
  public function getLanguages() {
    return $this->languages;
  }
  
  /**
   * @param array $languages
   */
  public function setLanguages(Array $languages = NULL) {
    $this->languages = $languages;
    return $this;
  }
  
  /**
   * @return array
   */
  public function getFormats() {
    return $this->formats;
  }
  
  /**
   * @param array $formats
   */
  public function setFormats(Array $formats = NULL) {
    $this->formats = $formats;
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
   * @return Doctrine\Common\Collections\Collection<SerienLoader\Entities\Season>
   */
  public function getSeasons() {
    return $this->seasons;
  }
  
  /**
   * @param Doctrine\Common\Collections\Collection<SerienLoader\Entities\Season> $seasons
   */
  public function setSeasons(Collection $seasons) {
    $this->seasons = $seasons;
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Season $season
   * @chainable
   */
  public function addSeason(Season $season) {
    if (!$this->seasons->contains($season)) {
      $this->seasons->add($season);
    }
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Season $season
   * @chainable
   */
  public function removeSeason(Season $season) {
    if ($this->seasons->contains($season)) {
      $this->seasons->removeElement($season);
    }
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Season $season
   * @return bool
   */
  public function hasSeason(Season $season) {
    return $this->seasons->contains($season);
  }
  
  public function getEntityName() {
    return 'SerienLoader\Entities\CompiledTvShow';
  }
  
  public static function getSetMeta() {
    return new \Psc\Data\SetMeta(array(
      'id' => new \Psc\Data\Type\IdType(),
      'title' => new \Psc\Data\Type\StringType(),
      'link' => new \Psc\Data\Type\StringType(),
      'languages' => new \Psc\Data\Type\ArrayType(new \Psc\Data\Type\StringType()),
      'formats' => new \Psc\Data\Type\ArrayType(new \Psc\Data\Type\StringType()),
      'active' => new \Psc\Data\Type\BooleanType(),
      'seasons' => new \Psc\Data\Type\PersistentCollectionType(new \Psc\Code\Generate\GClass('SerienLoader\\Entities\\Season')),
    ));
  }
}
?>