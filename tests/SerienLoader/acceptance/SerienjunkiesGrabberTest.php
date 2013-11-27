<?php

namespace SerienLoader;

use Psc\Data\Crypt\AES;
use Psc\URL\Request;
use Psc\URL\Response;
use Psc\URL\HTTP\Header as HTTPHeader;
use Psc\URL\CachedRequest;
use Psc\URL\RequestBundle;
use SerienLoader\Entities\TvShow;
use SerienLoader\Entities\Season;
use SerienLoader\Entities\Episode;


/**
 * @group acceptance
 */
class SerienjunkiesGrabberTest extends \Psc\Code\Test\DatabaseTest {
  
  protected $requestBundle;
  
  protected $sjg;
  
  public function xmlName() {
    return 'episode';
  }
  
  public static function SetUpBeforeClass() {
    //print \Psc\Doctrine\Helper::updateSchema(\Psc\Doctrine\Helper::FORCE);
  }
  
  public function setUp() {
    $this->requestBundle = $this->createRequestBundle();
    parent::setUp();
  }
  
  protected function createRequestBundle() {
    $bundle = $this->getMock('Psc\URL\RequestBundle', array('trigger'), array(30*60));
    $bundle->expects($this->never())
            ->method('trigger'); // keine requests die live sind ausführen
      
    foreach (
              array(
                'http://serienjunkies.org/serie/californication/'=>'index.californication.htm',
                'http://serienjunkies.org/serie/how-i-met-your-mother/' => 'himym.index.htm',
                'http://serienjunkies.org/?cat=0&showall' => 'index.htm',
                'http://serienjunkies.org/californication/californication-staffel-3-dvdripweb-dl-xvid720pdvd9dvdr/' => 'staffel3.californication.htm',
                'http://serienjunkies.org/serie/firefly/' => 'index.firefly.htm',
                'http://serienjunkies.org/firefly/firefly-season-1-hdtv720p-x264/' => 'staffel1.firefly.htm'
              )
              as $url => $file
            ) {
      $savedHTML = $this->getFile($file);
    
      $request = new CachedRequest($url);
      $request->setCachedResponse(new Response($savedHTML->getContents(), new HTTPHeader()));
      $bundle->addCachedRequest($request);
    }

    return $bundle;
  }
  
  public function testGrabHIMYMFixture() {
    //How.I.Met.Your.Mother.S07E04.The.Stinson.Missile.Crisis.German.Custom.Subbed.WS.HDTV.XviD.iNTERNAL-BaCKToRG
    $episode = $this->em->find('SerienLoader\Entities\Episode',9);
    
    list($sjg,$files) = $this->grab($episode);

    $expectedFiles = array (
      'filesonic.com' => 'http://download.serienjunkies.org/f-e04e23fd71439c30/fc_HMMS-704-rar.html',
      'netload.in' => 'http://download.serienjunkies.org/f-616cb936cf3fffef/nl_HMMS-704-rar.html',
      'rapidshare.com' => 'http://download.serienjunkies.org/f-dfa37b4aea371caa/rc_HMMS-704-rar.html',
      'share-online.biz' => 'http://download.serienjunkies.org/f-082d305f325e20d6/so_HMMS-704-rar.html',
      'uploaded.to' => 'http://download.serienjunkies.org/f-3d790c271326089e/ul_HMMS-704-rar.html',
    );
    
    $this->assertEquals($expectedFiles,$files, 'Expected: '.count($expectedFiles).' Links. Gefunden: '.count($files).' Links');
  }

  /**
   * @group sj
   */
  public function testGrabTvShows() {
    list($sjg, $tvShows) = $this->grab(function ($sjg) {
      return $sjg->getTvShows();
    });

    $this->assertEquals(5786, count($tvShows), sprintf('%d TvShows erwartet aber nur %d gefunden', 5786, count($tvShows)));
    $this->assertArrayHasKey('Californication', $tvShows);
    $this->assertInstanceOf('SerienLoader\Entities\TvShow',$californication = $tvShows['Californication']);
    $this->assertEquals('http://serienjunkies.org/serie/californication/', $californication->getLink());
    return $californication;
  }
  
  /**
   * @depends testGrabTvShows
   * @group sj
   */
  public function testGrabSeasons(TvShow $californication) {
    list($sjg, $seasons) = $this->grab(function ($sjg) use ($californication) {
      return $sjg->getSeasons($californication);
    });
    
    $this->assertArrayHasKey('en',$seasons);
    $this->assertArrayHasKey('de',$seasons);
    $this->assertEquals(5, count($seasons['en']), sprintf('%d %s Seasons erwartet aber nur %d gefunden', 5, 'englische', count($seasons)));
    $this->assertEquals(4, count($seasons['de']), sprintf('%d %s Seasons erwartet aber nur %d gefunden', 4, 'deutsche', count($seasons)));
    
    $c = 'SerienLoader\Entities\Season';
    
    $test = $this;
    $assertSeason = function ($lang, $num, $link) use ($test, $c, $seasons) {
      $test->assertInstanceOf($c, $season = $seasons[$lang][$num]);
      $test->assertEquals($num, $season->getNum());
      $test->assertEquals($lang, $season->getLanguage());
      $test->assertEquals($link, $season->getLink());
      return $season;
    };
    
    $assertSeason('en', 1, 'http://serienjunkies.org/californication/californication-season-1-hr-hdtv-xvid/');
    $assertSeason('de', 1, 'http://serienjunkies.org/californication/californication-staffel-1-dvdrip-xvid/');
    
    $assertSeason('en', 2, 'http://serienjunkies.org/californication/californication-season-2-dvdriphdtv-xvid/');
    $assertSeason('de', 2, 'http://serienjunkies.org/californication/californication-staffel-2-hdtv-xvid720p/');
    
    $assertSeason('de', 3, 'http://serienjunkies.org/californication/californication-staffel-3-dvdripweb-dl-xvid720pdvd9dvdr/');
    $assertSeason('en', 3, 'http://serienjunkies.org/californication/californication-season-3-dvdrip-xvid/');
    
    $assertSeason('de', 4, 'http://serienjunkies.org/californication/californication-staffel-4-hdtvweb-dl-xvid720p/');
    $assertSeason('en', 4, 'http://serienjunkies.org/californication/californication-season-4-dvdriphdtvweb-dl-xvid720p/');
    
    $assertSeason('en', 5, 'http://serienjunkies.org/californication/californication-season-5-hdtv-xvid/');
    
    return $seasons;
  }

  /**
   * @group firefly
   */
  public function testGrabFireflySeasons() {
    list($sjg, $tvShows) = $this->grab(function ($sjg) {
      return $sjg->getTvShows();
    });
    
    $firefly = $tvShows['Firefly'];

    list($sjg, $seasons) = $this->grab(function ($sjg) use ($firefly) {
      return $sjg->getSeasons($firefly);
    });
    
    $this->assertCount(1,$seasons); // die andere wird ignoriert
    $season = $seasons['de'][1];
    $this->assertEquals('http://serienjunkies.org/firefly/firefly-season-1-hdtv720p-x264/',$season->getLink());
    
    $season = $sjg->grab($season); // mit episoden füllen
    
    foreach ($season->getEpisodes() as $episode) {
      $this->assertNotEmpty($episode->getRelease(), 'Episode: '.$episode->getInfo().' hat kein Release');
      $this->assertNotEquals($episode->getRelease(), 'unknown', 'Episode: '.$episode->getInfo().' hat unknown format');
    }
    
    $uniques = new \Psc\DataInput();
    $debug = NULL;
    foreach ($season->getEpisodes() as $episode) {
      $uq = array($episode->getSeason()->getNum(),
                  $episode->getNum(),
                  $episode->getRelease(),
                  implode('|',$episode->getLanguages())
                  );
      
      $debug .= implode('.',$uq)."\n";
      if ($uniques->get($uq) === TRUE) {
        $this->fail('Unique-Key: '.\Psc\Code\Code::varInfo($uq).' würde verletzt werden! '.$debug);
      } else {
        $uniques->set($uq, TRUE);
      }
    }
  }

  /**
   * @group sj
   * @expectedException SerienLoader\Exception
   */
  public function testGetEpisodesThrowsExceptionWithoutLink() {
    $season = new Season();
    $this->grab(function ($sjg) use ($season) {
      $sjg->getEpisodes($season);
    });
  }

  /**
   * @group sj
   * @depends testGrabSeasons
   */
  public function testGetEpisodes(Array $seasons) {
    list($sjg, $episodes) = $this->grab(function ($sjg) use ($seasons) {
      $season = clone $seasons['de'][3];
      
      // wir wollen nach nichts filtern
      $season->setLanguage(NULL);

      return $sjg->getEpisodes($season);
    });
   
    $this->assertTrue(is_array($episodes));
    $this->assertEquals(4,count($episodes)); // 4 "Sektionen" von Formaten, da wir ja die DVDs überspringen
    
    /* ersteinmal testen wir die common assertions
    */
    foreach ($episodes as $sectionKey => $episodeSection) {
      foreach ($episodeSection as $episode) {
        $this->assertEquals(3, count($episode->getLinks()), 'Es wurde nicht die korrekte Anzahl an Links geparst');
        $this->assertGreaterThan(0,$episode->getNum());
        $this->assertNotEmpty($episode->getRelease());
        $this->assertInstanceOf('SerienLoader\Entities\Season',$episode->getSeason());
        $this->assertEquals(3, $episode->getSeason()->getNum());
        
        switch ($sectionKey) {
          case 0:
          case 2:
            $expectedLanguages = array('de','en');
            break;
          case 1:
          case 3:
            $expectedLanguages = array('de');
            break;
          default:
            throw new \Psc\Exception('Für '.$sectionKey.' ist keine erwartete Language defined');
        }
        $this->assertEquals($expectedLanguages,$episode->getLanguages());
        //print $episode->getRelease()."\n";
      }
      //print "\n\n";
    }

    
    $this->assertEquals(12,count($episodes[0]));
    $this->assertEquals(12,count($episodes[1]));
    $this->assertEquals(12,count($episodes[2]));
    $this->assertEquals(12,count($episodes[3]));
  }


  /**
   * @group sj
   * @depends testGrabSeasons
   */
  public function testGrabSeasonEpisodes(Array $seasons) {
    // hier wird nach language "de" gefiltert!
    list($sjg, $season) = $this->grab($seasons['de'][3]); // 2te path ist hier ohne link gesetzt
    
    $this->assertInstanceOf('SerienLoader\Entities\Season',$season);
    
    $this->assertEquals(4*12,count($season->getEpisodes())); // 2 seasons sind deutsch (und 2 sind deutsch+englisch)
  }

  /**
   * @param Epsiode|Season|TvShow|Closure
   */
  protected function grab($filter) {
    $this->sjg = $sjg = new SerienJunkiesGrabber($this->requestBundle);
      
    if ($filter instanceof \Closure) {
      $return = $filter($sjg);
    } else {
      $return = $sjg->grab($filter);
    }

    return array($sjg,$return);
  }
  
  protected function onNotSuccessfulTest(\Exception $e) {
    print \Psc\A::join($this->sjg->log, "\n  %s");
    throw $e;
  }
  
  public function testAESDecrypt() {
    $sjg = new SerienjunkiesGrabber();
    $jk = '33393233373536373131333133313536';
    $crypted = 'HFvWKaKcsOpAQiTM+Ud/YOSmp3LtSAMXqbnxZ2MAHSNeX8JBxRUBXXYFNoPHbmSZN/xJ4c5PKlv97IHQWZVcbQ==';
    $this->assertEquals('http://netload.in/dateicq3q5sB6KH/twoD-s08e05.rar.htm', $sjg->decrypt($crypted, $jk));
    
    
    /* POST
      passwords=autopostpw&crypted=f1%2FMKKKM74acE%2FGNLXgT1E0kXwOFZ6FV7%2FmAlwky1wlOc4h2NaO2IA6aVQN1oFtTre5a24TiZ1cP4oICulFoB%2FNbwoHN6EekBUoI9ttwt6WmuetjeSYX86wEMVOfT5Fw%2Folk5GSlyKubtRANfYXOcKi2uW6vWoF2C6e9P650%2FbGhkDKRlV2eetoYG4y%2B7lqZ%2FD3HIDqwb6s0DBQZ7b69w02AFRXhNc7517G%2BOzY3YWS8YatNwlsopdzbItcppiWD&jk=function%20f%28%29%7Ba%3Dnew%20Array%28%273539%27%2C%273533%27%2C%273134%27%2C%273537%27%2C%273637%27%2C%273639%27%2C%273338%27%2C%273930%27%29%3Bvar%20n%3D%27%27%3Bfor%28var%20i%3D0%3Bi%3C%3D7%3Bi%2B%2B%29n%3Dn%2Ba%5Bi%5D%2Esplit%28%27%27%29%2Ereverse%28%29%2Ejoin%28%27%27%29%2Esplit%28%27%27%29%2Ereverse%28%29%2Ejoin%28%27%27%29%3Breturn%20n%3B%7D
    */
    $jk = '35393533313435373637363933383930';
    $crypted = "f1/MKKKM74acE/GNLXgT1E0kXwOFZ6FV7/mAlwky1wlOc4h2NaO2IA6aVQN1oFtTre5a24TiZ1cP4oICulFoB/NbwoHN6EekBUoI9ttwt6WmuetjeSYX86wEMVOfT5Fw/olk5GSlyKubtRANfYXOcKi2uW6vWoF2C6e9P650/bGhkDKRlV2eetoYG4y+7lqZ/D3HIDqwb6s0DBQZ7b69w02AFRXhNc7517G+OzY3YWS8YatNwlsopdzbItcppiWD";
    
    
    $this->assertEquals('http://filesonic.com/file/569390184/Alexis_Jordan_-_Alexis_Jordan_2011.rar
http://fileserve.com/file/tN54Hdn/Alexis_Jordan_-_Alexis_Jordan_2011.rar
http://share-online.biz/dl/J3PY4XQLRA', $sjg->decrypt($crypted, $jk));
  }
  
  public function testDecryptExtraction() {
    
    $html = '    <form style="display: none;" name="cnlform" action="http://127.0.0.1:9666/flash/addcrypted2" target="hidden" method="POST">
				<input type="hidden" name="source" value="http://download.serienjunkies.org/f-9b406fd83ce42866/nl_twoD-s08e05-rar.html">
				<input type="hidden" name="jk" value="function f(){ return \'33393233373536373131333133313536\';}">
				<input type="hidden" name="crypted" value="HFvWKaKcsOpAQiTM+Ud/YOSmp3LtSAMXqbnxZ2MAHSNeX8JBxRUBXXYFNoPHbmSZN/xJ4c5PKlv97IHQWZVcbQ==">

			</form>
            
        ';
    
    $sjg = new SerienjunkiesGrabber();    
    $info = $sjg->extractCrypted($html);
    $this->assertEquals(array('HFvWKaKcsOpAQiTM+Ud/YOSmp3LtSAMXqbnxZ2MAHSNeX8JBxRUBXXYFNoPHbmSZN/xJ4c5PKlv97IHQWZVcbQ==','33393233373536373131333133313536'), $info);
  }
  
}