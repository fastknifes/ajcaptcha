<?php

namespace Fastknife\Domain\Vo;

use Fastknife\Utils\MathUtils;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;

abstract class ImageVo
{
    /**
     * @var Image
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
        $this->image = ImageManagerStatic::make($src);
    }

    /**
     * 获取图片中某一个位置的rgba值
     * @param $x
     * @param $y
     * @return array
     */
    public function getPickColor($x, $y): array
    {
        if (!isset($this->pickMaps[$x][$y])) {
            $this->pickMaps[$x][$y] = $this->image->pickColor($x, $y);
        }
        return $this->pickMaps[$x][$y];
    }



    /**
     * 设置图片指定位置的颜色值
     */
    public function setPixel($color, $x, $y)
    {
        $this->image->pixel($color, $x, $y);
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
        $width = $this->image->getWidth();
        $height = $this->image->getHeight();
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
    }
}