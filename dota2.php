<?php

require 'monitor.php';
$memcache = getMemCache();
$all_anchors = getAnchors();
display($all_anchors, $memcache, "DOTA2");
