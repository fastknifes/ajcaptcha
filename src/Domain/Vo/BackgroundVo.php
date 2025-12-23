<?php

namespace Fastknife\Domain\Vo;

use Fastknife\Utils\MathUtils;

class BackgroundVo extends ImageVo
{
    private $blurAreas = [];


    public function __construct($src)
    {
        parent::__construct($src);
        // 设置消除锯齿
         imageantialias($this->image->getCore(), true);
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
            [$r, $g, $b, $a] = $this->getBlurPick($pointX, $pointY);
            $red[] = $r;
            $green[] = $g;
            $blue[] = $b;
            $alpha[] = $a;
        }
        return [MathUtils::avg($red), MathUtils::avg($green), MathUtils::avg($blue), MathUtils::avg($alpha)];
    }

    /**
     * 记录模糊图片
     * @param $targetX
     * @param $targetY
     */
    public function recordBlurImage($targetX, $targetY)
    {
        $blurColor = $this->getBlurValue($targetX, $targetY);
        $this->setBlurPick($targetX, $targetY, $blurColor);
    }

    public function getBlurPick($x, $y)
    {
        if (!isset($this->blurAreas[$x][$y])) {
            return $this->getPickColor($x, $y);
        }
        return $this->blurAreas[$x][$y];
    }

    public function setBlurPick($x, $y, $color)
    {
        $this->blurAreas[$x][$y] = $color;
    }

    public function blur(int $num = 1)
    {
        while ($num > 1 && $num <= 10) {
            foreach ($this->blurAreas as $x => $arr) {
                foreach ($arr as $y => $color) {
                    $this->recordBlurImage($x, $y);
                }
            }
            $num--;
        }
        $this->flush();
    }

    private function flush()
    {
        foreach ($this->blurAreas as $x => $arr) {
            foreach ($arr as $y => $color) {
                $this->setPixel($color, $x, $y);
            }
        }
    }


}