<?php
// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'whitelist');
define('DB_USER', 'teswhitelist');
define('DB_PASS', 'password');

// 管理员配置
define('ADMIN_PASSWORD', 'YOUR_ADMIN_PASSWORD');
define('ADMIN_CONFIG_FILE', __DIR__ . '/admin_config.json');

// 初始化管理员配置文件
if (!file_exists(ADMIN_CONFIG_FILE)) {
    $initial_config = [
        'login_enabled' => true,
        'failed_attempts' => 0,
        'last_attempt_time' => 0
    ];
    file_put_contents(ADMIN_CONFIG_FILE, json_encode($initial_config, JSON_PRETTY_PRINT));
}

// 获取管理员配置
function getAdminConfig() {
    $config = json_decode(file_get_contents(ADMIN_CONFIG_FILE), true);
    return $config;
}

// 保存管理员配置
function saveAdminConfig($config) {
    file_put_contents(ADMIN_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

// 检查管理员登录是否被禁用
function isAdminLoginDisabled() {
    $config = getAdminConfig();
    return !$config['login_enabled'];
}

// 记录失败的登录尝试
function recordFailedLogin() {
    $config = getAdminConfig();
    $config['failed_attempts']++;
    $config['last_attempt_time'] = time();
    
    // 如果失败次数超过5次，禁用登录1小时
    if ($config['failed_attempts'] >= 5) {
        $config['login_enabled'] = false;
    }
    
    saveAdminConfig($config);
    return $config['failed_attempts'];
}

// 重置失败登录计数
function resetFailedLogins() {
    $config = getAdminConfig();
    $config['failed_attempts'] = 0;
    $config['last_attempt_time'] = 0;
    saveAdminConfig($config);
}

// 重置管理员状态
function resetAdminStatus() {
    $config = getAdminConfig();
    $config['failed_attempts'] = 0;
    $config['last_attempt_time'] = 0;
    $config['login_enabled'] = true;
    saveAdminConfig($config);
}

// 验证管理员token
function validateAdmin($token) {
    return $token === ADMIN_PASSWORD;
}

// 创建数据库连接
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建申请表（如果不存在）
        $pdo->exec("CREATE TABLE IF NOT EXISTS whitelist_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(36) NOT NULL UNIQUE,
            uuid VARCHAR(36) NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // 创建白名单表（如果不存在）
        $pdo->exec("CREATE TABLE IF NOT EXISTS whitelist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(36) NOT NULL UNIQUE,
            uuid VARCHAR(36) NULL
        )");
        
        // 创建临时登录表（如果不存在）
        $pdo->exec("CREATE TABLE IF NOT EXISTS temporarylogin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(36) NOT NULL UNIQUE,
            request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'pending',
            update_time TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // 为现有表添加UUID列（如果不存在）
        $pdo->exec("ALTER TABLE whitelist ADD COLUMN IF NOT EXISTS uuid VARCHAR(36) NULL");
        $pdo->exec("ALTER TABLE whitelist_applications ADD COLUMN IF NOT EXISTS uuid VARCHAR(36) NULL");
        
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// 通过Mojang API获取UUID
function getUUIDFromMojang($username) {
    $url = "https://api.mojang.com/users/profiles/minecraft/" . urlencode($username);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WhitelistRMS/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['id'])) {
            // 格式化UUID (添加连字符)
            $uuid = $data['id'];
            return substr($uuid, 0, 8) . '-' . 
                   substr($uuid, 8, 4) . '-' . 
                   substr($uuid, 12, 4) . '-' . 
                   substr($uuid, 16, 4) . '-' . 
                   substr($uuid, 20, 12);
        }
    }
    
    return null;
}

// 响应函数
function response($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
