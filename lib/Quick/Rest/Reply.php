<?

/**
 * REST api reply.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Rest_Reply {
    public function getStatus();
    public function getMessage();
    public function getHeaders();
    public function getBody();
    public function getReply();
    //public function setReply($replyStr, $status=null, $message=null);
}
