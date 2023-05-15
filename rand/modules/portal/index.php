<?php
$page = 'index';
$parts = explode('/',$_SERVER['REQUEST_URI']);
$req = end($parts);
if(is_readable(__DIR__."/rand/{$req}.html")) $page = $req;
include "rand/{$page}.html";