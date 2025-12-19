<?php
declare(strict_types=1);

use Fastknife\Domain\Factory;

require __DIR__ . '/autoload.php';
$config = require __DIR__ . '/../src/config.php';

$factory = new Factory($config);
$image = $factory->makeBlockImage();
$image->run();

$bg = $image->getBackgroundVo()->image;
$slider = $image->getTemplateVo()->image;

$result = [
    'mode' => $factory->getConfig()['block_puzzle']['mode'] ?? 'drawing',
    'background' => ['w' => imagesx($bg), 'h' => imagesy($bg)],
    'slider' => ['w' => imagesx($slider), 'h' => imagesy($slider)],
];

header('Content-Type: application/json');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
