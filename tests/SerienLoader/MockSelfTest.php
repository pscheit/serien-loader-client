<?php

namespace SerienLoader;

use SerienLoader\Mock\JDownloaderRPCMockBuilder;

class MockSelfTest extends EpisodesTestCase {
  
  public function setUp() {
    parent::setUp();
    
    $this->jd = new JDownloaderRPCMockBuilder($this);
  }
  
  public function testJDMockBuilderCanConfirmPackages() {
    $packageName = 'the.package';
    
    $this->jd->expectHasPackageCalls($this->atLeastOnce());
    $this->jd->expectConfirms(new JDownloaderPackage($packageName));
    
    $mock = $this->jd->build();
    
    $this->assertFalse($mock->hasPackage($packageName));
    $mock->confirmPackage($packageName);
    $this->assertTrue($mock->hasPackage($packageName));
  }
  
  
}
