<?php
/**
 * 自动更新系统
 * 从GitHub仓库检查并下载最新版本
 */

header('Content-Type: application/json; charset=utf-8');

class AutoUpdater {
    private $githubRepo = 'RMS-Server/whitelistwebadmin';
    private $currentVersionFile = '../version';
    private $backupDir = '../backup';
    private $tempDir = '../temp_update';
    
    // 需要保留的自定义内容
    private $preservePatterns = [
        'index.html' => [
            'title' => '<title>([^<]*白名单管理系统[^<]*)</title>',
            'main_title' => '<h1 class="text-center main-title">([^<]*白名单管理系统[^<]*)</h1>'
        ],
        'admin/index.html' => [
            'title' => '<title>([^<]*白名单管理系统[^<]*)</title>',
            'main_title1' => '<h1 class="text-center main-title">([^<]*白名单管理系统[^<]*)</h1>',
            'main_title2' => '<h1 class="text-center main-title">([^<]*白名单管理系统[^<]*管理员[^<]*)</h1>'
        ],
        'api/config.php' => [
            'DB_HOST' => "define\\('DB_HOST',\\s*'([^']*)'\\);",
            'DB_PORT' => "define\\('DB_PORT',\\s*'([^']*)'\\);", 
            'DB_NAME' => "define\\('DB_NAME',\\s*'([^']*)'\\);",
            'DB_USER' => "define\\('DB_USER',\\s*'([^']*)'\\);",
            'DB_PASS' => "define\\('DB_PASS',\\s*'([^']*)'\\);",
            'ADMIN_PASSWORD' => "define\\('ADMIN_PASSWORD',\\s*'([^']*)'\\);"
        ]
    ];
    
    public function checkForUpdates() {
        try {
            // 获取远程版本
            $remoteVersion = $this->getRemoteVersion();
            if (!$remoteVersion) {
                return $this->response(false, '无法获取远程版本信息，请检查网络连接或稍后重试');
            }
            
            // 获取本地版本
            $localVersion = $this->getLocalVersion();
            
            // 比较版本
            if ($localVersion && $localVersion === $remoteVersion) {
                return $this->response(true, '当前已是最新版本', [
                    'current_version' => $localVersion,
                    'remote_version' => $remoteVersion,
                    'need_update' => false
                ]);
            }
            
            return $this->response(true, '发现新版本', [
                'current_version' => $localVersion ?: '未知',
                'remote_version' => $remoteVersion,
                'need_update' => true
            ]);
            
        } catch (Exception $e) {
            return $this->response(false, '检查更新失败: ' . $e->getMessage());
        }
    }
    
    public function performUpdate() {
        try {
            // 检查是否需要更新
            $checkResult = $this->checkForUpdates();
            $checkData = json_decode($checkResult, true);
            
            if (!$checkData['success']) {
                return $checkResult;
            }
            
            if (!$checkData['data']['need_update']) {
                return $this->response(true, '当前已是最新版本，无需更新');
            }
            
            $remoteVersion = $checkData['data']['remote_version'];
            
            // 创建备份
            $this->createBackup();
            
            // 下载新版本
            $downloadPath = $this->downloadLatestVersion();
            if (!$downloadPath) {
                throw new Exception('下载新版本失败');
            }
            
            // 解压和安装
            $this->extractAndInstall($downloadPath, $remoteVersion);
            
            // 清理临时文件
            $this->cleanup();
            
            return $this->response(true, '更新成功完成', [
                'old_version' => $checkData['data']['current_version'],
                'new_version' => $remoteVersion
            ]);
            
        } catch (Exception $e) {
            $this->cleanup();
            return $this->response(false, '更新失败: ' . $e->getMessage());
        }
    }
    
    private function getRemoteVersion() {
        // 尝试使用GitHub API
        $apiUrl = "https://api.github.com/repos/{$this->githubRepo}/contents/version";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: RMS-AutoUpdater/1.0',
                    'Accept: application/vnd.github.v3.raw'
                ],
                'timeout' => 30
            ]
        ]);
        
        $content = @file_get_contents($apiUrl, false, $context);
        if ($content !== false) {
            return trim($content);
        }
        
        // 如果API失败，尝试直接访问raw文件
        $rawUrl = "https://raw.githubusercontent.com/{$this->githubRepo}/main/version";
        $rawContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: RMS-AutoUpdater/1.0',
                'timeout' => 30
            ]
        ]);
        
        $content = @file_get_contents($rawUrl, false, $rawContext);
        if ($content !== false) {
            return trim($content);
        }
        
        // 最后尝试通过curl（如果可用）
        if (function_exists('curl_init')) {
            return $this->getRemoteVersionWithCurl();
        }
        
        return false;
    }
    
    private function getRemoteVersionWithCurl() {
        $urls = [
            "https://api.github.com/repos/{$this->githubRepo}/contents/version",
            "https://raw.githubusercontent.com/{$this->githubRepo}/main/version"
        ];
        
        foreach ($urls as $index => $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'RMS-AutoUpdater/1.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            if ($index === 0) {
                // 对于API，使用raw accept header
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.v3.raw']);
            }
            
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($content !== false && $httpCode === 200) {
                return trim($content);
            }
        }
        
        return false;
    }
    
    private function getLocalVersion() {
        if (!file_exists($this->currentVersionFile)) {
            return null;
        }
        
        $content = file_get_contents($this->currentVersionFile);
        return trim($content);
    }
    
    private function createBackup() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        $backupName = 'backup_' . date('Y-m-d_H-i-s');
        $backupPath = $this->backupDir . '/' . $backupName;
        
        // 创建备份目录
        mkdir($backupPath, 0755, true);
        
        // 复制当前文件
        $this->copyDirectory('..', $backupPath, ['backup', 'temp_update', '.git']);
    }
    
    private function downloadLatestVersion() {
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        $zipPath = $this->tempDir . '/latest.zip';
        
        // 国内镜像列表，按优先级排序
        $mirrorUrls = [
            "https://mirror.ghproxy.com/https://github.com/{$this->githubRepo}/archive/main.zip",
            "https://ghproxy.net/https://github.com/{$this->githubRepo}/archive/main.zip", 
            "https://gh-proxy.com/https://github.com/{$this->githubRepo}/archive/main.zip",
            "https://gitclone.com/github.com/{$this->githubRepo}/archive/main.zip",
            "https://github.com/{$this->githubRepo}/archive/main.zip" // 原始地址作为最后备用
        ];
        
        foreach ($mirrorUrls as $index => $zipUrl) {
            try {
                $mirrorName = $this->getMirrorName($zipUrl);
                
                // 优先使用curl下载
                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $zipUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 减少超时时间
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'RMS-AutoUpdater/1.0');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                    
                    $zipContent = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);
                    
                    if ($zipContent !== false && $httpCode === 200 && strlen($zipContent) > 1000) {
                        // 验证是否为有效的ZIP文件
                        if ($this->isValidZip($zipContent)) {
                            if (file_put_contents($zipPath, $zipContent) !== false) {
                                return $zipPath;
                            }
                        } else {
                            error_log("镜像 {$mirrorName} 返回的不是有效ZIP文件");
                        }
                    }
                    
                    if ($error) {
                        error_log("镜像 {$mirrorName} curl错误: {$error}");
                    } else if ($httpCode !== 200) {
                        error_log("镜像 {$mirrorName} HTTP错误: {$httpCode}");
                    }
                }
                
                // 备用方案：使用file_get_contents
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'User-Agent: RMS-AutoUpdater/1.0',
                        'timeout' => 120,
                        'follow_location' => true
                    ]
                ]);
                
                $zipContent = @file_get_contents($zipUrl, false, $context);
                if ($zipContent !== false && strlen($zipContent) > 1000) {
                    // 验证是否为有效的ZIP文件
                    if ($this->isValidZip($zipContent)) {
                        if (file_put_contents($zipPath, $zipContent) !== false) {
                            return $zipPath;
                        }
                    } else {
                        error_log("镜像 {$mirrorName} 通过file_get_contents返回的不是有效ZIP文件");
                    }
                }
                
            } catch (Exception $e) {
                error_log("镜像 {$mirrorName} 下载失败: " . $e->getMessage());
                continue;
            }
        }
        
        throw new Exception('所有镜像下载都失败，请稍后重试或检查网络连接');
    }
    
    private function getMirrorName($url) {
        if (strpos($url, 'ghproxy.com') !== false) return 'ghproxy.com';
        if (strpos($url, 'ghproxy.net') !== false) return 'ghproxy.net';
        if (strpos($url, 'gh-proxy.com') !== false) return 'gh-proxy.com';
        if (strpos($url, 'gitclone.com') !== false) return 'gitclone.com';
        return 'GitHub官方';
    }
    
    private function isValidZip($content) {
        // 检查ZIP文件头部签名
        if (strlen($content) < 4) {
            return false;
        }
        
        // ZIP文件的魔数：50 4B 03 04 (PK..)
        $header = substr($content, 0, 4);
        return $header === "\x50\x4B\x03\x04" || $header === "\x50\x4B\x05\x06" || $header === "\x50\x4B\x07\x08";
    }
    
    private function extractAndInstall($zipPath, $newVersion) {
        // 首先验证ZIP文件是否存在和有效
        if (!file_exists($zipPath)) {
            throw new Exception('下载的压缩包文件不存在');
        }
        
        $fileSize = filesize($zipPath);
        if ($fileSize < 1000) {
            throw new Exception('下载的压缩包文件太小，可能不完整');
        }
        
        // 验证ZIP文件内容
        $zipContent = file_get_contents($zipPath);
        if (!$this->isValidZip($zipContent)) {
            throw new Exception('下载的文件不是有效的ZIP压缩包');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($zipPath);
        
        if ($result !== TRUE) {
            $errorMsg = $this->getZipError($result);
            throw new Exception('打开压缩包失败: ' . $errorMsg);
        }
        
        // 检查压缩包内容
        if ($zip->numFiles === 0) {
            $zip->close();
            throw new Exception('压缩包为空');
        }
        
        // 解压到临时目录
        $extractPath = $this->tempDir . '/extracted';
        if (is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
        mkdir($extractPath, 0755, true);
        
        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new Exception('解压文件失败，可能是权限问题');
        }
        $zip->close();
        
        // 找到解压后的实际目录
        $extractedContents = scandir($extractPath);
        $sourceDir = null;
        foreach ($extractedContents as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($extractPath . '/' . $item)) {
                $sourceDir = $extractPath . '/' . $item;
                break;
            }
        }
        
        if (!$sourceDir) {
            throw new Exception('找不到解压后的源目录');
        }
        
        // 验证源目录中有必要的文件
        $requiredFiles = ['index.html', 'version'];
        foreach ($requiredFiles as $file) {
            if (!file_exists($sourceDir . '/' . $file)) {
                throw new Exception("解压后的文件不完整，缺少: {$file}");
            }
        }
        
        // 保存需要保留的内容
        $preservedContent = $this->extractPreservedContent();
        
        // 复制新文件（排除某些目录）
        $this->copyDirectory($sourceDir, '..', ['backup', 'temp_update', '.git']);
        
        // 恢复保留的内容
        $this->restorePreservedContent($preservedContent);
        
        // 更新版本文件
        file_put_contents($this->currentVersionFile, $newVersion);
    }
    
    private function getZipError($code) {
        switch($code) {
            case ZipArchive::ER_OK: return '没有错误';
            case ZipArchive::ER_MULTIDISK: return '多磁盘zip档案不支持';
            case ZipArchive::ER_RENAME: return '重命名临时文件失败';
            case ZipArchive::ER_CLOSE: return '关闭zip档案失败';
            case ZipArchive::ER_SEEK: return '寻址错误';
            case ZipArchive::ER_READ: return '读取错误';
            case ZipArchive::ER_WRITE: return '写入错误';
            case ZipArchive::ER_CRC: return 'CRC错误';
            case ZipArchive::ER_ZIPCLOSED: return 'zip档案已关闭';
            case ZipArchive::ER_NOENT: return '没有这样的文件';
            case ZipArchive::ER_EXISTS: return '文件已经存在';
            case ZipArchive::ER_OPEN: return '不能打开文件';
            case ZipArchive::ER_TMPOPEN: return '创建临时文件失败';
            case ZipArchive::ER_ZLIB: return 'Zlib错误';
            case ZipArchive::ER_MEMORY: return '内存分配失败';
            case ZipArchive::ER_CHANGED: return '条目已被改变';
            case ZipArchive::ER_COMPNOTSUPP: return '压缩方法不支持';
            case ZipArchive::ER_EOF: return '过早的EOF';
            case ZipArchive::ER_INVAL: return '无效的参数';
            case ZipArchive::ER_NOZIP: return '不是一个zip档案';
            case ZipArchive::ER_INTERNAL: return '内部错误';
            case ZipArchive::ER_INCONS: return 'Zip档案不一致';
            case ZipArchive::ER_REMOVE: return '不能移除文件';
            case ZipArchive::ER_DELETED: return '条目已被删除';
            default: return "未知错误代码: {$code}";
        }
    }
    
    private function extractPreservedContent() {
        $preserved = [];
        
        foreach ($this->preservePatterns as $file => $patterns) {
            $filePath = '../' . $file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $preserved[$file] = [];
                
                if ($file === 'api/config.php') {
                    // 对于config.php，提取配置值
                    foreach ($patterns as $configKey => $pattern) {
                        if (preg_match('/' . $pattern . '/', $content, $matches)) {
                            $preserved[$file][$configKey] = $matches[1]; // 捕获组中的值
                        }
                    }
                } else {
                    // 对于HTML文件，提取完整的自定义标题内容
                    foreach ($patterns as $key => $pattern) {
                        if (preg_match('/' . $pattern . '/', $content, $matches)) {
                            $preserved[$file][$key] = $matches[1]; // 捕获组中的自定义标题
                        }
                    }
                }
            }
        }
        
        return $preserved;
    }
    
    private function restorePreservedContent($preservedContent) {
        foreach ($preservedContent as $file => $preservedData) {
            $filePath = '../' . $file;
            if (file_exists($filePath) && !empty($preservedData)) {
                $content = file_get_contents($filePath);
                
                if ($file === 'api/config.php') {
                    // 对于config.php，替换配置值
                    foreach ($preservedData as $configKey => $value) {
                        $pattern = $this->preservePatterns[$file][$configKey];
                        $replacement = "define('{$configKey}', '{$value}');";
                        $content = preg_replace('/' . $pattern . '/', $replacement, $content);
                    }
                } else {
                    // 对于HTML文件，恢复自定义标题
                    foreach ($preservedData as $key => $customTitle) {
                        $pattern = $this->preservePatterns[$file][$key];
                        
                        if ($key === 'title') {
                            $replacement = "<title>{$customTitle}</title>";
                        } else {
                            $replacement = "<h1 class=\"text-center main-title\">{$customTitle}</h1>";
                        }
                        
                        $content = preg_replace('/' . $pattern . '/', $replacement, $content);
                    }
                }
                
                file_put_contents($filePath, $content);
            }
        }
    }
    
    private function copyDirectory($source, $destination, $excludeDirs = []) {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $destPath = $destination . '/' . $relativePath;
            
            // 检查是否在排除目录中
            $shouldExclude = false;
            foreach ($excludeDirs as $excludeDir) {
                if (strpos($relativePath, $excludeDir . '/') === 0 || $relativePath === $excludeDir) {
                    $shouldExclude = true;
                    break;
                }
            }
            
            if ($shouldExclude) {
                continue;
            }
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $destPath);
            }
        }
        
        return true;
    }
    
    private function cleanup() {
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }
    
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    private function response($success, $message, $data = null) {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}

// 处理请求
try {
    $updater = new AutoUpdater();
    
    $action = $_GET['action'] ?? 'check';
    
    switch ($action) {
        case 'check':
            echo $updater->checkForUpdates();
            break;
            
        case 'update':
            echo $updater->performUpdate();
            break;
            
        case 'test':
            // 调试模式
            echo json_encode([
                'success' => true,
                'message' => '测试模式',
                'data' => [
                    'curl_available' => function_exists('curl_init'),
                    'file_get_contents_available' => function_exists('file_get_contents'),
                    'zip_available' => class_exists('ZipArchive'),
                    'php_version' => PHP_VERSION,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => '无效的操作'
            ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '系统错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>