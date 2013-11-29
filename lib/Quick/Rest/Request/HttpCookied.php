<?

class Quick_Rest_Request_HttpCookied
    extends Quick_Request_Http
{
    protected $_cookies = array();

    public function setCookies( Array $cookies ) {
        $this->_cookies = $cookies;
    }

    public function getCookie( $name ) {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
    }
}
