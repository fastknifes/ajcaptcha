<?php
declare(strict_types=1);

namespace Fastknife\Domain\Logic;

use Fastknife\Domain\Vo\PointVo;
use Fastknife\Utils\ImageUtils;
use Fastknife\Utils\RandomUtils;

/**
 * 文字码图片处理
 * Class WordCaptchaEntity
 * @package Fastknife\Domain\Entity
 */
class WordImage extends BaseImage
{

    /**
     * @var array
     */
    protected $wordList;
    
    /**
     * @var int 目标字数 (Service 层需要缓存前 N 个)
     */
    protected $targetCount = 0;


    /**
     * @return self
     */
    public function setWordList(array $wordList)
    {
        $this->wordList = $wordList;
        return $this;
    }

    public function getWordList()
    {
        return $this->wordList;
    }
    
    public function setTargetCount(int $count)
    {
        $this->targetCount = $count;
        return $this;
    }
    
    /**
     * 获取仅包含目标字的列表 (供 Service 使用)
     */
    public function getTargetWordList()
    {
        if ($this->targetCount > 0) {
            return array_slice($this->wordList, 0, $this->targetCount);
        }
        return $this->wordList;
    }
    
    /**
     * 获取仅包含目标字的坐标 (供 Service 使用)
     */
    public function getTargetPointList()
    {
        if ($this->targetCount > 0) {
            return array_slice($this->point, 0, $this->targetCount);
        }
        return $this->point;
    }



    public function run()
    {
        $this->inputWords();
        $this->makeWatermark($this->backgroundVo->image);
    }

    /**
     * 写入文字
     */
    protected function inputWords(){
        foreach ($this->wordList as $key => $word) {
            $point = $this->point[$key];
            // 使用 ImageUtils 替换 Intervention
            ImageUtils::text(
                $this->backgroundVo->image,
                $word,
                $point->x,
                $point->y,
                $this->fontFile,
                BaseData::FONTSIZE,
                RandomUtils::getRandomColor(), // [r, g, b, a]
                (float)RandomUtils::getRandomAngle(), // angle
                'left', // align (ImageUtils::text 修正逻辑基于左下角，这里我们假设 point 是左下角或者中心，原逻辑是 center/center)
                // 原逻辑 align('center') valign('center')。
                // ImageUtils::text 实现了 center/center 修正。
                'center'
            );
        }
    }

    /**
     * 返回前端需要的格式
     * @return string Base64
     */
    public function response()
    {
        return ImageUtils::outputBase64($this->getBackgroundVo()->image, 'png');
    }

    /**
     * 用来调试
     */
    public function echo()
    {
        header('Content-Type: image/png');
        imagepng($this->getBackgroundVo()->image);
        die;
    }

}
