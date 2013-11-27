<?php

namespace SerienLoader\Fixture;

use Psc\Data\ArrayCollection;
use SerienLoader\Entities\Episode;
use SerienLoader\Entities\Season;
use SerienLoader\Entities\TvShow;
use InvalidArgumentException;
use Psc\DateTime\DateTime;

class Episodes {
  
  protected $tvShows = array();
  
  protected $seasons = array();
  
  protected $episodes = array();
  
  public function __construct() {
    $this->tvShows['himym'] = $himym = new TvShow(
      'How I Met Your Mother',
      $active = TRUE,
      'http://serienjunkies.org/serie/how-i-met-your-mother/'
    );
  
    // verlinkt sich selbst mit himym
    $this->seasons['himym'] = array(
      new Season($himym, 1, 'de'),
      new Season($himym, 1, 'en'),
      new Season($himym, 2, 'de'),
      new Season($himym, 2, 'en'),
      new Season($himym, 3, 'de'),
      new Season($himym, 3, 'en'),

      new Season($himym, 7, 'de'),
      new Season($himym, 7, 'en')
    );
    
    // inactive
    $this->tvShows['WhiteCollar'] = new TvShow(
      'White Collar',
      $active = FALSE,
      'http://serienjunkies.org/serie/white-collar/'
    );
    $this->addSeason('WhiteCollar', 4, 'en');
    

    // active keine seasons
    $this->tvShows['bbt'] = new TvShow(
      'The Big Bang Theory',
      $active = TRUE,
      'http://serienjunkies.org/serie/the-big-bang-theory/'
    );

    $got = $this->tvShows['GameOfThrones'] = new TvShow(
      'Game of Thrones',
      $active = TRUE,
      'http://serienjunkies.org/serie/game-of-thrones/'
    );
    $this->addSeason('GameOfThrones', 1, 'de');

    $this->tvShows['InTreatment'] = new TvShow(
      'In Treatment',
      $active = TRUE,
      'http://serienjunkies.org/serie/in-treatment/'
    );
    $this->addSeason('InTreatment', 1, 'de');
    
    $this->addEpisodes();
  }
  
  protected function addSeason($tvShowName, $num, $lang) {
    $this->seasons[$tvShowName][$num] = new Season($this->tvShows[$tvShowName], $num, $lang);
  }
  
  protected function addEpisodes() {
    $this->loadFromData();
  }
  
  public function getTvShow($name) {
    return $this->tvShows[$name];
  }
  
  public function getSeason($tvShowName, $num, $lang = 'en') {
    if (isset($this->seasons[$tvShowName])) {
      foreach ($this->seasons[$tvShowName] as $season) {
        if ($season->getNum() == $num && $season->getLanguage() === $lang) {
          return $season;
        }
      }
    }
    
    throw new InvalidArgumentException(sprintf('Season: %s S%02d %s not found', $tvShowName, $num, $lang));
  }

  public function getEpisode($tvShowName, $seasonNum, EpisodeFilter $filter) {
    if (isset($this->episodes[$tvShowName])) {
      if (isset($this->episodes[$tvShowName][$seasonNum])) {
        foreach ($this->episodes[$tvShowName][$seasonNum] as $episode) {
          if (call_user_func($filter->closure, $episode)) {
            return $episode;
          }
        }
      }
    }
    
    throw new InvalidArgumentException(sprintf('No Episode with specified filter matches in %s S%02d %s', $tvShowName, $seasonNum, $filter));
  }

  public function getEpisodeByFilter(EpisodeFilter $filter) {
    foreach ($this->episodes as $tvShow) {
      foreach ($tvShow as $season) {
        foreach ($season as $episode) {
          if (call_user_func($filter->closure, $episode)) {
            return $episode;
          }
        }
      }
    }

    throw new InvalidArgumentException(sprintf('No Episode with specified filter matches %s', $filter));
  }

  public function loadFromData() {
    require $GLOBALS['env']['root']->getFile('tests/files/fixtures/EpisodesData.php');
    
    foreach ($episodes as $row) {
      $row = (object) $row;
      list($tvShowName, $seasonNum, $seasonLang) = $seasonMappings[$row->season_id];
      
      $episode = new Episode();
      $episode
        ->setIdentifier($row->id)
        ->setLanguages($row->languages ? (array) unserialize(stripslashes($row->languages)) : array($seasonLang))
        ->setSeason($season = $this->getSeason($tvShowName, $seasonNum, $seasonLang))
        ->setNum((int) $row->num)
        ->setStatus($row->status)
        ->setInfo($row->info)
        ->setTitle($row->title)
        ->setRelease($row->releasegroup)
        ->setLink($row->link)
        ->setSection($row->section)
        ->setDiscoveredTime(DateTime::createFromMysql($row->discoveredTime))
      ;
        
      if ($row->finishedTime) {
        $episode->setFinishedTime(DateTime::createFromMysql($row->finishedTime));
      }
        
      if ($row->downloadedTime) {
        $episode->setDownloadedTime(DateTime::createFromMysql($row->downloadedTime));
      }
      
      $this->episodes[$tvShowName][$season->getNum()][] = $episode;
    }
  }
}
