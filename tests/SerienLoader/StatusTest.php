<?php

namespace SerienLoader;

class StatusTest extends \PHPUnit_Framework_TestCase {
  
  public function testConstruct() {
    $status = Status::instance();
  }

  public function testOrder() {
    $order = array(status::DISCOVERED,
                   status::DISCARDED,
                   status::SCHEDULED,
                   status::DOWNLOADED,
                   status::WAIT_FOR_SUB,
                   status::FINISHED,
                   status::FOUND
                  );

    for ($i=0; $i < count($order)-1; $i++) {    
      $this->assertTrue(Status::ord($order[$i]) < Status::ord($order[$i+1]));
    }
    
    $this->assertTrue(Status::ord(Status::WAIT_FOR_SUB) == Status::ord(Status::WAITFORSUB));
  }
}

?>