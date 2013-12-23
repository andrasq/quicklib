<?

class Quick_Autoloader_EngineExposer
    extends Quick_Autoloader_Engine
{
    public function __construct( ) {
        parent::__construct();
    }
}

class Quick_Autoloader_EngineTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Autoloader_EngineExposer();
        $this->_cut->addSearchTree(TEST_ROOT . '/../lib');
    }

    public function testEmpty( ) {
    }

    public function xx_testAutoloadSpeed( ) {
        $cut = $this->_cut;
        $nloops = 50000;

        $tm = microtime(true);
        // NOTE: must hack Engine.php to not throw an exception if class not found,
        // because class_exists(false) is a no-op once the class has been loaded
        for ($i=0; $i<$nloops; ++$i) class_exists('Quick_Foo', false);
        $tm = microtime(true) - $tm;
        echo "AR: $nloops class_exists() autoload not found (and not finding) in $tm sec\n";
        // 130k/s

        $tm = microtime(true);
        for ($i=0; $i<$nloops; ++$i) $cut->autoload('Quick_Test_Case');
        $tm = microtime(true) - $tm;
        echo "AR: $nloops class_exists() found in $tm sec\n";
        // 225k/s ...wow! php overhead??

        $tm = microtime(true);
        for ($i=0; $i<$nloops; ++$i) require_once(TEST_ROOT . '/../lib/Quick/Test/Case.php');
        $tm = microtime(true) - $tm;
        echo "AR: $nloops require_once in $tm sec\n";
        // 210k/s if already included... but much faster if not yet seen??
    }
}
