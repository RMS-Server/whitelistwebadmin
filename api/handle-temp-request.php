<?php
require_once 'config.php';

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, '无效的请求方法');
    exit;
}

// 验证管理员token
$headers = getallheaders();
if (!isset($headers['Authorization']) || !validateAdmin($headers['Authorization'])) {
    response(false, '未授权的访问');
    exit;
}

// 获取请求数据
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !isset($data['status']) || 
    !in_array($data['status'], ['approved', 'rejected'])) {
    response(false, '无效的请求参数');
    exit;
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
        exit;
    }

    // 开始事务
    $pdo->beginTransaction();

    // 清理过期的请求（90秒以上的记录）
    $pdo->exec("DELETE FROM temporarylogin WHERE TIMESTAMPDIFF(SECOND, request_time, CURRENT_TIMESTAMP) > 90");

    // 更新临时登录请求状态
    $stmt = $pdo->prepare("UPDATE temporarylogin SET status = ?, update_time = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'");
    $result = $stmt->execute([$data['status'], $data['id']]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        response(false, '请求不存在或已被处理');
        exit;
    }

    // 如果批准了请求，获取玩家名称以供显示
    $stmt = $pdo->prepare("SELECT username FROM temporarylogin WHERE id = ?");
    $stmt->execute([$data['id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $request['username'];

    $pdo->commit();

    $message = $data['status'] === 'approved' ? 
        "已允许玩家 {$username} 的临时登录请求" : 
        "已拒绝玩家 {$username} 的临时登录请求";
    
    response(true, $message);
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    response(false, '数据库错误：' . $e->getMessage());
}
