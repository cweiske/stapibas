<?php
namespace stapibas;
require_once 'stapibas/autoloader.php';

class Content_Extractor_CommentTest extends \PHPUnit_Framework_TestCase
{
    public function testExtract()
    {
        $doc = new \DOMDocument();
        @$doc->loadHtmlFile(__DIR__ . '/data/aaron-parecki.html');
        $source = 'http://aaronparecki.com/replies/2013/04/19/2/indieweb';
        $target = 'http://eschnou.com/entry/testing-indieweb-federation-with-waterpigscouk-aaronpareckicom-and--62-24908.html';
        
        $logger = new Logger();
        $logger->debug = true;
        $cec = new Content_Extractor_Comment($logger);
        $comment = $cec->extract($doc, $source, $target);
        
        $this->assertNotNull($comment, 'No extracted data');
        $this->assertEquals(
            'Aaron Parecki',
            $comment['author_name'],
            'author name error'
        );
        $this->assertEquals(
            'http://aaronparecki.com/images/aaronpk.png',
            $comment['author_image']
        );
        $this->assertEquals(
            'http://aaronparecki.com/',
            $comment['author_url']
        );

        $this->assertEquals(
            <<<HTM
<a href="http://eschnou.com/">@eschnou</a> It worked! Now here's a reply! <a href="/tag/indieweb">#<span class="p-category">indieweb</span></a>
HTM
            ,
            $comment['content']
        );
    }
}
?>
