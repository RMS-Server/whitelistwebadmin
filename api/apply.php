<?php
require_once 'config.php';

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');

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
    $uuid = null;
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    // 检查用户名是否已存在于白名单中
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM whitelist WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        response(false, '该玩家已在白名单中');
    }

    // 检查用户名是否已有待处理的申请
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM whitelist_applications WHERE username = ? AND status = "pending"');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        response(false, '该玩家已有待处理的申请');
    }

    // 检查是否有之前的申请记录
    $stmt = $pdo->prepare('SELECT id, status, created_at FROM whitelist_applications WHERE username = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$username]);
    $lastApplication = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastApplication) {
        // 如果最后一次申请被拒绝，检查是否在24小时冷却期内
        if ($lastApplication['status'] === 'rejected') {
            $lastRejectedTime = strtotime($lastApplication['created_at']);
            $hoursSinceRejection = (time() - $lastRejectedTime) / 3600;
            
            if ($hoursSinceRejection < 24) {
                $hoursRemaining = ceil(24 - $hoursSinceRejection);
                response(false, "申请已被拒绝，请在 {$hoursRemaining} 小时后重试");
            }

            // 更新现有申请记录
            $stmt = $pdo->prepare('UPDATE whitelist_applications SET status = "pending", uuid = ?, created_at = NOW() WHERE id = ?');
            $stmt->execute([$uuid, $lastApplication['id']]);
        }
    } else {
        // 如果没有申请记录，创建新的
        $stmt = $pdo->prepare('INSERT INTO whitelist_applications (username, uuid, status, created_at) VALUES (?, ?, "pending", NOW())');
        $stmt->execute([$username, $uuid]);
    }
    
    response(true, '申请已提交，请等待管理员审核');
} catch (PDOException $e) {
    response(false, '申请失败: ' . $e->getMessage());
}
