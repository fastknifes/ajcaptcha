<?php
// slider_smooth.php
// 超采样抗锯齿优化版：边缘极其平滑，接近 JS Canvas 效果

header('Content-Type: image/png');
header('Cache-Control: no-cache');

// 原尺寸参数
$sliderL = 42;
$sliderR = 9;
$L = $sliderL + $sliderR * 2 + 3;  // ≈63px

// 超采样倍数（4~6 推荐，越大越平滑，但耗内存/CPU稍多）
$scale = 6;

// 放大后画布尺寸
$bigL = $L * $scale;

// 创建放大画布（透明）
$big = imagecreatetruecolor($bigL, $bigL);
imagesavealpha($big, true);
imageantialias($big, true);
$trans = imagecolorallocatealpha($big, 0, 0, 0, 127);
imagefill($big, 0, 0, $trans);

// 浅灰半透明内容占位（可注释掉改为纯透明）
$contentColor = imagecolorallocatealpha($big, 220, 220, 220, 40);
imagefilledrectangle($big, 0, 0, $bigL-1, $bigL-1, $contentColor);

// 白色半透明阴影（与原 JS 一致）
$shadowColor = imagecolorallocatealpha($big, 255, 255, 255, 50);

// 放大后的偏移坐标
$px = 3 * $scale;
$py = ($sliderR * 2 + 1) * $scale;

$PI = M_PI;
$points = [];

// 起点
$points[] = $px; $points[] = $py;

// 上方凸弧（更细密采样，步长缩小以匹配放大）
$cx = $px + ($sliderL / 2) * $scale;
$cy = $py - $sliderR * $scale + 2 * $scale;
for ($a = 0.72*$PI; $a <= 2.26*$PI; $a += 0.03 / $scale) {
    $points[] = $cx + $sliderR * $scale * cos($a);
    $points[] = $cy + $sliderR * $scale * sin($a);
}

// 右上角
$points[] = $px + $sliderL * $scale;
$points[] = $py;

// 右侧凸弧
$cx = $px + $sliderL * $scale + $sliderR * $scale - 2 * $scale;
$cy = $py + ($sliderL / 2) * $scale;
for ($a = 1.21*$PI; $a <= 2.78*$PI; $a += 0.03 / $scale) {
    $points[] = $cx + $sliderR * $scale * cos($a);
    $points[] = $cy + $sliderR * $scale * sin($a);
}

// 右下 → 左下
$points[] = $px + $sliderL * $scale; $points[] = $py + $sliderL * $scale;
$points[] = $px;                    $points[] = $py + $sliderL * $scale;

// 下方凹弧（逆向，半径 +0.4）
$cx = $px + $sliderR * $scale - 2 * $scale;
$cy = $py + ($sliderL / 2) * $scale;
for ($a = 2.76*$PI; $a >= 1.24*$PI; $a -= 0.03 / $scale) {
    $points[] = $cx + ($sliderR + 0.4) * $scale * cos($a);
    $points[] = $cy + ($sliderR + 0.4) * $scale * sin($a);
}

// 闭合
$points[] = $px; $points[] = $py;

// 绘制阴影（填充 + 描边模拟 stroke）
imagefilledpolygon($big, $points, count($points)/2, $shadowColor);
imagepolygon($big, $points, count($points)/2, $shadowColor);

// 创建最终尺寸画布并缩放（关键：高质量重采样产生优秀抗锯齿）
$block = imagecreatetruecolor($L, $L);
imagesavealpha($block, true);
$transFinal = imagecolorallocatealpha($block, 0, 0, 0, 127);
imagefill($block, 0, 0, $transFinal);

imagecopyresampled($block, $big, 0, 0, 0, 0, $L, $L, $bigL, $bigL);

// 输出
imagepng($block, null, 9);

imagedestroy($big);
imagedestroy($block);