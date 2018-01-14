<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 13/01/2018
 * Time: 21:36
 */

namespace Peru\Sunat;

/**
 * Class HtmlParser
 */
final class HtmlParser
{
    /**
     * Parse html to dictionary.
     *
     * @param string $html
     *
     * @return array|bool
     */
    public function parse($html)
    {
        $xp = $this->getXpathFromHtml($html);
        $table = $xp->query('./html/body/table[1]');

        if ($table->length == 0) {
            return false;
        }

        $nodes = $table->item(0)->childNodes;
        $dic = $this->getKeyValues($nodes, $xp);

        $dic['Phone'] = $this->getPhone($html);

        return $dic;
    }

    private function getKeyValues(\DOMNodeList $nodes, \DOMXPath $xp)
    {
        $dic = [];
        $temp = '';
        foreach ($nodes as $item) {
            /** @var $item \DOMNode */
            if ($this->isNotElement($item)) {
                continue;
            }
            $i = 0;
            foreach ($item->childNodes as $item2) {
                /** @var $item2 \DOMNode */
                if ($this->isNotElement($item2)) {
                    continue;
                }
                ++$i;
                if ($i == 1) {
                    $temp = trim($item2->textContent);
                    continue;
                }

                $dic[$temp] = $this->getContent($xp, $item2);
                $i = 0;
            }
        }

        return $dic;
    }

    private function getXpathFromHtml($html)
    {
        $dom = new \DOMDocument();
        $prevState = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($prevState);

        return new \DOMXPath($dom);
    }

    private function getPhone($html)
    {
        $arr = [];
        $patron = '/<td class="bgn" colspan=1>Tel&eacute;fono\(s\):<\/td>[ ]*-->\r\n<!--\t[ ]*<td class="bg" colspan=1>(.*)<\/td>/';
        preg_match_all($patron, $html, $matches, PREG_SET_ORDER);
        if (count($matches) > 0) {
            $phones = explode('/', $matches[0][1]);
            foreach ($phones as $phone) {
                if (empty($phone)) {
                    continue;
                }
                $arr[] = trim($phone);
            }
        }

        return $arr;
    }

    private function getContent(\DOMXPath $xp, \DOMNode $node)
    {
        $select = $xp->query('./select', $node);
        if ($select->length > 0) {
            $arr = [];
            $options = $select->item(0)->childNodes;
            foreach ($options as $opt) {
                /** @var $opt \DOMNode */
                if ($opt->nodeName != 'option') {
                    continue;
                }
                $arr[] = trim($opt->textContent);
            }
            return $arr;
        }

        return trim($node->textContent);
    }

    private function isNotElement(\DOMNode $node)
    {
        return $node->nodeType !== XML_ELEMENT_NODE;
    }
}