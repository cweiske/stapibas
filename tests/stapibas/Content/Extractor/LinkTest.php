<?php
namespace stapibas;
require_once 'stapibas/autoloader.php';

class Content_Extractor_LinkTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractShadowBox()
    {
        $doc = new \DOMDocument();
        @$doc->loadHtmlFile(__DIR__ . '/data/shadowbox-popup-positioning.htm');
        $source = 'http://www.bogo/tagebuch/shadowbox-popup-positioning.htm';
        $target = 'http://www.bogo/tagebuch/demo/shadowbox-manual-positioning/static.html';
        
        $logger = new Logger();
        $logger->debug = true;
        $cel = new Content_Extractor_Link($logger);
        $link = $cel->extract($doc, $source, $target);
        
        $this->assertNotNull($link, 'No extracted data');

        $this->assertEquals(
            'Shadowbox: Manual popup positioning',
            $link['title']
        );

        $this->assertEquals('Christian Weiske', $link['author_name']);
        $this->assertNull($link['author_image']);
        $this->assertEquals('http://www.bogo/', $link['author_url']);
    }

    public function testExtractXmlShadowBox()
    {
        $doc = new \DOMDocument();
        @$doc->load(__DIR__ . '/data/shadowbox-popup-positioning.htm');
        $source = 'http://www.bogo/tagebuch/shadowbox-popup-positioning.htm';
        $target = 'http://www.bogo/tagebuch/demo/shadowbox-manual-positioning/static.html';
        
        $logger = new Logger();
        $logger->debug = true;
        $cel = new Content_Extractor_Link($logger);
        $link = $cel->extract($doc, $source, $target);
        
        $this->assertNotNull($link, 'No extracted data');

        $this->assertEquals(
            'Shadowbox: Manual popup positioning',
            $link['title']
        );

        $this->assertEquals('Christian Weiske', $link['author_name']);
        $this->assertNull($link['author_image']);
        $this->assertEquals('http://www.bogo/', $link['author_url']);
    }

    public function testExtractLaurent()
    {
        $doc = new \DOMDocument();
        @$doc->loadHtmlFile(__DIR__ . '/data/laurent-eschenauer.html');
        $source = 'http://eschnou.com/entry/testing-indieweb-federation-with-waterpigscouk-aaronpareckicom-and--62-24908.html';
        $target = 'http://indiewebcamp.com';
        
        $logger = new Logger();
        $logger->debug = true;
        $cel = new Content_Extractor_Link($logger);
        $link = $cel->extract($doc, $source, $target);
        
        $this->assertNotNull($link, 'No extracted data');

        $this->assertEquals(
            'Testing #indieweb federation with @waterpigs.co.uk, @aaronparecki.com and @indiewebcamp.com !',
            $link['title']
        );

        $this->assertNull($link['author_name']);
        $this->assertNull($link['author_image']);
        $this->assertNull($link['author_url']);
    }

}
?>
