<?

/**
 * Object that gathers REST response values for return.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Rest_Response {
    public function setStatus($status, $message = "");
    public function getStatus();
    public function getMessage();
    public function setContent($str);
    public function setContentFile($filename);
    public function hasContent();
    public function hasValues();
    public function setValue($name, $value, $separator = null);
    public function getValue($name);
    public function unsetValues();
    public function nameCollection($name, $fieldname, $separator = null);
    public function appendCollection($name, $value);
    public function getResponse($includeHeaders=false);
    public function emitResponse();
}
