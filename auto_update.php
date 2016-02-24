<?php

require 'monitor.php';

$memcache = getMemCache();
update($memcache, 300); // result store 5 minutes
