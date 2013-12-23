<?

/**
 * Bundle that formats itself as terse XML without nelines or indentation.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_Bundle_XmlTerse
    extends Quick_Data_Bundle_Xml
{
    protected $_nl = "";
    protected $_indent = "";

    // inherits all else
}
