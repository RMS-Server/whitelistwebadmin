<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    // 检查uuid字段是否存在，确保向下兼容
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM whitelist LIKE "uuid"');
        $uuidExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $uuidExists = false;
    }
    
    // 根据uuid字段是否存在选择不同的查询
    if ($uuidExists) {
        $stmt = $pdo->query('SELECT id, username, uuid FROM whitelist ORDER BY id ASC');
    } else {
        $stmt = $pdo->query('SELECT id, username FROM whitelist ORDER BY id ASC');
    }
    
    $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 添加序号，保留ID用于删除操作
    $index = 1;
    foreach ($whitelist as &$item) {
        $item['index'] = $index++;
        // 如果没有uuid字段，设置为null
        if (!$uuidExists) {
            $item['uuid'] = null;
        }
    }
    
    response(true, '获取成功', $whitelist);
} catch (PDOException $e) {
    response(false, '获取白名单列表失败: ' . $e->getMessage());
}
