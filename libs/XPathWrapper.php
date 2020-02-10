<?php

class XPathWrapper {

    private $dom;
    private $xpath;

    public function __construct($strUrl) {
        /* Createa a new DomDocument object */
        $this->dom = new DomDocument;
        libxml_use_internal_errors(true);

        /* Load the HTML */
        try {
            $this->dom->loadHTMLFile($strUrl);
            libxml_clear_errors();

            /* Create a new XPath object */
            $this->xpath = new DomXPath($this->dom);
        } catch (Exception $e) {
            $this->xpath = null;
        }
    }

    function getXPathValueOf($strXpath) {
        /* Query all <td> nodes containing specified class name */
        if (empty($this->xpath)) {
            return '';
        }
        $nodes = $this->xpath->query($strXpath);

        /* Set HTTP response header to plain text for debugging output */
        // header("Content-type: text/plain");

        $value = '';
        foreach ($nodes as $i => $node) {
            // if (is_numeric($node->nodeValue) {
            //     $value += $node->nodeValue;
            // } else {
                $value = $node->nodeValue;
            // }
        }
        return $value;
    }
}