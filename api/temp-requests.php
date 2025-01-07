<?php
require_once 'config.php';

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    response(false, '无效的请求方法');
    exit;
}

// 验证管理员token
$headers = getallheaders();
if (!isset($headers['Authorization']) || !validateAdmin($headers['Authorization'])) {
    response(false, '未授权的访问');
    exit;
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
        exit;
    }

    // 清理过期的请求（90秒以上的记录）
    $pdo->exec("DELETE FROM temporarylogin WHERE TIMESTAMPDIFF(SECOND, request_time, CURRENT_TIMESTAMP) > 90");

    // 获取所有临时登录请求
    $stmt = $pdo->query("SELECT * FROM temporarylogin WHERE status = 'pending' ORDER BY request_time DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    response(true, '获取成功', $requests);
} catch (PDOException $e) {
    response(false, '数据库错误：' . $e->getMessage());
}
