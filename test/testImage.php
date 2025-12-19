<?php

use Fastknife\Domain\Factory;

require 'autoload.php';

$config = require '../src/config.php';

$type = $_GET['type'] ?? 'background'; // 获取 type 参数，默认为 background

function showBlock($type)
{
    global $config;
    $factory = new Factory($config);
    $blockImage = $factory->makeBlockImage();
    $blockImage->run();
    $blockImage->echo($type);
}

function showWord()
{
    global $config;
    $factory = new Factory($config);
    $blockImage = $factory->makeWordImage();
    $blockImage->run();
    $blockImage->echo();
}

// 简单的路由判断
if (isset($_GET['mode']) && $_GET['mode'] === 'word') {
    showWord();
} else {
    showBlock($type);
}
showBlock();