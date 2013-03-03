<?php

namespace SerienLoader;

class Status extends \Psc\Doctrine\EnumType {
  
  const DISCOVERED = 'discovered'; // wurde im stream gefunden
  const MAILED = 'mailed'; // wurde im stream gefunden und gemailed
  const DISCARDED = 'discarded';   // wurde im stream gefunden aber nicht weiter betrachtet durch einen Filter (language meist)
  
  const SCHEDULED = 'scheduled';   // wurde zum download vorgemerkt, weil die Episode nicht vorhanden war
  const DOWNLOADED = 'downloaded'; // wurde scheduled und dann ausgewählt
  const DOWNLOADING = 'downloading'; // wurde dem jdownloader hinzugefügt und ist in process
  const MISSING_FILES = 'err_downloading_missing_files'; // beim downloaden wurde festgestellt, dass download-files fehlen (hdd crash, etc)
  
  const MOVED = 'moved'; // wurde heruntergeladen und in die Verzeichnisstruktur eingeordnet
  const WAIT_FOR_SUB = 'wait_for_sub'; // wurde zum download gebracht oder bereits heruntergeladen, aber der sub ist noch nicht da
  const WAITFORSUB = self::WAIT_FOR_SUB;
  const FINISHED = 'finished'; // wurde heruntergeladen und ist komplett fertig (auch mit subs)
  
  const FOUND = 'found'; // war bereits vorhanden
  
  protected static $orderedValues = array(self::DISCOVERED, self::MAILED, self::DISCARDED, self::SCHEDULED, self::DOWNLOADING, self::MISSING_FILES, self::DOWNLOADED, self::MOVED, self::WAIT_FOR_SUB, self::FINISHED, self::FOUND);
  
  protected $name = 'SerienLoaderStatus';
  protected $values;

  public function getValues() {
    if (!isset($this->values)) {
      $this->values = self::$orderedValues;
    }
    return $this->values;
  }
  
  public static function instance() {
    return self::getType('SerienLoaderStatus');
  }

  public static function ord($status) {
    if ($status === NULL) return -1;
    $values = self::$orderedValues;
    
    $order = array_flip($values);
    
    return $order[$status];
  }
  
  public static function name($ord) {
    $values = self::$orderedValues;
    
    $names = $values;
    return $names[$ord];
  }
}
?>