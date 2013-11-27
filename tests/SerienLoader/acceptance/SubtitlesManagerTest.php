<?php

namespace SerienLoader;

/**
 * @group acceptance
 */
class SubtitlesManagerTest extends \Psc\Code\Test\DatabaseTest {
  
  public function xmlName() {
    return 'episode';
  }
  
  public function testGetEpisode() {
    $episode = $this->em->getRepository('SerienLoader\Entities\Episode')->hydrate(array('id'=>8));
    
    $this->assertEquals('How.I.Met.Your.Mother.S07E05.HDTV.XviD-LOL', $episode->getInfo());
    $this->assertEquals(7,$episode->getSeason()->getNum());
    $this->assertEquals(5,$episode->getNum());
    $this->assertEquals('XviD-LOL',$episode->getRelease());

    $manager = new SubtitlesManager($this->getTestDirectory()->sub('subs/'));
    $file = $manager->getSubtitle($episode);
    
    $this->assertInstanceof('Psc\System\File',$file);
    $this->markTestIncomplete('// @FIXME @TODO wer auch immer das alles mocken will, viel spaß');
    
    $this->assertFileExists((string) $file); // hehe das ist mal geil geht nur online und wenn subcentral nicht down ist usw usf
        
    /* cache test */
    $file = $manager->getSubtitle($episode);
  }
}
?>