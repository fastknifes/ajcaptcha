# AJ-Captcha for PHP

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/php-%3E%3D7.1-green.svg)
![License](https://img.shields.io/badge/license-MIT-orange.svg)

这个类库使用 PHP 实现了行为验证码（滑动拼图、点选文字）。
v2.0 版本基于 **Strategy Pattern（策略模式）** 彻底重构了底层架构，移除了第三方图像库依赖，引入了原生 GD 绘图与抗锯齿技术，带来了更极致的体验与更高的性能。

## ✨ 核心特性 (v2.0)

*   **轻量零依赖**：彻底移除 `intervention/image`，完全基于 PHP 原生 GD 库，体积更小，兼容性更强（PHP 7.1 ~ 8.5+）。
*   **极致抗锯齿 (Anti-Aliasing)**：
    *   全新的 `Drawing` 模式，抛弃了传统的图片模板抠图方案。
    *   采用 **6 倍超采样 (Super Sampling) + 高斯模糊 (Gaussian Blur)** 技术实时绘制滑块。
    *   生成的滑块边缘平滑细腻，自带内阴影与半透明质感，完美融合背景。
*   **多形状支持**：内置多种滑块形状，支持一键切换：
    *   🧩 拼图 (`jigsaw`)
    *   ❤️ 红桃 (`red_heart`)
    *   ♠️ 黑桃 (`spade`)
    *   ♦️ 方片 (`diamond`)
    *   ♣️ 草花 (`club`)
*   **安全增强**：
    *   **干扰图**：滑动验证码支持随机生成干扰滑块（位置、形状随机），增加机器识别难度。
    *   **干扰字**：点击验证码支持生成随机干扰文字。
    *   **智能布局**：采用 **随机坐标 + 碰撞检测算法**，确保文字不重叠、不越界。
*   **双模式兼容**：保留了旧版“图片模板”模式 (`resource`)，老用户可无缝切换。

## 📸 效果预览

### 滑动验证码 (Drawing 模式)
*(无需任何图片素材，纯代码实时绘制)*

> 效果图占位：建议运行 testImage.php 查看实际效果

### 点击验证码
支持文字点选，自带干扰文字与随机布局。

## 🛠 安装

### 要求
*   PHP >= 7.1
*   ext-gd
*   ext-openssl

### Composer 安装
```bash
composer require fastknife/ajcaptcha
```

## 🚀 快速开始

### 1. 原生 PHP 使用
```php
<?php
require 'vendor/autoload.php';

use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;

// 加载配置
$config = require 'src/config.php';

// --- 获取验证码 ---
$service = new BlockPuzzleCaptchaService($config);
$data = $service->get(); 

// --- 一次验证 (前端滑动/点选后调用) ---
// $token = $_REQUEST['token'];
// $pointJson = $_REQUEST['pointJson'];
// try {
//     // 验证成功会返回加密的 captchaVerification
//     $captchaVerification = $service->check($token, $pointJson);
//     
//     // 将 captchaVerification 返回给前端
//     // echo json_encode(['success' => true, 'repData' => ['captchaVerification' => $captchaVerification]]);
// } catch (\Exception $e) {
//     // 验证失败
// }

// --- 二次验证 (业务接口登录/注册时调用) ---
// 前端将上一步获取的 captchaVerification 传给业务接口
// $captchaVerification = $_REQUEST['captchaVerification'];
// try {
//     $service->verificationByEncryptCode($captchaVerification);
//     // 验证通过，执行业务逻辑 (登录/注册...)
// } catch (\Exception $e) {
//     // 二次验证失败，拦截业务请求
// }
```

### 2. 框架集成
本库无任何全局变量与单例依赖，完美支持 ThinkPHP, Laravel, Hyperf, Swoole 等现代框架。只需在控制器中实例化 Service 即可。

## ⚙️ 详细配置说明

在 `src/config.php` 中进行配置：

```php
return [
    'font_file' => '', //自定义字体包路径， 不填使用默认值
    
    // 滑动验证码配置
    'block_puzzle' => [
        // 模式: 'drawing' (推荐, 原生绘图), 'resource' (旧版图片模板)
        'mode' => 'drawing',

        // 形状类型 (仅在 drawing 模式下生效)
        // 可选: 'jigsaw', 'red_heart', 'spade', 'diamond', 'club'
        'shape_type' => 'red_heart',

        // 开启干扰图
        'is_interfere' => true, 
        
        // 容错偏移量 (px)
        'offset' => 10,

        // 背景图路径 (支持 string 目录或 array 文件列表)
        // 'backgrounds' => '/path/to/images/',
    ],
    
    // 点击验证码配置
    'click_word' => [
        // 干扰字数量
        'distract_num' => 2, 
        // 目标字数量
        'word_num' => 4,
        // 背景图
        'backgrounds' => [], 
    ],
    
    // 水印配置
    'watermark' => [
        'fontsize' => 12,
        'color' => '#ffffff',
        'text' => '我的水印'
    ],
    
    // 缓存配置 (默认使用内置文件缓存)
    'cache' => [
        'constructor' => \Fastknife\Utils\CacheUtils::class,
        'method' => [
            'get' => 'get',
            'set' => 'set',
            'delete' => 'delete',
            'has' => 'has'
        ],
        'options' => [
            'expire' => 300, // 300秒有效期
            'prefix' => '',
            'path' => '', // 缓存目录
        ]
    ]
];
```

### 框架缓存集成

如果您使用 Laravel, ThinkPHP 或 Hyperf 等框架，建议替换默认缓存驱动以获得更好性能。

**Laravel 配置示例**:
```php
'cache' => [
    'constructor' => [Illuminate\Support\Facades\Cache::class, 'store'],
    // ...
]
```

**ThinkPHP 6 配置示例**:
```php
'cache' => [
    'constructor' => [think\Facade\Cache::class, 'instance'],
    // ...
]
```

## 💻 前端集成注意事项

前端请求时，请确保 `Content-Type` 设置为 `application/x-www-form-urlencoded`。

**Axios 示例**:
```javascript
import axios from 'axios';
import qs from 'qs';

axios.defaults.baseURL = 'https://your-api.com/captcha-api';

const service = axios.create({
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    },
})

service.interceptors.request.use(config => {
    if (config.data) {
        config.data = qs.stringify(config.data)
    }
    return config
})
```

## 🏗 架构设计

v2.0 引入了**策略模式 (Strategy Pattern)**，将滑块的生成逻辑解耦。

### 核心目录结构

```
src/
├── Domain/
│   ├── Template/                   # 策略层
│   │   ├── TemplateProviderInterface.php  # 策略接口
│   │   ├── DrawingTemplateProvider.php    # 策略A: 原生绘图 (抗锯齿核心)
│   │   ├── ResourceTemplateProvider.php   # 策略B: 图片资源 (兼容旧版)
│   │   │
│   │   └── Shape/                  # 形状绘制子策略
│   │       ├── ShapeDrawerInterface.php
│   │       ├── ShapeFactory.php           # 工厂模式
│   │       ├── JigsawShapeDrawer.php      # 拼图形状
│   │       ├── RedHeartShapeDrawer.php    # 红桃形状
│   │       ├── SpadeShapeDrawer.php       # 黑桃形状
│   │       ├── DiamondShapeDrawer.php     # 方片形状
│   │       └── ClubShapeDrawer.php        # 草花形状
│   │
│   └── Logic/                      # 业务逻辑层
│       ├── BlockImage.php          # 滑动验证码合成逻辑 (Alpha混合, 挖槽)
│       └── ...
│
├── Utils/
│   └── ImageUtils.php              # 基础设施层: GD 库底层封装 (屏蔽 PHP 版本差异)
└── ...
```

### 绘图原理 (Drawing Mode)

1.  **大画布绘制**：系统首先创建一个 6 倍于目标尺寸的透明画布。
2.  **矢量路径**：使用 `imagefilledpolygon` 等函数在画布上绘制高精度的矢量形状（拼图、心形等）。
3.  **光影渲染**：在形状内部绘制半透明的内阴影、外发光，模拟立体感。
4.  **高斯模糊**：应用轻微的高斯模糊 (`IMG_FILTER_GAUSSIAN_BLUR`) 柔化边缘。
5.  **下采样**：使用 `imagecopyresampled` 将大图缩放回原尺寸，生成高质量的 Alpha Mask。
6.  **混合渲染**：利用 Alpha Blending 将 Mask 与背景图混合，实现完美的抠图效果。

## 📝 变更日志

查看 [CHANGELOG.md](./CHANGELOG.md)

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！如果你想添加新的滑块形状，只需实现 `ShapeDrawerInterface` 并在 `ShapeFactory` 中注册即可。

## 📄 License

MIT
