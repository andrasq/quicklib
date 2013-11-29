<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Db_SelectResult
{
    public function asList();
    public function asHash();
    public function asColumn($columndIndex = 0);
    public function asObject($class = 'Quick_Db_Object');

//    public function asBuilderObject(Quick_Db_ObjectBuilder $builder);
//    public function asClonedObject($obj);
//    public function asCallbackObject($callback);
}
