<?php

/**
 * @group acceptance
 */
class JDownloaderTest extends \PHPUnit_Framework_TestCase {
  
  public function testJoinPackages() {
    $jd = new SerienLoader\JDownloaderRPC();
    $jd->removeLinks();
    
    //$link = 'http://netload.in/datei0CZeC3t6nA/BBTV.07.rar.htm';
    //$jd->addLink($link, 'patch-package', 'D:\downloads');
    
    $jd->removePackage('existing-package');
  }
  
  
  public function testWaitForChecked() {
    return;
    $link = 'http://netload.in/datei0CZeC3t6nA/BBTV.07.rar.htm';
    $jd = new SerienLoader\JDownloaderRPC();
    $jd->assertVersion();
    
    $jd->removeLinks();
    
    try {
      $jd->addLinks(array($link),
                  'custom.package.name',
                  'D:\downloads'
                  );
      
      print implode("\n",$jd->getLog());
    } catch (\SerienLoader\IncorrectLinkException $e) {
      print implode("\n",$jd->getLog());
      throw $e;
    }
  }

  public function testConnect() {
    return;
    ini_set('max_execution_time',0);
    
    $jd = new SerienLoader\JDownloaderRPC();
    $jd->assertVersion();
    
    $tvShow = new SerienLoader\Entities\TvShow();
    $tvShow->setTitle('How I Met Your Mother');
    
    $season = new SerienLoader\Entities\Season();
    $season->setNum(7);
    $season->setTvShow($tvShow);
    
    $episode = new SerienLoader\Entities\Episode();
    $episode->setSeason($season);
    $episode->setNum(1);
    $episode->setRelease('IMMERSE');
    $episode->setLanguages(array('en'));
    
    $jd->removeLinks();
    
    $package = $episode->getPackageName();
    $this->assertFalse($jd->hasPackage($package));
    $this->assertTrue($jd->hasPackage('ngbb'));
    
    $jd->addLinks(array('http://netload.in/dateiFe2mdJG0mP.htm','http://netload.in/datei2QgkfRzCT3.htm'),
                  $package,
                  'D:\downloads'
                  );
    
    $this->assertTrue($jd->hasGrabberPackage($package));
    
    $jd->confirmLinks();
    $this->assertTrue($jd->hasPackage($package));
  }
}
?>