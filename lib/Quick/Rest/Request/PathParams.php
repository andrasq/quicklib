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
    extends Quick_Data_StringParams
{
    protected $_template, $_path;

    public static function getParamsFromPath( $template, $path ) {
        $o = new self();
        return $o
            ->setTemplate($template)
            ->getParams($path);
    }

    public function setTemplate( $template ) {
        return parent::setTemplate($this->_normalizePath($template));
    }

    public function getParams( $path ) {
        if (($params = parent::getParams($path = $this->_normalizePath($path))) !== false)
            return $params;
        else
            throw new Quick_Rest_Exception("path params error: path $path does not match template $this->_template");
    }

    protected function _normalizePath( $path ) {
        if ($path[0] !== '/') $path = "/$path";
        if (strpos($path, '//') !== false) $path = preg_replace(':/+:', '/', $path);
        return $path;
    }
}
