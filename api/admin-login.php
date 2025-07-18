<?php
require_once 'config.php';

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (empty($password)) {
    response(false, '请输入管理员口令');
}

// 检查是否被禁用
if (isAdminLoginDisabled()) {
    response(false, '管理员登录已被禁用，请联系系统管理员重置');
}

if (!validateAdmin($password)) {
    $config = getAdminConfig();
    $remainingAttempts = 5 - $config['failed_attempts'];
    
    if ($remainingAttempts > 0) {
        response(false, "管理员口令错误，还剩 {$remainingAttempts} 次尝试机会");
    } else {
        response(false, '管理员登录已被禁用，请联系系统管理员重置');
    }
}

// 登录成功，返回密码作为token
response(true, '登录成功', ['token' => $password]);
