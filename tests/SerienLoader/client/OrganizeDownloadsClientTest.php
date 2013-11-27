<?php

namespace SerienLoader;

use Webforge\Common\System\Dir;
use Psc\System\BufferLogger;
use SerienLoader\Mock\JDownloaderRPCMockBuilder;

class OrganizeDownloadsClientTest extends EpisodesTestCase {
  
  protected $jdownloaderRPC;
  protected $downloadsDir, $targetDir;
  protected $logger;
  
  protected $hosterPrio = array('netload.in', 'uploaded.net', 'share-online.biz', 'rapidshare.com');
  
  public function setUp() {
    parent::setUp();
    
    $this->jd = new JDownloaderRPCMockBuilder($this);
    $this->notFoundFile1 = new JDownloaderFile('bludkkd');
    $this->notFoundFile1->setStatus('File not found');
    
    $this->client = $this->getMock('SerienLoader\Client', array(), array('http://serien-loader-mock.ps-webforge.com'));
  }
  
  public function testOrganizerRequestsTheCurrentEpisodeListWithTheClient() {
    $this->expectClientReturnsEpisodes(array());
    
    $this->runOrganizer();
  }
  
  public function testOrganizerNeedsDecryptedLinksForAnEpisodeToStartDownloading() {
    $list = array(
      $episode = $this->getScheduledEpisode()
    );
    
    $this->expectClientReturnsEpisodes($list);
    
    $this->runOrganizer();
    
    // still scheduled
    $this->assertEquals(Status::SCHEDULED, $episode->getStatus());
  }

  public function testOrganizerStartsDownloadingAnEpisodeWhenLinksAreDecrypted() {
    $list = array(
      $episode = $this->getScheduledEpisode()
    );
    $packageName = $episode->getPackageName();
    
    $this->addDecryptedLink($episode, 'netload.in');
    $this->expectClientReturnsEpisodes($list);

    $this->jd->expectHasGrabberPackageCalls($this->atLeastOnce());
    $this->jd->expectHasPackageCalls($this->atLeastOnce());
    $this->jd->getsLinksForPackage($this->isType('array'), $packageName);
    $this->jd->expectConfirms(new JDownloaderPackage($packageName));
    
    $this->runOrganizer();
    
    $this->assertEquals(Status::DOWNLOADING, $episode->getStatus());
  }
  
  public function testRegression_DecryptLinksCanBeFoundIfHosterWithHigherPrio_HasCrawledButNotDecryptedLinks_AndOtherHosterHasDecryptedLinks() {
    $list = array(
      $episode = $this->getScheduledEpisode()
    );
    $this->expectClientReturnsEpisodes($list);
    
    $episode->setEncryptedLink('http://netload.in/encrypted', 'netload.in');
    $link = $this->addDecryptedLink($episode, 'uploaded.net');
    
    $this->hosterPrio = array('netload.in', 'uploaded.net');
    
    $this->jd->expectHasGrabberPackageCalls($this->atLeastOnce());
    $this->jd->expectHasPackageCalls($this->atLeastOnce());
    $this->jd->getsLinksForPackage($this->equalTo(array($link)), $episode->getPackageName());
    $this->jd->expectConfirms(new JDownloaderPackage($episode->getPackageName()));

    $this->runOrganizer();
    
    $this->assertEquals(Status::DOWNLOADING, $episode->getStatus());
  }
  
  public function testPackageWithMissingFilesWillSetEpisodeStatusWhileDownloadingToMISSING_FILES() {
    $list = array(
      $episode = $this->getDownloadingEpisode()
    );
    $this->expectClientReturnsEpisodes($list);
    
    $package = new JDownloaderPackage($episode->getPackageName());
    $package->setFiles(array($this->notFoundFile1));
    
    $this->assertTrue($this->notFoundFile1->isNotFound());
    
    $this->assertTrue($package->hasMissingFiles());
    
    $this->jd->hasPackage($package);
    $this->jd->expectHasPackageCalls($this->atLeastOnce());
    
    $this->runOrganizer();
    
    $this->assertEquals(Status::MISSING_FILES, $episode->getStatus());
  }
  
  public function testWhenEpisodeExistsPhyicallyInTargetPathTheStatusIsSetTo_WAIT_FOR_SUB() {
    $this->markTestIncomplete('TODO');
  }

  public function testWhenEpisodeIsDownloadingAndFileExistsInDownloadDir_TheFileIsMovedToTheTargetLocation_AndChangedTo_WAIT_FOR_SUB() {
    $this->markTestIncomplete('TODO');
  }
  
  public function testWhenEpisodeIsWaitingForSubAndSubCanBeDownloaded_EpisodeStatusIsChangedToFinished() {
    $this->markTestIncomplete('TODO');
  }
  
  public function testWhenEpisodeDoesNotNeedSubtitles() {
    $this->markTestIncomplete('TODO');
  }

  protected function runOrganizer() {
    $this->downloadsDir = Dir::createTemporary();
    $this->targetDir = Dir::createTemporary();

    $this->jdownloaderRPC = $this->jd->build();
    
    $this->organizer = new DownloadsOrganizer(
      $this->downloadsDir,
      $this->targetDir,
      $this->client,
      $this->jdownloaderRPC,
      $subsManager = NULL,
      $this->logger = new BufferLogger
    );
    
    $this->organizer->setHosterPrio($this->hosterPrio);

    $this->organizer->organize();
  }
  
  protected function onNotSuccessfulTest(\Exception $e) {
    $this->debug();
    throw $e;
  }
  
  protected function debug() {
    print $this->logger;
  }
  
  protected function addDecryptedLink($episode, $hoster) {
    $this->addEncryptedLink($episode, $hoster);
    
    $episode->setDecryptedLink($link = 'http://'.$hoster.'/decrypted/download.avi', $hoster);
    return $link;
  }
  
  protected function addEncryptedLink($episode, $hoster) {
    $episode->setEncryptedLink($link = 'http://'.$hoster.'/encrypted/xxx', $hoster);
    return $link;
  }
  
  protected function expectClientReturnsEpisodes(Array $list) {
    $this->client->expects($this->once())->method('getEpisodes')
                  ->will($this->returnValue(
                    $list
                  ));
  }
  
  public function tearDown() {
    if (isset($this->downloadsDir))
      $this->downloadsDir->delete();
      
    if (isset($this->targetDir))
      $this->targetDir->delete();
      
    parent::tearDown();
  }
}
?>