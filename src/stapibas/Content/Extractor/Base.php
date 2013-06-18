<?php
namespace stapibas;

class Content_Extractor_Base
{
    public function __construct(Logger $log)
    {
        $this->log = $log;
    }

    protected function extractAuthorData($hentry, $xpath, &$data, $source)
    {
        $data['author_name']  = null;
        $data['author_image'] = null;
        $data['author_url']   = null;

        $authors = $xpath->evaluate(
            './/*[' . $this->xpc('p-author') . ']'
        );
        if ($authors->length != 1) {
            //no p-author, so use page author data
            $data['author_name'] = $this->getFirst(
                '/*[self::html or self::h:html]/*[self::head or self::h:head]'
                . '/*[(self::meta or self::h:meta) and @name="author"]',
                'content', $hentry, $xpath
            );
        
            $data['author_url'] = 
                $this->absUrl(
                    $this->getFirst(
                        '/*[self::html or self::h:html]/*[self::head or self::h:head]'
                        . '/*[(self::link or self::h:link) and @rel="author"]',
                        'href', $hentry, $xpath
                    ),
                    $source
                );
            return;
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
        $data['author_url'] = $this->absUrl(
            $this->getFirst(
                './/*[' . $this->xpc('u-url') . ']',
                'href', $author, $xpath
            ),
            $source
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

    protected function absUrl($url, $source)
    {
        if ($url === null) {
            return null;
        }
        $sourceUrl = new \Net_URL2($source);
        return (string)$sourceUrl->resolve($url);
    }

}
?>
