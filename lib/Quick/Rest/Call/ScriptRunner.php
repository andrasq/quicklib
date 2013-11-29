<?

/**
 * Run the script named in $argv[0] in top-level context.
 * The script arguments should have been prepared into $argv.
 * The called script starts at the expected topmost environment,
 * and has full control over the globals until it finishes.
 *
 * Caller is responsible for saving/restoring globals to clean up.
 * Note that some side-effects are not reversible, eg changes to
 * namespaces, included files, declared classes, and $GLOBALS['GLOBALS'].
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

global $argv;
include $argv[0];
