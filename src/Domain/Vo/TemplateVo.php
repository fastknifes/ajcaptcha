<?php
declare(strict_types=1);

namespace Fastknife\Domain\Vo;
use Fastknife\Utils\MathUtils;
use Intervention\Image\Image;
class TemplateVo extends ImageVo
{

    private $borderBoth = [];

    /**
     * @var OffsetVo
     */
    public $offset;



    /**
     * @return OffsetVo
     */
    public function getOffset(): OffsetVo
    {
        return $this->offset;
    }

    /**
     * @param OffsetVo $offset
     */
    public function setOffset(OffsetVo $offset): void
    {
        $this->offset = $offset;
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
     * 是否为边界
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function isBoundary(int $x, int $y): bool
    {
        $image = $this->image;
        if ($x >= $image->width() - 1 || $y >= $image->height() - 1) {
            return false;
        }
        $right = [$x + 1, $y];
        $down = [$x, $y + 1];


        $isOpacity = $this->isOpacity($x, $y);

        if($isOpacity && !$this->isOpacity(...$right) || !$isOpacity && $this->isOpacity(...$right)){
            //右边框, 当前点是外边框，右边是透明

            return true;
        }

        if($isOpacity && !$this->isOpacity(...$down) || !$isOpacity && $this->isOpacity(...$down)){

            //下边框
            return true;
        }


        return false;
    }




}
