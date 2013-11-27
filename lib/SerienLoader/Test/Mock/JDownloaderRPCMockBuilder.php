<?php

namespace SerienLoader\Mock;

use SerienLoader\JDownloaderPackage;

class JDownloaderRPCMockBuilder extends \Psc\Code\Test\Mock\Builder {
  
  protected $hasPackage, $hasGrabberPackage, $confirmPackage, $getPackage;
  
  public function __construct($testCase) {
    parent::__construct($testCase, $fqn = 'SerienLoader\JDownloaderRPC');
    $this->mockAllMethods = TRUE;
    
    /* JDownloaderRPC behaviour */
    $packages = array();
    $grabberPackages = array();
    
    $this->hasPackage = function ($package) use (&$packages) {
      return array_key_exists($package, $packages);
    };
    
    $this->hasGrabberPackage = function ($package) use (&$grabberPackages) {
      return array_key_exists($package, $grabberPackages);
    };
    
    $this->confirmPackage = function ($packageName, JDownloaderPackage $package) use (&$packages) {
      $packages[$packageName] = $package;
    };
    
    $this->getPackage = function ($packageName) use (&$packages) {
      return isset($packages[$packageName]) ? $packages[$packageName] : NULL;
    };
    
    $this->addPackage = function (JDownloaderPackage $package) use (&$packages) {
      $packages[$package->getName()] = $package;
    };
  }
  
  public function build() {
    $this->buildExpectation('getPackage', $this->any())
      ->will($this->returnCallback($this->getPackage));

    return $this->buildMock(array('localhost', '10025'), $callConstructor = FALSE);
  }
  
  public function expectHasPackageCalls($times = NULL) {
    $this->buildExpectation('hasPackage', $times ?: $this->any())
      ->with($this->isType('string'))
      ->will($this->returnCallback($this->hasPackage));
  }

  public function expectHasGrabberPackageCalls($times) {
    $this->buildExpectation('hasGrabberPackage', $times ?: $this->any())
      ->with($this->isType('string'))
      ->will($this->returnCallback($this->hasGrabberPackage));
  }
  
  public function getsLinksForPackage($valueMatcher, $packageName) {
    $this->buildAtMethodGroupExpectation('addLinks', 'default')
      ->with($valueMatcher, $this->equalTo($packageName));
  }
  
  public function hasPackage(JDownloaderPackage $package) {
    call_user_func($this->addPackage, $package);
  }

  public function expectConfirms(JDownloaderPackage $package) {
    $confirmPackage = $this->confirmPackage;
    
    $this->buildAtMethodGroupExpectation('confirmPackage', 'default')
      ->with($this->equalTo($package->getName()))
      ->will($this->returnCallback(function ($packageName) use ($package, $confirmPackage) {
        $confirmPackage($packageName, $package);
      }));
  }
}
