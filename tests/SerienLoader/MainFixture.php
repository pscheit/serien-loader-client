<?php

namespace SerienLoader;

use SerienLoader\Entities\TvShow;
use SerienLoader\Entities\Season;

use Doctrine\Common\Persistence\ObjectManager;

class MainFixture extends \Psc\Doctrine\ProjectFixture {
  
  /**
   * Load data fixtures with the passed EntityManager
   * 
   * @param Doctrine\Common\Persistence\ObjectManager $manager
   */
  public function load(ObjectManager $manager) {
    parent::load($manager);
    
    $this->loadTvShows($manager);
    
    $manager->flush();
  }
  
  public function loadTvShows(ObjectManager $manager) {
    
    // active und seasons
    $manager->persist($himym = new TvShow('How I Met Your Mother', TRUE, 'http://serienjunkies.org/serie/how-i-met-your-mother/'));
    $manager->persist(new Season($himym, 1, 'de'));
    $manager->persist(new Season($himym, 1, 'en'));
    $manager->persist(new Season($himym, 2, 'de'));
    $manager->persist(new Season($himym, 2, 'en'));
    $manager->persist(new Season($himym, 3, 'de'));
    $manager->persist(new Season($himym, 3, 'en'));
    
    // inactive keine seasons
    $manager->persist(new TvShow('White Collar', FALSE, 'http://serienjunkies.org/serie/white-collar/'));

    // active keine seasons
    $manager->persist(new TvShow('The Big Bang Theory', TRUE, 'http://serienjunkies.org/serie/the-big-bang-theory/'));
  }
}
?>