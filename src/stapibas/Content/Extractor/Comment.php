<?php
namespace stapibas;

class Content_Extractor_Comment extends Content_Extractor_Base
{
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
            . '//*[(self::a or self::h:a) and '
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

        $this->extractAuthorData($hentry, $xpath, $data, $source);
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
}
?>
