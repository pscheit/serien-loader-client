<?php

namespace SerienLoader;

use SerienLoader\Entities\TvShow,
    SerienLoader\Entities\Season,
    Psc\PSC,
    Psc\System\Dir
;

use Symfony\Component\CssSelector\Parser;

/**
 * @group class:SerienLoader\Subcentral
 * @group acceptance
 */
class SubcentralTest extends \Psc\Code\Test\Base {

  public function testGrab() {
    $sc = new Subcentral(); // @TODO solve dependency injection für request (mit requestbundle so wie beim serienjunkies grabber)
    $html = $sc->login('technoplayer','logf22x3');
    
    $tvShow = new TvShow();
    $tvShow->setTitle('How I Met Your Mother');
    $boardId = $sc->findBoard($tvShow);
    
    $season = new Season();
    $season->setNum(7);
    $season->setTvShow($tvShow);
    
    
    list($threadLink, $threadId) = $sc->findThread($boardId, $season);
    
    $subs = $sc->getSubs($threadLink);
    
    $dir = PSC::get(PSC::PATH_FILES)->append('subs/'.$tvShow->getTitle().'/'.$season->getNum().'/');
    $dir->make(Dir::PARENT | Dir::ASSERT_EXISTS);
    
    $sc->downloadSubs($subs, $dir);
  }
}
?>