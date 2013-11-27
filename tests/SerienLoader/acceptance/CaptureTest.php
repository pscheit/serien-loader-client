<?php

namespace SerienLoader\tests;

use Psc\URL\Request;
use Psc\XML\Helper as xml;
use Psc\PSC;

/**
 * @group acceptance
 */
class CaptureTest extends \Psc\Code\Test\Base {
  
  protected $instance;
  
  protected function ajaxTestRequest($url, Array $post) {
    $hostURL = 'http://serien-loader.philipp.zpintern/';
    
    $ajaxURL = $hostURL.ltrim($url,'/');
    $request = new Request($ajaxURL);
    $request->setPost($post);
    
    $hostConfig = PSC::getProjectsFactory()->getHostConfig();
    $request->setAuthentication($hostConfig->req('cms.user'),$hostConfig->req('cms.password'),CURLAUTH_BASIC);
    $json = json_decode($request->init()->process());
    
    $this->assertInstanceof('stdClass',$json, 'Response kann nicht zu JSOn aufgelöst werden (pw richtig??)');
    $this->assertEquals('ok',$json->status);
    return $json;
  }
  
  public function testAjaxImage() {
    $this->markTestSkipped('Akzeptanztest (es fehlen die Passwörter in der DB)');
    $url = 'http://download.serienjunkies.org/f-3c346d11cb64efec/fc_HMM-701-rar.html';
    
    $json = $this->ajaxTestRequest('/ajax.php?todo=ctrl&ctrlTodo=check',
                                   array('type'=>'captcha','url'=>$url)
                                  );
    
    $imageURL = $json->content;
    $this->assertRegexp('|/captchas/[0-9]+/image|',$imageURL);
    $this->assertEquals('captcha',$json->type);
    
    $request = new Request($hostURL.$imageURL);
    $this->assertNotEmpty($pngRaw = $request->init()->process());
    $this->assertInternalType('resource',imagecreatefromstring($pngRaw));
  }

  
  public function testManualCaptureTest() {
    $this->markTestSkipped('skipped wegen manual handling');
    $captchaText = '38V'; // das ist interactive user input
    
    $storage = new \Psc\Data\Storage(new \Psc\Data\PHPStorageDriver($this->newFile('storage.capture.php')));
    $grabber = new \SerienLoader\SerienJunkiesGrabber();
    $url = 'http://download.serienjunkies.org/f-3c346d11cb64efec/fc_HMM-701-rar.html';
    
    $loaded = FALSE;
    $links = $grabber->checkCaptcha($url, $loaded);
    if ($loaded === TRUE) {
      print "Bypassed the Captcha";
      $this->assertEquals('http://www.filesonic.com/file/2070157124/HMM.701.rar',$links);
    } else {
      if (!empty($captchaText)) {
        $storage->init();
        $secure = $storage->getData()->get(array('secure'));
        $url = $storage->getData()->get(array('url'));
        
        $this->assertNotEmpty($secure);
        $this->assertNotEmpty($url);
        
        $links = $grabber->resolveCaptcha($url,$secure,$captchaText);
        $this->assertNotEmpty($links);
        $this->assertEquals('http://www.filesonic.com/file/2070157124/HMM.701.rar',$links);
        print "Capture eingegeben und durchgekommen";
        
      } else {
        list($png,$secure) = $grabber->getCaptcha($url);
      
        $this->assertNotEmpty($png);
        $this->assertNotEmpty($secure);
      
        $storage->getData()->set(array('secure'),$secure);
        $storage->getData()->set(array('url'),$url);
        $storage->persist();
        file_put_contents('D:\captcha.png',$png);
        print "Capture unter D:\captcha.png gespeichert. Variable \$captchaText jetzt setzen!";
      }
    }
  }
}

?>