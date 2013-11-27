<?php

namespace SerienLoader;

/**
 * @group acceptance
 */
class CMSAcceptanceTest extends \Psc\Code\Test\Acceptance {
  
  protected $bbt, $wc, $himym;
  
  public function setUp() {
    $this->fixtures = array(new tests\MainFixture());
    parent::setUp();
    $this->bbt = $this->hydrate('TvShow', array('title'=>'The Big Bang Theory'));
    $this->wc = $this->hydrate('TvShow', array('title'=>'White Collar'));
    $this->himym = $this->hydrate('TvShow', array('title'=>'How I Met Your Mother'));
  }
  
  public function testTvShowActivatingForm() {
    $this->test->acceptance('tvshow')->form(NULL)
      ->inserts('activate')
      ->hasComboBox('tvShowId')
    ;
  }

  public function testTvShowHasSeasonsAssociationsOnRightSide() {
    $accordion = $this->test->acceptance('tvshow')->form($this->himym->getIdentifier())
                  ->getRightAccordion();
    
    $this->test->css($accordion)
                 ->css('.psc-cms-ui-button')
                 ->atLeast(2);
  }

  public function testTvShowActivating() {
    $this->assertFalse($this->wc->isActive());
    
    $json = $this->test->acceptance('tvshow')->action(NULL, 'activate', 'json', 'POST', array('tvShowId'=>$this->wc->getId()));
    
    $this->assertEquals($this->wc->getId(), $json->id);
    $this->assertTrue($json->active);
  }


  public function testTvShowSeasonsCrawling() {
    $this->assertCount(0, $this->bbt->getSeasons());
    
    $json = $this->test->acceptance('tvshow')->action($this->bbt->getIdentifier(), 'update-seasons', 'json', 'PUT');
    
    $this->em->clear();
    $bbt = $this->hydrate('TvShow', $this->bbt->getIdentifier());
    $this->assertGreaterThan(1, count($bbt->getSeasons()));
  }
}
?>