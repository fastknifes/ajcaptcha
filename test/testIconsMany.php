<?php
declare(strict_types=1);

/**
 * 大量测试图标生成功能
 */

require_once __DIR__ . '/autoload.php';

use Fastknife\Service\ClickWordCaptchaService;

echo "=== 大量图标生成测试 ===\n\n";

// 加载配置
$config = require __DIR__ . '/../src/config.php';

echo "配置信息:\n";
echo "  图标数量: " . count($config['click_word']['icons'] ?? []) . "\n";
echo "  最大图标数: " . ($config['click_word']['max_icons'] ?? 0) . "\n\n";

$service = new ClickWordCaptchaService($config);

// 统计信息
$totalTests = 50;
$iconCount = 0;
$iconExamples = [];

echo "生成 {$totalTests} 次验证码...\n\n";

for ($i = 1; $i <= $totalTests; $i++) {
    try {
        $data = $service->get();
        $wordList = $data['wordList'] ?? [];
        
        // 检查是否包含图标
        foreach ($wordList as $word) {
            if (preg_match('/^<(.+)>$/', $word, $matches)) {
                $iconCount++;
                $iconName = $matches[1];
                if (!isset($iconExamples[$iconName])) {
                    $iconExamples[$iconName] = 0;
                }
                $iconExamples[$iconName]++;
            }
        }
        
        if ($i % 10 == 0) {
            echo "  已测试 {$i} 次...\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ 测试 #{$i} 失败: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 测试结果 ===\n";
echo "总测试次数: {$totalTests}\n";
echo "生成图标次数: {$iconCount}\n";
echo "图标出现率: " . round($iconCount / $totalTests * 100, 2) . "%\n";

if (!empty($iconExamples)) {
    echo "\n出现的图标统计:\n";
    arsort($iconExamples);
    foreach ($iconExamples as $iconName => $count) {
        echo "  <{$iconName}>: {$count} 次\n";
    }
} else {
    echo "\n未检测到任何图标生成\n";
}
