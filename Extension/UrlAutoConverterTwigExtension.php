<?php

namespace Liip\UrlAutoConverterBundle\Extension;

class UrlAutoConverterTwigExtension extends \Twig_Extension
{
    protected $linkClass;
    protected $target;
    protected $debugMode;

    // @codeCoverageIgnoreStart
    public function getName()
    {
        return 'liip_urlautoconverter';
    }

    public function setLinkClass($class)
    {
        $this->linkClass = $class;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function setDebugMode($debug)
    {
        $this->debugMode = $debug;
    }
    // @codeCoverageIgnoreEnd

    public function getFilters()
    {
        return array(
            'converturls' => new \Twig_Filter_Method($this, 'autoConvertUrls', array('is_safe' => array('html')))
        );
    }

    /**
     * method that finds different occurrences of urls or email addresses in a string
     * @param string $string input string
     * @return string with replaced links
     */
    public function autoConvertUrls($string)
    {
        $pattern = '/(href=")?([-a-zA-Z0-9@:%_\+.~#?&\/\/=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)?)/';
        $stringFiltered = preg_replace_callback($pattern, array($this, 'callbackReplace'), $string);

        return $stringFiltered;
    }

    public function callbackReplace($matches)
    {
        if ($matches[1] !== '') {
            return $matches[0]; // don't modify existing <a href="">links</a>
        }

        $url = $matches[2];
        $urlWithPrefix = $matches[2];

        if (strpos($url, '@') !== false) {
            $urlWithPrefix = 'mailto:'.$url;
        } else if (strpos($url, 'https://') === 0 ) {
            $urlWithPrefix = $url;
        } else if (strpos($url, 'http://') !== 0) {
            $urlWithPrefix = 'http://'.$url;
        }

        $style = ($this->debugMode) ? ' style="color:#00ff00"' : '';

        $urlComponents = parse_url($url);

        $displayUrl = '';

        if (array_key_exists('scheme', $urlComponents)) {

            $displayUrl .= "<span>{$urlComponents['scheme']}://</span><wbr></wbr><span style=\"display: inline-block;\"></span>";

        }

        if (array_key_exists('host', $urlComponents)) {
            $displayUrl .= "<span>{$urlComponents['host']}</span><wbr></wbr><span style=\"display: inline-block;\"></span>";
        }

        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', rtrim($path, '/'));

        $i = 0;

        foreach ($segments as $segment) {

            $displayUrl .= "<span>{$segment}";
            $displayUrl .= (count($segments) != $i + 1) ? "/" : null;
            $displayUrl .= "</span><wbr></wbr><span style=\"display: inline-block;\"></span>";

            $i++;
        }

        if (array_key_exists('query', $urlComponents)) {

            $querySegments = explode('&', $urlComponents['query']);

            $displayUrl .= '?';

            $i = 0;

            foreach ($querySegments as $segment) {

                if ($i == 0) {

                    $displayUrl .= "<span>{$segment}</span><wbr></wbr><span style=\"display: inline-block;\"></span>";

                } else {

                    $displayUrl .= "<span>&{$segment}</span><wbr></wbr><span style=\"display: inline-block;\"></span>";

                }

                $i++;
            }

        }

        return '<a href="'.$urlWithPrefix.'" rel="nofollow" class="'.$this->linkClass.'" target="'.$this->target.'"'.$style.'>'.$displayUrl.'</a>';
    }
}
