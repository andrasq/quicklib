<?

/**
 * Templated pathname argument extractor.
 *
 * For template /foo/{base}/index.php/{a}/{b} and path /foo/bar/index.php/1/2,
 * return params array('base' => 'bar', 'a' => '1','b' => '2').  It is an error
 * if any pair of corresponding path components do not match or if templated
 * parameters do not have corresponding values.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Request_PathParams
{
    protected $_template, $_path;

    public static function getParamsFromPath( $template, $path ) {
        $o = new self();
        return $o
            ->setTemplate($template)
            ->getParams($path);
    }

    public function setTemplate( $template ) {
        $this->_template = $this->_normalizePath($template);
        return $this;
    }

    public function getParams( $path ) {
        $params = array();
        $template = explode('/', $this->_template);
        $pathparts = explode('/', $this->_normalizePath($path));
        foreach ($template as $ix => $name) {
            if (!isset($pathparts[$ix]))
                throw new Quick_Rest_Exception("$path: exptected more arguments for template $this->_template");
            if ($name === $pathparts[$ix] && ($name === '' || $name[0] !== '{'))
                continue;
            if ($name[0] === '{')
                $params[trim($name, '{}')] = isset($pathparts[$ix]) ? $pathparts[$ix] : null;
            else
                throw new Quick_Rest_Exception("path params error: path $path does not match template $this->_template");
        }
        return $params;
    }

    protected function _normalizePath( $path ) {
        if ($path[0] !== '/') $path = "/$path";
        if (strpos($path, '//') !== false) $path = preg_replace(':/+:', '/', $path);
        return $path;
    }
}
