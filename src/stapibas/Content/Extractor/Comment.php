<?php
namespace stapibas;

class Content_Extractor_Comment
{
    public function __construct(Logger $log)
    {
        $this->log = $log;
    }

    /**
     * Try to extract comment data from HTML
     *
     * @param object $doc HTML
     * @param string $source URL this HTML has been loaded from
     * @param string $target URL the reply should be to
     *
     * @return mixed NULL if nothing found, array if ok
     */
    public function extract(\DOMDocument $doc, $source, $target)
    {
        $xpath = $this->getXpath($doc);
        $hentries = $xpath->query(
            '//*[(' . $this->xpc('h-entry') . ' or ' . $this->xpc('hentry') . ') and '
            . '//a['
            . $this->xpc('u-in-reply-to') . ' and @href=' . $this->xpq($target)
            . ']'
            . ']'
        );

        if ($hentries->length == 0) {
            return null;
        }

        $data = array(
            'content' => null,
            'title'   => null,
        );
        $hentry = $hentries->item(0);

        $this->extractAuthorData($hentry, $xpath, $data, $doc);
        $content = $this->getFirst(
            './/*[' . $this->xpc('e-content') . ']', false, $hentry, $xpath
        );
        if ($content) {
            $data['content'] = $this->innerHtml($content);
        }
        $data['title'] = $this->getFirst(
            './/*[' . $this->xpc('p-name') . ']', false, $hentry, $xpath
        );

        return $data;
    }

    protected function extractAuthorData($hentry, $xpath, &$data, $d)
    {
        $data['author_name']  = null;
        $data['author_image'] = null;
        $data['author_url']   = null;

        $authors = $xpath->evaluate(
            './/*[' . $this->xpc('p-author') . ']'
        );
        if ($authors->length != 1) {
            return false;
        }

        $author = $authors->item(0);

        $data['author_name'] = $this->getFirst(
            './/*[' . $this->xpc('p-name') . ' or ' . $this->xpc('fn') . ']',
            null, $author, $xpath
        );
        $data['author_image'] = $this->getFirst(
            './/*[' . $this->xpc('u-photo') . ']',
            'src', $author, $xpath
        );
        $data['author_url'] = $this->getFirst(
            './/*[' . $this->xpc('u-url') . ']',
            'href', $author, $xpath
        );
    }

    protected function getFirst($xpathExpr, $attrName, $elem, $xpath)
    {
        $items = $xpath->evaluate($xpathExpr, $elem);
        if (!$items instanceof \DOMNodeList || $items->length == 0) {
            return null;
        }

        if ($attrName === false) {
            return $items->item(0);
        } else if ($attrName == null) {
            return $items->item(0)->nodeValue;
        } else {
            return $items->item(0)->attributes->getNamedItem($attrName)->nodeValue;
        }
    }

    protected function innerHtml($element)
    {
        $innerHTML = '';
        $children = $element->childNodes;
        foreach ($children as $child) {
            $tmp_dom = new \DOMDocument();
            $tmp_dom->appendChild($tmp_dom->importNode($child, true));
            $innerHTML .= rtrim($tmp_dom->saveHTML(), "\n");
        }
        return trim($innerHTML);
    }

    protected function getXpath($node)
    {
        $xpath = new \DOMXPath($node);
        $xpath->registerNamespace('h', 'http://www.w3.org/1999/xhtml');
        return $xpath;
    }

    protected function xpc($class)
    {
        return 'contains('
            . 'concat(" ", normalize-space(@class), " "),'
            . '" ' . $class . ' "'
            . ')';
    }

    protected function xpq($str)
    {
        return '"' . htmlspecialchars($str, ENT_QUOTES) . '"';
    }

}

?>
