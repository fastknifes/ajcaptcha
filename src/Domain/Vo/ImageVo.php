<?php

namespace Fastknife\Domain\Vo;

use Fastknife\Utils\ImageUtils;
use Fastknife\Utils\MathUtils;

abstract class ImageVo
{
    /**
     * @var resource|\GdImage
     */
    public $image;

    public $src;

    private $pickMaps = [];

    private $finishCallback;

    public function __construct($src)
    {
        $this->src = $src;
        $this->initImage($src);
    }

    public function initImage($src)
    {
        // 如果 $src 是 null (Drawing 模式手动注入) 则不处理
        if ($src !== null) {
            $this->image = ImageUtils::read($src);
        }
    }

    /**
     * 获取图片中某一个位置的rgba值
     * @param $x
     * @param $y
     * @return array [r, g, b, a] (a: 0-1 float for compat, or 0-127 int? Intervention v2 returns a as 0-1 float where 0 is transparent?)
     * Intervention v2 pickColor:
     * array(4) {
     *   [0] => int(255)
     *   [1] => int(255)
     *   [2] => int(255)
     *   [3] => float(1) // 1 is opaque, 0 is transparent? Or 0 is opaque?
     * }
     * Intervention v2: alpha 0 (transparent) -> 1 (opaque).
     * GD: 0 (opaque) -> 127 (transparent).
     *
     * 为了兼容旧代码 check 逻辑: $this->getPickColor($x, $y)[3] > 0.5 (opaque)
     * 我们需要把 GD 的 0-127 转换为 0-1 (opaque).
     * GD: 0 -> 1.0, 127 -> 0.0
     */
    public function getPickColor($x, $y): array
    {
        if (!isset($this->pickMaps[$x][$y])) {
            $rgb = imagecolorat($this->image, (int)$x, (int)$y);
            $colors = imagecolorsforindex($this->image, $rgb);
            
            // Convert GD alpha (0-127 transparent) to Intervention alpha (0-1 opaque)
            // 127 (transparent) -> 0
            // 0 (opaque) -> 1
            $alpha = 1 - ($colors['alpha'] / 127);
            
            $this->pickMaps[$x][$y] = [
                $colors['red'],
                $colors['green'],
                $colors['blue'],
                $alpha
            ];
        }
        return $this->pickMaps[$x][$y];
    }


    /**
     * 设置图片指定位置的颜色值
     * @param array $color [r, g, b, a] (Intervention format)
     * @param int $x
     * @param int $y
     */
    public function setPixel($color, $x, $y)
    {
        // Convert Intervention alpha to GD alpha
        // 1 (opaque) -> 0
        // 0 (transparent) -> 127
        $alpha = 0;
        if (isset($color[3])) {
            $alpha = (int)((1 - $color[3]) * 127);
        }
        
        $col = imagecolorallocatealpha($this->image, $color[0], $color[1], $color[2], $alpha);
        imagesetpixel($this->image, (int)$x, (int)$y, $col);
        // imagecolorallocatealpha 可能会耗尽调色板 (对于真彩色不需要担心)
    }

    /**
     * @param int $x
     * @param int $y
     * @return array
     */
    public function getBlurValue(int $x, int $y): array
    {
        $image = $this->image;
        $red = [];
        $green = [];
        $blue = [];
        $alpha = [];
        foreach ([
                     [0, 1], [0, -1],
                     [1, 0], [-1, 0],
                     [1, 1], [1, -1],
                     [-1, 1], [-1, -1],
                 ] as $distance) //边框取5个点，4个角取3个点，其余取8个点
        {
            $pointX = $x + $distance[0];
            $pointY = $y + $distance[1];
            if ($pointX < 0 || $pointX >= $image->getWidth() || $pointY < 0 || $pointY >= $image->height()) {
                continue;
            }
            [$r, $g, $b, $a] = $this->getPickColor($pointX, $pointY);
            $red[] = $r;
            $green[] = $g;
            $blue[] = $b;
            $alpha[] = $a;
        }
        return [MathUtils::avg($red), MathUtils::avg($green), MathUtils::avg($blue), MathUtils::avg($alpha)];
    }
    /**
     * @param int $x
     * @param int $y
     * @return array
     */
    public function getBlurValue(int $x, int $y): array
    {
        $image = $this->image;
        $red = [];
        $green = [];
        $blue = [];
        $alpha = [];
        $w = imagesx($image);
        $h = imagesy($image);
        
        foreach ([
                     [0, 1], [0, -1],
                     [1, 0], [-1, 0],
                     [1, 1], [1, -1],
                     [-1, 1], [-1, -1],
                 ] as $distance) //边框取5个点，4个角取3个点，其余取8个点
        {
            $pointX = $x + $distance[0];
            $pointY = $y + $distance[1];
            if ($pointX < 0 || $pointX >= $w || $pointY < 0 || $pointY >= $h) {
                continue;
            }
            [$r, $g, $b, $a] = $this->getPickColor($pointX, $pointY);
            $red[] = $r;
            $green[] = $g;
            $blue[] = $b;
            $alpha[] = $a;
        }
        return [MathUtils::avg($red), MathUtils::avg($green), MathUtils::avg($blue), MathUtils::avg($alpha)]; // Note: avg returns int, but alpha is float. MathUtils::avg casts to int?
        // MathUtils::avg returns intval. So alpha 0.5 -> 0.
        // 这会导致半透明信息丢失。
        // 原有逻辑: MathUtils::avg($alpha) -> intval. 
        // 看来原有逻辑本身就不支持半透明模糊。
        // 暂时保持兼容。
    }


    /**
     * 是否不透明
     * @param $x
     * @param $y
     * @return bool
     */
    public function isOpacity($x, $y): bool
    {
        return $this->getPickColor($x, $y)[3] > 0.5;
    }

    /**
     * 是否为边框
     * @param bool $isOpacity
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function isBoundary(bool $isOpacity, int $x, int $y): bool
    {
        $image = $this->image;
        $w = imagesx($image);
        $h = imagesy($image);
        
        if ($x >= $w - 1 || $y >= $h - 1) {
            return false;
        }
        $right = [$x + 1, $y];
        $down = [$x, $y + 1];
        if (
            $isOpacity && !$this->isOpacity(...$right)
            || !$isOpacity && $this->isOpacity(...$right)
            || $isOpacity && !$this->isOpacity(...$down)
            || !$isOpacity && $this->isOpacity(...$down)
        ) {
            return true;
        }
        return false;
    }

    /**
     * 模糊图片
     * @param $targetX
     * @param $targetY
     */
    public function vagueImage($targetX, $targetY)
    {
        $blur = $this->getBlurValue($targetX, $targetY);
        $this->setPixel($blur, $targetX, $targetY);
    }


    /**
     * @return array
     */
    public function getPickMaps(): array
    {
        return $this->pickMaps;
    }

    /**
     * @param array $pickMaps
     */
    public function setPickMaps(array $pickMaps): void
    {
        $this->pickMaps = $pickMaps;
    }

    /**
     * 提前初始化像素
     */
    public function preparePickMaps()
    {
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $this->getPickColor($x, $y);
            }
        }
    }

    public function setFinishCallback($finishCallback){
        $this->finishCallback = $finishCallback;
    }

    public function __destruct()
    {
        if(!empty($this->finishCallback) && $this->finishCallback instanceof \Closure){
            ($this->finishCallback)($this);
        }
        // 释放 GD 资源
        ImageUtils::destroy($this->image);
    }
}
