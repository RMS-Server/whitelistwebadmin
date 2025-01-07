<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    $stmt = $pdo->query('SELECT id, username FROM whitelist ORDER BY id ASC');
    $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 添加序号，保留ID用于删除操作
    $index = 1;
    foreach ($whitelist as &$item) {
        $item['index'] = $index++;
    }
    
    response(true, '获取成功', $whitelist);
} catch (PDOException $e) {
    response(false, '获取白名单列表失败: ' . $e->getMessage());
}
