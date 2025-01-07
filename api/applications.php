<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    $stmt = $pdo->query("
        SELECT id, username, status, created_at 
        FROM whitelist_applications 
        WHERE status IN ('pending', 'rejected')
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 添加序号
    $index = 1;
    foreach ($applications as &$app) {
        $app['index'] = $index++;
    }
    
    response(true, '获取成功', $applications);
} catch (PDOException $e) {
    response(false, '获取申请列表失败: ' . $e->getMessage());
}
