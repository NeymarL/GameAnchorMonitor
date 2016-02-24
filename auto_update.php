<?php

require 'monitor.php';

$memcache = getMemCache();
update($memcache, 3); // result store 3 minutes
