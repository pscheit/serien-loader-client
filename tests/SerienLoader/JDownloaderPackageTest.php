<?php

namespace SerienLoader;

use Psc\XML\Helper as xml;
use Psc\JS\jQuery;

class JDownloaderPackageTest extends \Psc\Code\Test\Base {
  
  public function setUp() {
    $this->chainClass = 'SerienLoader\JDownloaderPackage';
    parent::setUp();
  }
  
  public function testParsing() {
    $xml = $this->getFile('packages.xml')->getContents();
    
    $name = 'Drive.German.AC3D.DL.720p.BluRay.x264-HDW';
    $doc = xml::doc($xml);
    $res = xml::query($doc, 'jdownloader packages[package_name="'.$name.'"]');
    
    $package = JDownloaderPackage::parse(current($res));
    $this->assertChainable($package);
    
    $this->assertEquals($name, $package->getName());
    $this->assertEquals('2.86 GiB', $package->getLoaded());
    $this->assertEquals(29, $package->getLinksTotal());
    $this->assertEquals('2.86 GiB', $package->getSize());
    $this->assertEquals(100.00, $package->getPercent());
  }
  
  public function testFileParsing() {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
    $xml .= '<packages package_ETA="~" package_linksinprogress="0" package_linkstotal="2" package_loaded="0 B" package_name="2.Broke.Girls.S01E09.de.en.XViD-4SJ" package_percent="0,00" package_size="0 B" package_speed="0 B" package_todo="0 B">';
    $xml .= '<file file_downloaded="0 B" file_hoster="uploaded.to" file_name="d6cno5nf" file_package="2.Broke.Girls.S01E09.de.en.XViD-4SJ" file_percent="0,00" file_size="0 B" file_speed="0" file_status="File not found"/>';
    $xml .= '<file file_downloaded="0 B" file_hoster="uploaded.to" file_name="kla6pdp2" file_package="2.Broke.Girls.S01E09.de.en.XViD-4SJ" file_percent="0,00" file_size="0 B" file_speed="0" file_status="File not found"/>';
    $xml .= '</packages>';
    
    $res = xml::query(xml::doc($xml), 'packages');
    $package = JDownloaderPackage::parse(current($res));
    
    $this->assertChainable($package);
    
    $this->assertCount(2, $files = $package->getFiles());
    $this->assertContainsOnlyInstancesOf('SerienLoader\JDownloaderFile', $files);
    
    list($file1, $file2) = $files;
    
    $this->assertEquals('d6cno5nf', $file1->getName());
    $this->assertTrue($file1->isNotFound());

    $this->assertEquals('kla6pdp2', $file2->getName());
    $this->assertTrue($file2->isNotFound());

    $this->assertTrue($package->hasMissingFiles());
  }
}
?>