<?php

/**
 * Simple little autoloader.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-09 - AR.
 */

class Quick_Autoloader_Engine
{
    protected $_nodes = array();
    protected $_classes = array();
    protected $_installed = false;

    protected function __construct( ) {
    }

    public static function getInstance( ) {
        static $instance;
        return $instance ? $instance : $instance = new Quick_Autoloader_Engine();
    }

    public function addClass( $classname, $sourcefile ) {
        $this->_classes[$classname] = $sourcefile;
        return $this;
    }

    public function addSearchPath( $dirname, $extensions = ".class.php,.interface.php" ) {
        // caller should verify paths, autoloader just tests if file exists
        $this->_nodes[] = array('mode' => 'flat', 'path' => $dirname, 'extensions' => explode(',', $extensions) );
        return $this;
    }

    public function addSearchTree( $dirname, $extensions = ".php" ) {
        // caller should verify paths, autoloader just tests if file exists
        $this->_nodes[] = array('mode' => 'tree', 'path' => $dirname, 'extensions' => explode(',', $extensions));
        return $this;
    }

    public function addCallback( $dirname, $callback, $extensions = ".php" ) {
        if (!is_callable($callback))
            throw new Exception("mb_autoloader: class path builder callback is not callable");
        $this->_nodes[] = array(
            'mode' => 'build', 'path' => $dirname, 'extensions' => explode(',', $extensions), 'builder' => $callback
        );
        return $this;
    }


    public function autoload( $classname ) {
        if (isset($this->_classes[$classname])) {
            if (!$this->_loadClassFromPath($this->_classes[$classname], $classname, array("")))
                throw new Exception("$classname: not defined by registered source file {$this->_classes[$classname]}");
            return true;
        }
        else {
            if (($p = strrpos($classname, '\\')) !== false) {
                $namespace = substr($classname, 0, $p);
                $classfile = substr($classname, $p);
            }
            else {
                $namespace = '';
                $classfile = $classname;
            }
            foreach ($this->_nodes as $node) {
                if ($node['mode'] === 'flat') {
                    // 183k lookups / sec if found by 1st node, 100k by 2nd, 70k by 3rd, 52k by 4th (one extension)
                    if ($this->_loadClassFromPath("{$node['path']}/$classfile", $classname, $node['extensions'])) {
                        return true;
                    }
                }
                elseif ($node['mode'] === 'tree') {
                    // 159k lookups / sec if found by 1st node, 86k by 2nd, 59k by 3rd, 45k by 4th (one extension)
                    $classnamepath = str_replace(array('::', '_', '\\'), '/', $classname);
                    if ($this->_loadClassFromPath("{$node['path']}/$classnamepath", $classname, $node['extensions'])) {
                        return true;
                    }
                }
                elseif ($node['mode'] === 'build') {
                    // 135k gateway paths generated / sec
                    $dirname = $node['path'];
                    if ($classnamepaths = call_user_func($node['builder'], $classname)) {
                        // the callback may return a string or an array of strings; if an array, each will be tried.
                        if (!is_array($classnamepaths)) $classnamepaths = array($classnamepaths);
                        foreach ($classnamepaths as $classnamepath) {
                            if ($dirname > '') $classnamepath = "$dirname/$classnamepath";
                            if ($this->_loadClassFromPath($classnamepath, $classname, $node['extensions'])) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function register( ) {
        return $this->install();
    }

    public function unregister( ) {
        if (function_exists('spl_autoload_unregister')) {
            return spl_autoload_unregister(array($this, 'autoload'));
        }
    }

    public function install( ) {
        if ($this->_installed)
            return $this;

        if (function_exists('spl_autoload_register')) {
            if (!spl_autoload_register(array($this, 'autoload')))
                throw new Exception("unable to register autoloader");
        }
        elseif (!function_exists('__autoload')) {
            global $__global_mb_autoloader;
            $__global_mb_autoloader = $this;
            function __autoload( $classname ) {
                global $__global_mb_autoloader;
                if ($__global_mb_autoloader->autoload($classname))
                    return;
                elseif (function_exists('spl_autoload'))
                    spl_autoload($classname);
                else
                    throw new Exception("$classname: no mechanism to autoload");
            }
        }
        else {
            throw new Exception("unable to install autoloader");
        }

        $this->_installed = true;
        return $this;
    }

    protected function _loadClassFromPath( $classnamepath, $classname, Array $extensions ) {
        if (!$extensions) $extensions = array('');
        foreach ($extensions as $ext) {
            if (file_exists($classpath = "$classnamepath{$ext}")) {
                if (!class_exists($classname, false) && !interface_exists($classname, false)) {
                    require $classpath;
                }
                if (class_exists($classname, false) || interface_exists($classname, false))
                    return true;
            }
        }
        return false;
    }
}
