<?php

namespace SerienLoader;

use Psc\Code\Test\Mock\RequestDispatcherBuilder;
use Psc\URL\Response;
use Psc\URL\Request;
use Psc\URL\HTTP\Header;
use Psc\Net\HTTP\SimpleURL;

/**
 * Notice:
 * wenn der JDownloaderRPC mit Call to a member function getRaw() on a non-object aussteigt
 * dann macht der Jdownloader einen Request zu viel, den man nicht mit expectReturnsResponse() hinzugefügt hat
 */
class JDownloaderRPCTest extends \Webforge\Code\Test\Base {
  
  protected $jd;
  protected $dispatcher;
  protected $dispatcherMock;
  
  public function setUp() {
    $this->chainClass = 'SerienLoader\\JDownloaderRPC';
    parent::setUp();
    
    $this->dispatcher = new RequestDispatcherBuilder($this);
    $this->expectResponseFor('20126', 'get/rcversion');
  }
  
  public function testWhenConstructedJDownloadRPCRunsAssertVersionRequest() {
    // expectation is already made in setup
    $this->start();
  }
  
  public function testOlderJDownloaderVersionsFailToRun() {
    // re-setup
    $this->dispatcher = new RequestDispatcherBuilder($this);
    $this->expectResponseFor('12611', 'get/rcversion');
    
    $this->setExpectedException('SerienLoader\Exception');
    
    try {
      $this->start();
    } catch (SerienLoader\Exception $e) {
      $this->assertContains('Es muss Jdownloader BETA benutzt werden', $e->getMessage());
      throw $e;
    }
  }
  
  public function testHasPackageTriggersGetDownloadsAllListOnlyOnce() {
    $this->expectResponseFor( // once!
      $this->getFile('fixtures/packages.xml')->getContents(),
      'get/downloads/all/list'
    );
    
    $this->start();
    
    $this->assertTrue($this->jd->hasPackage('Californication.S03E12.de.XviD-RSG DVDRip'));
    $this->assertTrue($this->jd->hasPackage('Californication.S03E12.de.XviD-RSG DVDRip'));
    $this->assertFalse($this->jd->hasPackage('something'));
    $this->assertFalse($this->jd->hasPackage('something'));
  }

  public function testHasGrabberPackageTriggersGetGrabberListOnlyOnce() {
    $this->expectResponseFor( // once!
      $this->getFile('fixtures/grabber.xml')->getContents(),
      'get/grabber/list'
    );
    
    $this->start();
    
    $this->assertFalse($this->jd->hasGrabberPackage('something'));
    $this->assertFalse($this->jd->hasGrabberPackage('something'));
    $this->assertTrue($this->jd->hasGrabberPackage('PoloFi-S01E01'));
  }
  
  public function testGetPackageReturnsAPackageWithCorrectInfoAndWithHasPackageTriggersAllListOnlyOnce() {
    $this->expectResponseFor( // once!
      $this->getFile('fixtures/packages.xml')->getContents(),
      'get/downloads/all/list'
    );
    
    $this->start();
    
    $packageName = 'Californication.S03E12.de.XviD-RSG DVDRip';
    $this->assertTrue($this->jd->hasPackage($packageName));
    $this->assertInstanceOf('SerienLoader\JDownloaderPackage', $package = $this->jd->getPackage($packageName));
    
    // see packages.xml
    $this->assertEquals($packageName, $package->getName());
    $this->assertEquals(1, $package->getLinksTotal());
    $this->assertEquals(100.00, $package->getPercent());
    $this->assertEquals('49.61 MiB', $package->getLoaded());
  }
  
  public function testGetPackageForUnknownNameWillThrowException() {
    $this->expectResponseFor( // once!
      $this->getFile('fixtures/packages.xml')->getContents(),
      'get/downloads/all/list'
    );
    
    $nonexistant = 'nonexistantpackagenameinpackages';
    $this->start();
    
    $this->assertFalse($this->jd->hasPackage($nonexistant));
    
    $this->setExpectedException('SerienLoader\JDownloaderException');
    $this->jd->getPackage($nonexistant);
  }
  
  public function testHasPackageTriggersGetDownloadsAllListOnlyOnceUntilCleareIsCalled() {
    // clear? oder wie nennen wir es ? halt "updatePackages" oder sowas?
    return 'YAGNI ? ';
  }

  protected function start() {
    $this->dispatcherMock = $this->dispatcher->build();
    
    $this->jd = new JDownloaderRPC('localhost', 10025, $this->dispatcherMock);
  }
  
  protected function expectResponseFor($responseRaw, $relativeUrl, $matcher = NULL) {
    $relativeUrl = ltrim($relativeUrl, '/');
    
    $this->dispatcher->expectReturnsResponse(
      $this->createResponse($responseRaw),
      function ($request) use ($relativeUrl) {
        return $request->getUrl() === 'http://localhost:10025/'.$relativeUrl;
      }
    );
  }
  
  protected function createResponse($raw) {
    $response = new Response($raw, new Header(array()));
    
    return $response;
  }
  
  protected function onNotSuccessfulTest(\Exception $e) {
    if (isset($this->jd)) {
      print "JDownloader RPC log:\n";
      print $this->jd->getLog();
    }
    
    throw $e;
  }
}
?>