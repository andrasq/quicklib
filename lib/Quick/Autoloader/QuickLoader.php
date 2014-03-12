<?

/**
 * Micro autoloader, for when all classes live in a single directory.
 * Not stackable, it breaks if it cannot load the class file.
 * Good for bootstrapping, for slim apps built solely out of quicklib parts,
 * and for benchmarks.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-12-22 - AR.
 */

class Quick_Autoloader_QuickLoader {
    public function __construct( $dir, $prefix = "" ) {
        $this->_dirname = $dir;
        $this->_prefix = $prefix;
    }

    // Hand off autoloading to the general-purpose Quick_Autoloader.
    // Note that this autoloader is usable as a plug-in for MiniLoader,
    // a redesigned general-purpose autoloader.
    public function bootstrap( ) {
//        require_once 'MiniLoader.php';
        $this->register();
        if (0) {
            $loader = Quick_Autoloader_MiniLoader::getInstance();
            $loader
                ->setMapper($this)
                //->setQuickSearchTree($this->_dirname, ".php")
                ->register();
        }
        else {
            $loader = Quick_Autoloader::getInstance();
            $loader
                ->addSearchTree($this->_dirname, ".php")
                ->register();
        }
        $this->unregister();
        return $loader;
    }

    public function map( $classname ) {
        if ($this->_prefix && strpos($classname, $this->_prefix) !== 0)
            return false;
        $classpath = str_replace('_', '/', $classname);
        return "{$this->_dirname}/{$classpath}.php";
    }

    public function autoload( $classname ) {
        if ($this->_prefix && strpos($classname, $this->_prefix) !== 0)
            return false;
        $classpath = str_replace('_', '/', $classname);
        require "{$this->_dirname}/{$classpath}.php";
    }

    public function register( ) {
        spl_autoload_register(array($this, 'autoload'));
    }

    public function unregister( ) {
        spl_autoload_unregister(array($this, 'autoload'));
    }
}
