<?php

require_once '../vendor/autoload.php';

$small = 'http://jbbs.shitaraba.net/bbs/read.cgi/otaku/14504/1426429320/';
$yaranaio = 'http://jbbs.shitaraba.net/bbs/read.cgi/otaku/14429/1390143374';
$test = 'http://jbbs.shitaraba.net/bbs/read.cgi/otaku/17372/1446491727/';

$driver = new \Localdisk\BBS\Drivers\ShitarabaDriver($yaranaio);

//var_dump($driver->comments());

$res = $driver->post('test', 'sage', 'てすと');

var_dump(mb_convert_encoding($res->body, 'UTF-8', 'EUC-JP'));