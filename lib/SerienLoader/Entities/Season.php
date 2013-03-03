<?php

namespace SerienLoader\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Psc\Code\Code;
use stdClass;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SerienLoader\Entities\SeasonRepository")
 * @ORM\Table(name="seasons",uniqueConstraints={@ORM\UniqueConstraint(name="season_uniqueness", columns={"num", "tvShow_id","language"})})
 */
class Season extends CompiledSeason {

  protected function getJSONFields() {
    return array(
      'tvShow'=>'JSON',
      //'episodes'=>'Collection<JSON>', // nicht in die fields, weil das probleme gibt wenn wir von episode aus exportieren
      'language'=>NULL,
      'num'=>NULL,
      'id'=>NULL
    );
  }
  
  /**
   * wir sind NICHT die OwningSide, trotzdem setzen wir hier uns selbst in Episode
   */
  public function addEpisode(Episode $episode) {
    if (!$this->episodes->contains($episode)) {
      $this->episodes->add($episode);
      
      $episode->setSeason($this);
    }
    return $this;
  }

  /**
   * wir sind NICHT die OwningSide, trotzdem setzen wir hier uns selbst in Episode
   */
  public function removeEpisode(Episode $episode) {
    if ($this->episodes->contains($episode)) {
      $this->episodes->removeElement($episode);
    }
    return $this;
  }

  /**
   * @return Season
   */
  public static function createFromJSON(stdClass $jsonObject) {
    $season = new static(
      TvShow::createFromJSON($jsonObject->tvShow),
      $jsonObject->num,
      $jsonObject->language
    );
    $season->setIdentifier((int) $jsonObject->id);
    
    return $season;
  }
  
  public function getEntityName() {
    return 'SerienLoader\Entities\Season';
  }
  
  public function getContextLabel($context = self::CONTEXT_DEFAULT) {
    return sprintf('%s %s: %d',
                   $this->tvShow->getTitle(), $this->language === 'de' ? 'Staffel' : 'Season', $this->num);
  }
  
  public function __toString() {
    return $this->tvShow->getTitle().' Season: '.$this->num;
  }
}
?>