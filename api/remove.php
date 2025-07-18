<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// 验证管理员权限
$headers = getallheaders();
error_log('Headers: ' . print_r($headers, true));  // 记录所有头信息
$token = isset($headers['Authorization']) ? $headers['Authorization'] : 
        (isset($headers['authorization']) ? $headers['authorization'] : '');
error_log('Token: ' . $token);  // 记录token值

if (empty($token)) {
    response(false, '未提供授权token');
}

if (!validateAdmin($token)) {
    response(false, '无权限执行此操作 (token: ' . substr($token, 0, 10) . '...)');
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    response(false, '无效的ID');
}

try {
    $pdo = getConnection();
    if (!$pdo) {
        response(false, '数据库连接失败');
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        // 获取玩家名称
        $stmt = $pdo->prepare('SELECT username FROM whitelist WHERE id = ?');
        $stmt->execute([$id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$player) {
            throw new Exception('找不到该玩家');
        }

        // 删除白名单记录
        $stmt = $pdo->prepare('DELETE FROM whitelist WHERE id = ?');
        $stmt->execute([$id]);

        // 删除该玩家的所有申请记录
        $stmt = $pdo->prepare('DELETE FROM whitelist_applications WHERE username = ?');
        $stmt->execute([$player['username']]);

        // 提交事务
        $pdo->commit();
        
        response(true, '已移除玩家 ' . $player['username'] . ' 的白名单和所有申请记录');
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    response(false, '移除失败: ' . $e->getMessage());
}
