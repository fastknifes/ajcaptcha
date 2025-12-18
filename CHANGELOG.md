# aj-captcha迭代文档

## v2.0.0 2025-12-18
### 新增特性
* **原生绘图模式 (Drawing Mode)**: 新增 `DrawingTemplateProvider`，支持实时生成滑块，彻底解决边缘锯齿问题。
* **多形状支持**: 滑块形状现在支持 `jigsaw` (拼图), `red_heart` (红桃), `spade` (黑桃), `diamond` (方片), `club` (草花) 等多种样式。
* **点击验证码增强**: 新增 `distract_num` 配置，支持生成干扰字；新增智能碰撞检测算法，防止文字重叠。
* **策略模式架构**: 引入 `TemplateProviderInterface` 和 `ShapeDrawerInterface`，解耦模板生成逻辑，便于扩展。
* **兼容性**: 完美兼容 PHP 8.0+ 及即将到来的 PHP 8.5 (废弃 `imagedestroy` 处理)。

### 变更优化
* **依赖移除**: 彻底移除 `intervention/image` 依赖，完全基于原生 GD 库实现，体积更小，性能更高。
* **配置结构**: `config.php` 新增 `block_puzzle.mode` 和 `block_puzzle.shape_type` 配置项。
* **底层优化**: 重构 `ImageUtils`，统一封装 GD 操作，屏蔽 PHP 版本差异。
* **视觉优化**: 滑块自带内阴影和半透明质感，背景凹槽采用动态变暗逻辑，视觉效果更自然。

### 移除
* 移除了对旧版像素级缓存 (`is_cache_pixel`) 的强依赖（Drawing 模式下不再需要）。

## v1.1.2 2021-12-15
* server 增加`verificationByEncryptCode` 方法，兼容前端二次验证`captchaVerification`值

## v1.1.1 2021-12-13
* 去除多余代码、注释

## v1.1.0 2021-12-9
* 重构工厂类与领域层
* 永久缓存图片像素值，增加响应性能

## v1.0.7 2021-11-24
* 增强缓存冗错
* 自定义缓存配置

## v1.0.3 2021-10-11
* 新增二次验证


## v1.0.0  2021-7-16
* 初步实现滑动验证码与文字点选验证码





