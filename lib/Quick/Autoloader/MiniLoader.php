<?php

/**
 * CAUTION: Pre-beta code, NOT under test.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Autoloader_MiniTreeLoader {
    //protected $_dirname, $_ext;
    public function __construct( $dir, $ext ) {
        $this->_dirname = $dir;
        $this->_ext = $ext;
    }
    public function map( $classname ) {
        return $this->_dirname . '/' . str_replace(array('::', '_', '\\'), '/', $classname) . $this->_ext;
    }
/*
    public function autoload( $classname ) {
        require $this->map($classname);
        //require $this->_dirname . '/' . str_replace(array('::', '_', '\\'), '/', $classname) . $this->_ext;
    }
    public function register( ) {
        spl_autoload_register(array($this, 'autoload'));
    }
*/
}

class Quick_Autoloader_MiniQuickTreeLoader {
    //protected $_dirname, $_ext;
    public function __construct( $dir, $ext ) {
        $this->_dirname = $dir;
        $this->_ext = $ext;
    }
    public function map( $classname ) {
        return $this->_dirname . '/' . str_replace('_', '/', $classname) . $this->_ext;
    }
/*
    public function autoload( $classname ) {
        require $this->map($classname);
        //require $this->_dirname . '/' . str_replace(array('::', '_', '\\'), '/', $classname) . $this->_ext;
    }
    public function register( ) {
        spl_autoload_register(array($this, 'autoload'));
    }
*/
}

class Quick_Autoloader_MiniPathLoader {
    //protected $_dirname, $_ext;
    public function __construct( $dir, $ext ) {
        $this->_dirname = $dir;
        $this->_ext = $ext;
    }
    public function map( $classname ) {
        return $this->_dirname . '/' . $classname . $this->_ext;
    }
}

class Quick_Autoloader_MiniCallbackLoader {
    //protected $_dir, $_ext, $_callback;
    public function __construct( $dir, $ext, $callback ) {
        $this->_dir = $dir;
        $this->_ext = $ext;
        $this->_callback = $callback;
    }
    public function map( $classname ) {
        $callback = $this->_callback;
        $files = is_array($callback) ? call_user_func($callback, $classname) : $callback($classname);
        if (!is_array($files))
            return array(isset($this->_dir) ? "$this->_dir/$file$ext" : "$file$ext");
        $ret = array();
        foreach ($files as $file) {
            $ret[] = isset($this->_dir) ? "$this->_dir/$file$ext" : "$file$ext";
        }
        return $ret;
    }
}

// 13.0k/s 1st hit (13.2k/s quick), 12.6k/s 4th hit w/ array replace
// 8.7k/s if file_exists test is added!! (or if @include is error suppressed)
// 11.4 if mapped and file_exists is used; 11.9 if quick mapper is used
class Quick_Autoloader_MiniLoader {
    protected $_classes, $_trees, $_mappers, $_multimappers;

    public static function getInstance( ) {
        static $instance;
        return $instance ? $instance : $instance = new Quick_Autoloader_MiniLoader();
    }

    public function setMapper( $mapper ) {
        $this->_mappers[] = $mapper;
        return $this;
    }

    public function setClass( $classname, $sourcefile ) {
        $this->_classes[$classname] = $sourcefile;
        return $this;
    }

    public function setQuickSearchTree( $dir, $ext ) {
        $this->_trees[] = new Quick_Autoloader_MiniQuickTreeLoader($dir, $ext);
        return $this;
    }

    public function setSearchTree( $dir, $ext ) {
        $this->_mappers[] = new Quick_Autoloader_MiniTreeLoader($dir, $ext);
        return $this;
    }

    public function setSearchPath( $dir, $ext ) {
        $this->_mappers[] = new Quick_Autoloader_MiniPathLoader($dir, $ext);
        return $this;
    }

    public function setSearchCallback( $dir, $ext, $callback ) {
        $this->_multimappers[] = new Quick_Autoloader_MiniCallbackLoader($dir, $ext, $callback);
        return $this;
    }

    public function autoload( $classname ) {
        if ($this->_classes && isset($this->_classes[$classname]))
            require $this->_classes[$classname];

        if ($this->_trees) foreach ($this->_trees as $mapper) {
            if (($file = $mapper->map($classname)) && file_exists($file)) {
                require $file;
                return true;
            }
        }

        if ($this->_mappers) foreach ($this->_mappers as $mapper) {
            if (($file = $mapper->map($classname)) && file_exists($file)) {
                require $file;
            }
        }
        // warning: only check for done after all mappers have checked their matching files! else can collide
        if (class_exists($classname) || interface_exists($classname)) return true;

        if ($this->_multimappers) foreach ($this->_multimappers as $mapper) {
            if ($files = $mapper->map($classname)) {
                foreach ($files as $file)
                    if (file_exists($file))
                        require $file;
            }
        }
        if (class_exists($classname) || interface_exists($classname)) return true;
    }

    public function register( ) {
        spl_autoload_register(array($this, 'autoload'));
        return $this;
    }

    public function unregister( ) {
        spl_autoload_unregister(array($this, 'autoload'));
        return $this;
    }
}
