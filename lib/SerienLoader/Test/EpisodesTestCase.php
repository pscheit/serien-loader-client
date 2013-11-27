<?php

namespace SerienLoader;

use SerienLoader\Fixture\EpisodeFilter;
use Closure;
use SerienLoader\Entities\Episode;
use SerienLoader\Entities\Season;

class EpisodesTestCase extends \Psc\Doctrine\DatabaseTestCase {
  
  const HIMYM = 'himym';
  const WHITE_COLLAR = 'WhiteCollar';
  const GAME_OF_THRONES = 'GameOfThrones';
  const BBT = 'bbt';
  
  protected $episodes;
  
  public function setUp() {
    parent::setUp();
    
    $this->episodes = new Fixture\Episodes();
  }

  protected function getEpisodeById($id) {
    $id = (int) $id;
    return $this->episodes->getEpisodeByFilter(
      new EpisodeFilter(
        function (Entities\Episode $episode) use ($id) {
          return $episode->getId() === $id;
        }, 
        sprintf("filter by id %d", $id)
      )
    );
  }

  protected function getEpisodesList() {
    return $this->getFile('fixtures/api.episodes.response.json')->getContents();
  }
  
  protected function getEpisodeBy($tvShowName, $seasonNum, $seasonLang, Closure $filter, $filterDesc = NULL) {
    $episodeFilter = new EpisodeFilter($filter, $filterDesc);
    return $this->episodes->getEpisode($tvShowName, $seasonNum, $episodeFilter);
  }
  
  protected function getSeason($tvShowName, $seasonNum, $lang = 'en') {
    return $this->episodes->getSeason($tvShowName, $seasonNum, $lang);
  }
  
  // specials
  protected function getScheduledEpisode() {
    return $this->getEpisodeBy(self::WHITE_COLLAR, 4, 'en', function ($episode) {
      return $episode->getNum() === 14 && $episode->getStatus() === Status::SCHEDULED;
    }, 'scheduled episode 14');
  }

  protected function getDownloadingEpisode() {
    return $this->getEpisodeBy(self::HIMYM, 2, 'en', function ($episode) {
      return $episode->getNum() === 9 && $episode->getStatus() === Status::DOWNLOADING;
    }, 'downloading episode 9');
  }

  protected function getMovedEpisode() {
    return $this->getEpisodeBy(self::HIMYM, 2, 'en', function ($episode) {
      return $episode->getStatus() === Status::MOVED;
    }, 'moved episode (all)');
  }

  protected function getDiscardedEpisode() {
    return $this->getEpisodeBy(self::GAME_OF_THRONES, 1, 'de', function ($episode) {
      return $episode->getNum() === 3 && $episode->getStatus() === Status::DISCARDED;
    }, 'discarded episode 3');
  }

  protected function getWaitForSubEpisode() {
    return $this->getEpisodeBy(self::HIMYM, 2, 'en', function ($episode) {
      return $episode->getNum() === 8 && $episode->getStatus() === Status::WAIT_FOR_SUB;
    }, 'wait for sub episode 8');
  }

  protected function getMailedEpisode() {
    return $this->getEpisodeBy(self::WHITE_COLLAR, 4, 'en', function ($episode) {
      return $episode->getNum() === 14 && $episode->getStatus() === Status::MAILED;
    }, 'mailed episode 14');
  }

  protected function persist($thing) {
    $this->em->persist($thing);
    if ($thing instanceof Episode) {
      
    }
  }
}
