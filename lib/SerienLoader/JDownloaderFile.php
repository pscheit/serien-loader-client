<?php

namespace SerienLoader;

use Psc\JS\jQuery;

class JDownloaderFile extends \Psc\Object {
  /*
  <file file_downloaded="0 B"
  file_hoster="uploaded.to"
  file_name="d6cno5nf"
  file_file="2.Broke.Girls.S01E09.de.en.XViD-4SJ"
  file_percent="0,00"
  file_size="0 B"
  file_speed="0"
  file_status="File not found"/>
  */
  
  protected $percent;
  protected $hoster;
  protected $loaded;
  protected $name;
  protected $status;
  protected $size;
  
  public function __construct($name) {
    $this->name = $name;
  }
  
  public static function parse(\DOMElement $file) {
    $jq = new jQuery($file);
    
    $file = new static($jq->attr('file_name'));
    $file->setHoster($jq->attr('file_hoster'));
    $file->setStatus($jq->attr('file_status'));
    $file->setPercent((float) $jq->attr('file_percent'));
    $file->setSize($jq->attr('file_size'));
    $file->setLoaded($jq->attr('file_downloaded'));
    
    return $file;
  }
  
  public function isNotFound() {
    return $this->status === 'File not found';
  }
}
?>