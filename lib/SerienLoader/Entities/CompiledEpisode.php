<?php

namespace SerienLoader\Entities;

use Psc\DateTime\DateTime;
use Doctrine\Common\Collections\Collection;
use Psc\Data\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class CompiledEpisode extends Entity {
  
  /**
   * @var integer
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   */
  protected $id;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $title;
  
  /**
   * @var integer
   * @ORM\Column(type="integer")
   */
  protected $num;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $section;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $info;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $extension;
  
  /**
   * @var string
   * @ORM\Column(nullable=true)
   */
  protected $release;
  
  /**
   * @var SerienLoader\Status
   * @ORM\Column(type="SerienLoaderStatus")
   */
  protected $status;
  
  /**
   * @var Psc\DateTime\DateTime
   * @ORM\Column(type="PscDateTime")
   */
  protected $discoveredTime;
  
  /**
   * @var Psc\DateTime\DateTime
   * @ORM\Column(type="PscDateTime", nullable=true)
   */
  protected $downloadedTime;
  
  /**
   * @var Psc\DateTime\DateTime
   * @ORM\Column(type="PscDateTime", nullable=true)
   */
  protected $finishedTime;
  
  /**
   * @var array
   * @ORM\Column(type="array", nullable=true)
   */
  protected $languages;
  
  /**
   * @var string
   * @ORM\Column
   */
  protected $link;
  
  /**
   * @var SerienLoader\Entities\Season
   * @ORM\ManyToOne(targetEntity="SerienLoader\Entities\Season", inversedBy="episodes")
   * @ORM\JoinColumn(nullable=false, onDelete="cascade")
   */
  protected $season;
  
  /**
   * @var Doctrine\Common\Collections\Collection<SerienLoader\Entities\Link>
   * @ORM\OneToMany(mappedBy="episode", targetEntity="SerienLoader\Entities\Link")
   */
  protected $links;
  
  public function __construct() {
    $this->links = new \Psc\Data\ArrayCollection();
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
  public function getSection() {
    return $this->section;
  }
  
  /**
   * @param string $section
   */
  public function setSection($section) {
    $this->section = $section;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getInfo() {
    return $this->info;
  }
  
  /**
   * @param string $info
   */
  public function setInfo($info) {
    $this->info = $info;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getExtension() {
    return $this->extension;
  }
  
  /**
   * @param string $extension
   */
  public function setExtension($extension) {
    $this->extension = $extension;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getRelease() {
    return $this->release;
  }
  
  /**
   * @param string $release
   */
  public function setRelease($release) {
    $this->release = $release;
    return $this;
  }
  
  /**
   * @return SerienLoader\Status
   */
  public function getStatus() {
    return $this->status;
  }
  
  /**
   * @param SerienLoader\Status $status
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }
  
  /**
   * @return Psc\DateTime\DateTime
   */
  public function getDiscoveredTime() {
    return $this->discoveredTime;
  }
  
  /**
   * @param Psc\DateTime\DateTime $discoveredTime
   */
  public function setDiscoveredTime(DateTime $discoveredTime) {
    $this->discoveredTime = $discoveredTime;
    return $this;
  }
  
  /**
   * @return Psc\DateTime\DateTime
   */
  public function getDownloadedTime() {
    return $this->downloadedTime;
  }
  
  /**
   * @param Psc\DateTime\DateTime $downloadedTime
   */
  public function setDownloadedTime(DateTime $downloadedTime = NULL) {
    $this->downloadedTime = $downloadedTime;
    return $this;
  }
  
  /**
   * @return Psc\DateTime\DateTime
   */
  public function getFinishedTime() {
    return $this->finishedTime;
  }
  
  /**
   * @param Psc\DateTime\DateTime $finishedTime
   */
  public function setFinishedTime(DateTime $finishedTime = NULL) {
    $this->finishedTime = $finishedTime;
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
   * @return SerienLoader\Entities\Season
   */
  public function getSeason() {
    return $this->season;
  }
  
  /**
   * @param SerienLoader\Entities\Season $season
   */
  public function setSeason(Season $season) {
    $this->season = $season;
    $season->addEpisode($this);

    return $this;
  }
  
  /**
   * @return Doctrine\Common\Collections\Collection<SerienLoader\Entities\Link>
   */
  public function getLinks() {
    return $this->links;
  }
  
  /**
   * @param Doctrine\Common\Collections\Collection<SerienLoader\Entities\Link> $links
   */
  public function setLinks(Collection $links) {
    $this->links = $links;
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Link $link
   * @chainable
   */
  public function addLink(Link $link) {
    if (!$this->links->contains($link)) {
      $this->links->add($link);
    }
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Link $link
   * @chainable
   */
  public function removeLink(Link $link) {
    if ($this->links->contains($link)) {
      $this->links->removeElement($link);
    }
    return $this;
  }
  
  /**
   * @param SerienLoader\Entities\Link $link
   * @return bool
   */
  public function hasLink(Link $link) {
    return $this->links->contains($link);
  }
  
  public function getEntityName() {
    return 'SerienLoader\Entities\CompiledEpisode';
  }
  
  public static function getSetMeta() {
    return new \Psc\Data\SetMeta(array(
      'id' => new \Psc\Data\Type\IdType(),
      'title' => new \Psc\Data\Type\StringType(),
      'num' => new \Psc\Data\Type\IntegerType(),
      'section' => new \Psc\Data\Type\StringType(),
      'info' => new \Psc\Data\Type\StringType(),
      'extension' => new \Psc\Data\Type\StringType(),
      'release' => new \Psc\Data\Type\StringType(),
      'status' => new \Psc\Data\Type\DCEnumType(new \Psc\Code\Generate\GClass('SerienLoader\\Status'),array()),
      'discoveredTime' => new \Psc\Data\Type\DateTimeType(),
      'downloadedTime' => new \Psc\Data\Type\DateTimeType(),
      'finishedTime' => new \Psc\Data\Type\DateTimeType(),
      'languages' => new \Psc\Data\Type\ArrayType(new \Psc\Data\Type\StringType()),
      'link' => new \Psc\Data\Type\StringType(),
      'season' => new \Psc\Data\Type\EntityType(new \Psc\Code\Generate\GClass('SerienLoader\\Entities\\Season')),
      'links' => new \Psc\Data\Type\PersistentCollectionType(new \Psc\Code\Generate\GClass('SerienLoader\\Entities\\Link')),
    ));
  }
}
?>