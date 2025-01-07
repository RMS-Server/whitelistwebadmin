<?php
require_once 'config.php';

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';

if (empty($username)) {
    response(false, '玩家名称不能为空');
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    // 检查是否已存在
    $stmt = $pdo->prepare('SELECT id FROM whitelist WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        response(false, '该玩家已在白名单中');
    }

    // 添加白名单
    $stmt = $pdo->prepare('INSERT INTO whitelist (username) VALUES (?)');
    $stmt->execute([$username]);
    
    response(true, '添加成功');
} catch (PDOException $e) {
    response(false, '添加失败: ' . $e->getMessage());
}
