<?php

namespace SerienLoader;

class EpisodeTest extends EpisodesTestCase {
  
  public function testFixtureReleasesFromHIMYM1De() {
    $episodes = $this->getSeason(self::HIMYM, 1, 'de')->getEpisodes();
    
    $this->assertCount(22, $episodes);
    foreach ($episodes as $episode) {
      $this->assertEquals('x264-Ryu 720p', $episode->getRelease());
    }
  }
  
  public function testJSONImportAndExport() {
    $episode = $this->getEpisodeById(2057);

    $episodeJson = $episode->json();
    $this->assertNotEmpty($episodeJson);

    $nrmlz = json_decode($episodeJson);
    $importedEpisode = Entities\Episode::createFromJSON($nrmlz);
    
    $this->assertEquals($importedEpisode->getIdentifier(), $episode->getIdentifier());
    $this->assertEquals($importedEpisode->getInfo(), $episode->getInfo());
    $this->assertEquals($importedEpisode->getSeason()->getIdentifier(), $episode->getSeason()->getIdentifier());
    $this->assertEquals($importedEpisode->getSeason()->getTvShow()->getIdentifier(), $episode->getSeason()->getTvShow()->getIdentifier());
    $this->assertEquals($importedEpisode->getDiscoveredTime()->format('U'), $episode->getDiscoveredTime()->format('U'));
  }
}
