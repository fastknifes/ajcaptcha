<?php
declare(strict_types=1);

namespace Fastknife\Domain\Template;

use Fastknife\Domain\Template\Shape\ShapeFactory;
use Fastknife\Domain\Vo\OffsetVo;
use Fastknife\Domain\Vo\TemplateVo;
use Fastknife\Utils\ImageUtils;
use Fastknife\Utils\RandomUtils;

class DrawingTemplateProvider implements TemplateProviderInterface
{
    // 原尺寸参数 (与前端一致或自定)
    private $scale = 6; // 超采样倍数
    
    // 形状类型
    private $shapeType = 'jigsaw';

    public function __construct(array $config)
    {
        $this->shapeType = $config['block_puzzle']['shape_type'] ?? 'jigsaw';
    }

    public function getTemplateVo(int $bgWidth, int $bgHeight): TemplateVo
    {
        // 1. 获取或生成高质量 Mask
        $maskImage = $this->getMaskImage();

        // 2. 封装为 TemplateVo
        $templateVo = new TemplateVo(null); // src 为 null
        $templateVo->image = $maskImage;    // 直接注入资源

        // 3. 计算偏移量
        $bgHalfWidth = intval($bgWidth / 2);
        $tmplWidth = imagesx($maskImage);

        $maxOffsetX = $bgHalfWidth - $tmplWidth - 1;
        $maxOffsetX = max(0, $maxOffsetX);

        $offset = RandomUtils::getRandomInt(0, $maxOffsetX);
        
        $templateVo->setOffset(new OffsetVo($offset + $bgHalfWidth, 0));

        return $templateVo;
    }
    
    public function getInterfereVo(int $bgWidth, int $bgHeight, TemplateVo $targetVo): TemplateVo
    {
        // 1. 复用 Mask
        $maskImage = $this->getMaskImage();
        
        $interfereVo = new TemplateVo(null);
        $interfereVo->image = $maskImage;
        
        // 2. 随机位置 (避开目标)
        $bgHalfWidth = intval($bgWidth / 2);
        $tmplWidth = imagesx($maskImage);
        
        $maxOffsetX = $bgHalfWidth - $tmplWidth - 5;
        $maxOffsetX = max(0, $maxOffsetX);
        
        $offset = RandomUtils::getRandomInt(5, $maxOffsetX);
        
        // y 轴随机 (0 ~ bgHeight - tmplHeight)
        $tmplHeight = imagesy($maskImage);
        $maxOffsetY = $bgHeight - $tmplHeight;
        $maxOffsetY = max(0, $maxOffsetY);
        
        // 确保 Y 轴不与目标重叠 (minDistance = tmplHeight)
        $minDistance = $tmplHeight;
        $targetY = $targetVo->offset->y;
        $maxRetries = 20;
        $retry = 0;
        
        do {
            $y = RandomUtils::getRandomInt(0, $maxOffsetY);
            $retry++;
        } while (abs($y - $targetY) < $minDistance && $retry < $maxRetries);
        
        $interfereVo->setOffset(new OffsetVo($offset, $y));
        
        return $interfereVo;
    }


    /**
     * 获取 Mask 图像资源 (单例缓存)
     * @return resource|\GdImage
     */
    private function getMaskImage()
    {
        return $this->drawMask();
    }

    private function drawMask()
    {
        // 使用 ShapeFactory 创建 Drawer
        $drawer = ShapeFactory::create($this->shapeType);
        
        // 获取未缩放的大图
        $big = $drawer->createMask($this->scale);
        
        // 高斯模糊 (进一步平滑边缘，去除锯齿)
        imagefilter($big, IMG_FILTER_GAUSSIAN_BLUR);
        
        // 获取原始尺寸
        $originalSize = $drawer->getOriginalSize();
        $targetW = $originalSize[0];
        $targetH = $originalSize[1];

        // 5. 下采样缩放 (Super Sampling)
        $block = ImageUtils::resize($big, $targetW, $targetH);

        // 6. 清理大图
        ImageUtils::destroy($big);

        return $block;
    }
}
