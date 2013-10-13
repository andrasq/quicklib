<?

/**
 * General-purpose source path rewriter, for class names that do not map
 * directly to a pathname.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * Rewriting is done with splices.  A splice removes a portion of a list
 * and replaces it with different content.  The splice can act as an insert
 * (ie, the removed portion be of length zero), and the splice can act as
 * a delete (ie, the replace portion be empty).
 *
 * A splice is specified as an array ('offset' => $o, 'count' => $n, 'text' => $str),
 * indicating to replace the 'count' classname path components from position 'offset'
 * with a single component with value 'text'.  If count = 0, 'text' is inserted at 'offset'.
 * If text is empty, count components are removed starting at offset.  If offset is
 * negative, the offset is that many items from the end of the list.
 *
 * NOTE: the splices are applied in order.  Since the element counts and offsets
 * change with each splice, splices must be aware of each other's actions.
 * Valid counts and offsets range from 0 to count($pathComponents)
 *
 * Array splicing is perhaps not the quickest, but is very general-purpose.
 *
 * 2013-02-12 - AR.
 */
class Quick_Autoloader_PathRewriter
{
    protected $_prefix, $_prefixlen, $_suffix, $_suffixlen;
    protected $_splices;

    public function __construct( $matchPrefix, $matchSuffix, Array $applySplices ) {
        foreach ($applySplices as & $splice)
            $this->_normalizeSpliceRule($splice);
        $this->_prefix = $matchPrefix;
        $this->_prefixlen = strlen("$matchPrefix");
        $this->_suffix = $matchSuffix;
        $this->_suffixlen = strlen("$matchSuffix");
        $this->_splices = $applySplices;
    }

    /**
     * Rewrite the class name into a source path using the configured rewrite rules.
     */
    public function buildPath( $classname ) {
        // the class name must match the specified prefix and suffix to be a candidate for loading
        // note: comparison is on strings, so include the separator '_', eg suffix = '_Gateway'
        if ($this->_prefix > '' && strncmp($classname, $this->_prefix, $this->_prefixlen) !== 0)
            return false;
        if ($this->_suffix > '' && substr_compare($classname, $this->_suffix, -$this->_suffixlen) !== 0)
            return false;

        $pathComponents = preg_split('/[\\\_]/', $classname);
        foreach ($this->_splices as $splice)
            $this->_applySpliceRule($pathComponents, $splice);

        return $pathComponents ? implode('/', $pathComponents) : false;
    }

    protected function _normalizeSpliceRule( & $splice ) {
        if (!isset($splice['offset']))
            throw new InvalidArgumentException("invalid splice, missing offset: " . print_r($splice, true));
        if (!isset($splice['offset'])) $splice['offset'] = 0;
        if (!isset($splice['count'])) $splice['count'] = 0;
        if (!isset($splice['text'])) $splice['text'] = null;
    }

    protected function _applySpliceRule( & $list, $splice ) {
        if (isset($splice['text']))
            array_splice($list, $splice['offset'], $splice['count'], array($splice['text']));
        else
            array_splice($list, $splice['offset'], $splice['count']);
    }
}

