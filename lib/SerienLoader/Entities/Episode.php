<?php

namespace SerienLoader\Entities;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\EntityManager,
    Psc\Doctrine\Helper as DoctrineHelper,
    SerienLoader\Status,
    Psc\Code\Code,
    Psc\Preg,
    stdClass,
    Psc\DateTime\DateTime
;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\SerienLoader\Entities\EpisodeRepository")
 * @ORM\Table(name="episodes");
 *
 * , uniqueConstraints={
 *   UniqueConstraint(name="episode_releasegroup", columns={"season_id","num","release"})
 *  })
 *
 * eigentlich wollen wir auch noch die langauge in den unique keys haben, aber das geht nicht.
 * @TODO languages in vernünftiges Datenformat umbauen (nicht blob) dann geht das auch
 *  //@UniqueConstraint(name="episode_info", columns={"info"})
 */
class Episode extends CompiledEpisode {

  /**
   * Cache
   */
  protected $groupStatus;
  protected $groupStatusFetched = FALSE; // da groupStatus auch NULL sein kann und damit valide ist
  
  /**
   * cache für subtitles
   *
   * @var array URLs zu subtitlen. Keys sind die sprachen
   */
  protected $subtitles;
  
  public function __construct() {
    $this->languages = array();
    parent::__construct();
  }
  
  protected function getJSONFields() {
    return array('links'=>'Collection<JSON>',
                 'link'=>NULL,
                 'languages'=>NULL,
                 'finishedTime'=>'JSON<Psc\DateTime\DateTime>',
                 'downloadedTime'=>'JSON<Psc\DateTime\DateTime>',
                 'discoveredTime'=>'JSON<Psc\DateTime\DateTime>',
                 'subtitles'=>NULL,
                 'status'=>NULL,
                 'extension'=>NULL,
                 'release'=>NULL,
                 'season'=>'JSON',
                 'info'=>NULL,
                 'title'=>NULL,
                 'num'=>NULL,
                 'id'=>NULL,
                 );
  }
  
  /**
   * @return Episode
   */
  public static function createFromJSON(stdClass $jsonObject) {
    $episode = new static();
    $episode->setJSONFields($jsonObject);
    
    $episode->setSeason(Season::createFromJSON($jsonObject->season));
    foreach ($jsonObject->links as $jsonLink) {
      $episode->getLinks()->add(Link::createFromJSON($jsonLink));
    }
    
    return $episode;
  }
  
  
  public function getContextLabel($context = self::CONTEXT_DEFAULT) {
    return $this->getLabel();
  }
  
  public function setEncryptedLink($url, $hoster) {
    $links = $this->getLinksByHoster();
    
    if (!array_key_exists($hoster, $links)) {
      $link = new Link($url);
      $link->setEpisode($this)
           ->setHoster($hoster);
      ;
      $this->links->add($link);
    } else {
      $links[$hoster]->setURL($url);
    }
    return $this;
  }
  
  public function setDecryptedLink($partsText,$hoster) {
    if (!empty($partsText)) {
      $links = $this->getLinksByHoster();
      if (array_key_exists($hoster,$links)) {
        $links[$hoster]->setDecrypted($partsText);
      } else {
        throw new \Psc\Exception('Keinen Link zum Hoster: '.$hoster.' für die Episode gefunden!');
      }
    }
    return $this;
  }
  
  public function isDecrypted($hoster) {
    if (count($this->links) == 0) return FALSE;
    foreach ($this->links as $link) {
      if ($link->getDecrypted() != NULL)
        return TRUE;
    }
    return FALSE;
  }
  
  public function getLinksByHoster() {
    return DoctrineHelper::reindex($this->getLinks()->toArray(),'hoster');
  }

  public function getLabel() {
    return sprintf('%s S%02dE%02d %s [%s]',
                   $this->getSeason()->getTvShow()->getTitle(), $this->getSeason()->getNum(), $this->getNum(), implode(',',$this->getLanguages()), $this->getRelease());
  }
  
  public function getPackageName() {
    return rtrim(sprintf('%s.S%02dE%02d.%s.%s',
                   $this->fily($this->getSeason()->getTvShow()->getTitle()), $this->getSeason()->getNum(), $this->getNum(), implode('.',$this->getLanguages()), $this->getRelease()), '.'); //rtrim dafür wenn release leer ist
  }
  
  protected function fily($string) {
    return str_replace(' ','.',$string);
  }
  
  public function getSearchFormats() {
    if (count($this->getTvShow()->getFormats()) > 0) {
      return array_reverse($this->getTvShow()->getFormats());
    }
    return array();
  }
  
  /**
   * Gibt alle Sprachen zurück, für die auf ein Subtitle gewartet werden soll
   */
  public function getSubtitleLanguages() {
    if (in_array('de',$this->languages)) { // keine subtitles folgen die de sind
      return array();
    } else {
      return array('de','en'); // de, en für alle anderen
    }
  }
  
  public function getRelease() {
    if (!isset($this->release) && !empty($this->info)) {
      /* wir holen uns das Release so:
        meist steht am Ende (xvid|h.264|web-ddl|..etc...)-$release
      */
      
      // @TODO mal in en Parser auslagern
      
      // das ist <Format>-<ReleaseGroup> am Ende des Strings
      // sowas wie AVC-TVS oder XviD-RSG
      $this->release = Preg::qmatch($this->info,'/([a-z0-9A-Z]+-[a-zA-Z0-9&]+)$/',1);
      
      /* group darf auch leer sein, dann versuchen wir das release anders zu bekommen
      */
      if (!isset($this->release)) {
        if (($this->release = Preg::qmatch($this->info, '/S[0-9]+E[0-9]+\.German\.Dubbed\.DL\.(.*)$/',1)) !== NULL) {
          $this->release = str_replace('.',' ',$this->release);
          return $this->release;
        }
        
        try {
          // normalize
          $this->release = \Psc\Preg::matchArray(Array(
            '/(WEB.DL(.*)720p|720p(.*)WEB.DL)/i' => 'WEB-DL 720p',
            '/(WEB.DL(.*)1080|1080(.*)WEB.DL)/i' => 'WEB-DL 1080',
            '/720p.ITunesHD/' => 'ITunesHD 720p',
            '/WEB-DL/i' => 'WEB-DL',
            '/(dvdrip(.*)xvid|dvdrip(.*)xvid)/i' => 'XviD-DVDRip',
            '/dvdrip/i' => 'DVDRip',
          ), $this->info);
        } catch (\Psc\NoMatchException $e) {
          
        }
      }
      
      /* jetzt suchen wir noch nach tags, die das Release besser kennzeichnen */
      try {
        $tags = \Psc\Preg::matchFullArray(array(
          '/ITunesHD/i'=>'ITunes',
          '/WEB.DL/i' => 'WEB-DL',
          '/dvdrip/i' => 'DVDRip',
          '/720p/i'=>'720p',
          '/1080p/i'=>'1080p',
          '/1080i/i'=>'1080i',
          '/dvdscr/i'=>'DVDScr',
          '/blue?ray/i'=>'Bluray',
          '/xvid/i'=>'XviD'
        ), $this->info);
        
        // merge
        foreach ($tags as $tag) {
          if (mb_stripos($this->release,$tag) === FALSE) {
            $this->release .= ' '.$tag;
          }
        }
      } catch (\Psc\NoMatchException $e) {
        
      }
      
      $this->release = trim($this->release);
      if ($this->release === '') $this->release = NULL;
    }
    
    return $this->release;
  }
  
  public function getTvShow() {
    return $this->getSeason()->getTvShow();
  }

  public function getEntityName() {
    return 'SerienLoader\Entities\Episode';
  }
  
  
  public function setStatus($status, $updateTime = TRUE) {
    $updateTime = $updateTime && $this->status !== $status;
    $this->status = $status;
    
    if ($updateTime)
      $this->updateStatusTimestamp();
      
    return $this;
  }

  public function updateStatusTimestamp() {
    if ($this->status === Status::FINISHED) {
      $this->finishedTime = DateTime::now();
    } elseif ($this->status == Status::DISCOVERED) {
      $this->discoveredTime = DateTime::now();
    } elseif ($this->status == Status::DOWNLOADED) {
      $this->downloadedTime = DateTime::now();
    }
    return $this;
  }
    
  /**
   * ist nur gesetzt wenn lokal auf dem client und vorher durch episodes (API) gesetzt
   */
  public function getSubtitles() {
    return $this->subtitles;
  }
  
  public function setSubtitles(Array $subtitles) {
    $this->subtitles = $subtitles;
  }
  
  public function getGroupStatus() {
    if (!$this->groupStatusFetched) {
      $this->groupStatus = $this->getRepository()->getGroupStatus($this,FALSE);
      $this->groupStatusFetched = TRUE;
    }
    return $this->groupStatus;
  }
  
  public function setLanguages(Array $languages = NULL) {
    if (is_array($languages)) {
      sort($languages);
    }
    
    $this->languages = $languages;
    return $this;
  }
  
  public function hasLanguage($lang) {
    return $this->in_array($lang, $this->languages);
  }


}
?>