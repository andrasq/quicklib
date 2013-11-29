<?

class Quick_Db_Credentials_GlobalCsv
    implements Quick_Db_Credentials
{
    protected $_varname;

    public function __construct( $varname ) {
        if (!is_string($varname)) throw new Quick_Db_Exception("Db_Credentials_GlobalCsv requires a string");
        $this->_varname = $varname;
    }

    public static function create( $varname ) {
        //$class = get_called_class();          // php 5.3
        $class = __CLASS__;
        return new $class($varname);
    }

    public function getHost( ) {
        return $this->_getAttribute(__FUNCTION__);
    }
    public function getPort( ) {
        return $this->_getAttribute(__FUNCTION__);
    }
    public function getSocket( ) {
        return $this->_getAttribute(__FUNCTION__);
    }
    public function getUser( ) {
        return $this->_getAttribute(__FUNCTION__);
    }
    public function getPassword( ) {
        return $this->_getAttribute(__FUNCTION__);
    }
    public function getDatabase( ) {
        return $this->_getAttribute(__FUNCTION__);
    }

    protected function _getAttribute( $method ) {
        $varname = $this->_varname;
        global $$varname;
        return Quick_Db_Credentials_Discrete::create(${$this->_varname})->$method();
    }
}
