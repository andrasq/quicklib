<?

/**
 * Interface that identifies classes that act as REST applications.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Rest_App
{
    public function getInstance($name);
    //public function getInjector();
    //public function restAction(Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app);
}
