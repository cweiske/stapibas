<?php
namespace stapibas;

class Content_Extractor_Link extends Content_Extractor_Base
{
    /**
     * Try to extract link data from HTML
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
            '//*[(' . $this->xpc('h-entry') . ' or ' . $this->xpc('hentry') . ')'
            . ' and //*[' . $this->xpc('e-content') . ']'
            . ']'
        );

        $sourceUrl = new \Net_URL2($source);
        $found = false;

        foreach ($hentries as $hentry) {
            $links = $xpath->query('.//*[self::a or self::h:a]', $hentry);
            foreach ($links as $link) {
                $url = (string)$sourceUrl->resolve(
                    $link->attributes->getNamedItem('href')->nodeValue
                );
                if ($url == $target) {
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) {
            return null;
        }

        $data = array('title' => null);
        $hentry = $hentries->item(0);

        $this->extractAuthorData($hentry, $xpath, $data, $source);
        $data['title'] = trim(
            $this->getFirst(
                './/*[' . $this->xpc('p-name') . ']', null, $hentry, $xpath
            )
        );
        if ($data['title'] === null) {
            //use page title
            $data['title'] = trim(
                $this->getFirst(
                    '/*[self::html or self::h:html]/*[self::head or self::h:head]'
                    . '/*[self::title or self::h:title]',
                    null, $hentry, $xpath
                )
            );
        }

        return $data;
    }
}
?>
