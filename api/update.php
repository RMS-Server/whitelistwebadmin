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
            '<title>RMS白名单管理系统</title>',
            '<h1 class="text-center main-title">RMS白名单管理系统</h1>'
        ],
        'admin/index.html' => [
            '<title>RMS白名单管理系统 - 管理员</title>',
            '<h1 class="text-center main-title">RMS白名单管理系统</h1>',
            '<h1 class="text-center main-title">RMS白名单管理系统 - 管理员</h1>'
        ]
    ];
    
    public function checkForUpdates() {
        try {
            // 获取远程版本
            $remoteVersion = $this->getRemoteVersion();
            if (!$remoteVersion) {
                return $this->response(false, '无法获取远程版本信息');
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
        $url = "https://raw.githubusercontent.com/{$this->githubRepo}/main/version";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: RMS-AutoUpdater/1.0',
                'timeout' => 30
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return false;
        }
        
        return trim($content);
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
        
        $zipUrl = "https://github.com/{$this->githubRepo}/archive/main.zip";
        $zipPath = $this->tempDir . '/latest.zip';
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: RMS-AutoUpdater/1.0',
                'timeout' => 300
            ]
        ]);
        
        $zipContent = @file_get_contents($zipUrl, false, $context);
        if ($zipContent === false) {
            throw new Exception('下载压缩包失败');
        }
        
        if (file_put_contents($zipPath, $zipContent) === false) {
            throw new Exception('保存压缩包失败');
        }
        
        return $zipPath;
    }
    
    private function extractAndInstall($zipPath, $newVersion) {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            throw new Exception('打开压缩包失败');
        }
        
        // 解压到临时目录
        $extractPath = $this->tempDir . '/extracted';
        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new Exception('解压文件失败');
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
        
        // 保存需要保留的内容
        $preservedContent = $this->extractPreservedContent();
        
        // 复制新文件（排除某些目录）
        $this->copyDirectory($sourceDir, '..', ['backup', 'temp_update', '.git']);
        
        // 恢复保留的内容
        $this->restorePreservedContent($preservedContent);
        
        // 更新版本文件
        file_put_contents($this->currentVersionFile, $newVersion);
    }
    
    private function extractPreservedContent() {
        $preserved = [];
        
        foreach ($this->preservePatterns as $file => $patterns) {
            $filePath = '../' . $file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $preserved[$file] = [];
                
                foreach ($patterns as $pattern) {
                    // 使用正则表达式匹配完整的标签行
                    if (preg_match('/.*' . preg_quote($pattern, '/') . '.*/', $content, $matches)) {
                        $preserved[$file][] = [
                            'pattern' => $pattern,
                            'full_line' => trim($matches[0])
                        ];
                    }
                }
            }
        }
        
        return $preserved;
    }
    
    private function restorePreservedContent($preservedContent) {
        foreach ($preservedContent as $file => $preservedLines) {
            $filePath = '../' . $file;
            if (file_exists($filePath) && !empty($preservedLines)) {
                $content = file_get_contents($filePath);
                
                foreach ($preservedLines as $preserved) {
                    $pattern = $preserved['pattern'];
                    $fullLine = $preserved['full_line'];
                    
                    // 查找类似的行并替换
                    $content = preg_replace(
                        '/.*' . preg_quote($pattern, '/') . '.*/',
                        $fullLine,
                        $content
                    );
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