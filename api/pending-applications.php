<?php
require_once 'config.php';

// 验证管理员token
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    response(false, '未授权的访问');
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    $stmt = $pdo->query("SELECT id, username, created_at FROM whitelist_applications WHERE status = 'pending' ORDER BY created_at ASC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 添加序号
    $index = 1;
    foreach ($applications as &$app) {
        $app['index'] = $index++;
    }
    
    response(true, '获取成功', $applications);
} catch (PDOException $e) {
    response(false, '获取待处理申请失败: ' . $e->getMessage());
}
