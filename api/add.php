<?php
require_once 'config.php';

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';

if (empty($username)) {
    response(false, '玩家名称不能为空');
}

// 验证用户名格式（Minecraft用户名规则）
if (!preg_match('/^[a-zA-Z0-9_]{1,16}$/', $username)) {
    response(false, '玩家名称格式无效');
}

// 通过Mojang API获取UUID
$uuid = getUUIDFromMojang($username);
if (!$uuid) {
    response(false, '无法找到该玩家，请检查玩家名称是否正确');
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
    $stmt = $pdo->prepare('INSERT INTO whitelist (username, uuid) VALUES (?, ?)');
    $stmt->execute([$username, $uuid]);
    
    response(true, '添加成功');
} catch (PDOException $e) {
    response(false, '添加失败: ' . $e->getMessage());
}
