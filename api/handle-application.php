<?php
require_once 'config.php';

// 验证管理员token
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    response(false, '未授权的访问');
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$approve = $data['approve'] ?? false;

if (!$id) {
    response(false, '无效的申请ID');
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    // 开始事务
    $pdo->beginTransaction();

    // 获取申请信息
    $stmt = $pdo->prepare('SELECT username FROM whitelist_applications WHERE id = ? AND status = ?');
    $stmt->execute([$id, 'pending']);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $pdo->rollBack();
        response(false, '找不到该申请或已被处理');
    }

    if ($approve) {
        // 添加到白名单
        $stmt = $pdo->prepare('INSERT INTO whitelist (username) VALUES (?)');
        $stmt->execute([$application['username']]);
        
        // 更新申请状态
        $stmt = $pdo->prepare('UPDATE whitelist_applications SET status = ? WHERE id = ?');
        $stmt->execute(['approved', $id]);
        
        $pdo->commit();
        response(true, '已同意申请并添加到白名单');
    } else {
        // 更新申请状态为拒绝
        $stmt = $pdo->prepare('UPDATE whitelist_applications SET status = ? WHERE id = ?');
        $stmt->execute(['rejected', $id]);
        
        $pdo->commit();
        response(true, '已拒绝申请');
    }
} catch (PDOException $e) {
    if ($pdo) {
        $pdo->rollBack();
    }
    response(false, '处理申请失败: ' . $e->getMessage());
}
