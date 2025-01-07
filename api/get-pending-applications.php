<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    // 查询待处理的申请
    $stmt = $pdo->prepare('SELECT id, username, created_at FROM whitelist_applications WHERE status = ? ORDER BY created_at ASC');
    $stmt->execute(['pending']);
    $pendingApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pendingApplications)) {
        response(true, '无待处理的申请');
    }

    // 添加序号
    $index = 1;
    foreach ($pendingApplications as &$application) {
        $application['index'] = $index++;
    }

    response(true, '获取成功', $pendingApplications);
} catch (PDOException $e) {
    response(false, '获取待处理申请失败: ' . $e->getMessage());
}
