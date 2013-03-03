<?php

namespace SerienLoader\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use stdClass;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SerienLoader\Entities\TvShowRepository")
 * @ORM\Table(name="tvshows", uniqueConstraints={@ORM\UniqueConstraint(name="tvshow_uniqueness", columns={"title"})})
 */
class TvShow extends CompiledTvShow {

  protected function getJSONFields() {
    return array(
      'formats'=>NULL,
      'languages'=>NULL,
      'title'=>NULL,
      'id'=>NULL
    );
  }

  /**
   * @return TvShow
   */
  public static function createFromJSON(stdClass $jsonObject) {
    $o = new static($jsonObject->title, isset($jsonObject->active) ? (bool) $jsonObject->active : FALSE);
    $o->setJSONFields($jsonObject);
    
    return $o;
  }
  
  public function getSeasonsByLanguage($lang = NULL) {
    $seasons = array();
    foreach ($this->getSeasons() as $season) {
      $seasons[$season->getLanguage()][$season->getNum()] = $season;
    }
    
    if ($lang === NULL) {
      return $seasons;
    } else {
      return $seasons[$lang];
    }
  }
  
  public function isActive() {
    return $this->active;
  }
  
  public function getContextLabel($context = self::CONTEXT_DEFAULT) {
    return $this->title;
  }
  
  public function getEntityName() {
    return 'SerienLoader\Entities\TvShow';
  }
  
  public function getFormats() {
    if (!isset($this->formats)) {
      $this->formats = array();
    }
    return $this->formats;
  }

  public function getLanguages() {
    if (!isset($this->languages)) {
      $this->languages = array();
    }
    return $this->languages;
  }
}
?>