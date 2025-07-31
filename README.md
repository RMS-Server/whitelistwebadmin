# WhitelistWebAdmin

## 项目简介
这是一个在线白名单管理系统，用于配合[WhitelistRMS](https://github.com/XRain66/WhitelistRMS)

基于Vue3重写的现代化Web前端，支持响应式设计和自动化构建部署。

## 项目结构
```
whitelistRMSweb/
├── .github/              # GitHub Actions工作流
│   └── workflows/
│       └── build.yml     # 自动化构建配置
├── api/                  # API 接口文件
├── src/                  # Vue3 源码目录
│   ├── views/           # 页面组件
│   ├── components/      # 通用组件
│   ├── stores/          # Pinia状态管理
│   └── services/        # API服务
├── dist/                # 构建输出目录
├── package.json         # 项目依赖配置
├── vite.config.js       # Vite构建配置
└── index.html           # 主页面
```

## 快速部署（推荐）

### 使用GitHub Actions自动构建

1. **Fork本项目到你的GitHub账户**

2. **自定义构建**
   - 进入你的GitHub仓库
   - 点击"Actions"选项卡
   - 选择"Build and Package"工作流
   - 点击"Run workflow"
   - 输入自定义网站名称（如："XXX服务器白名单管理系统"）
   - 点击运行

3. **下载构建产物**
   - 构建完成后，在Actions页面下载artifacts
   - 下载`release-package`压缩包

4. **服务器部署**
   - 解压下载的压缩包到Web服务器目录
   - 配置数据库连接（参考下方配置说明）

### 本地开发部署

如果你需要修改代码或本地开发：

```bash
# 1. 克隆项目
git clone https://github.com/your-username/whitelistRMSweb.git
cd whitelistRMSweb

# 2. 安装依赖
npm install

# 3. 启动开发服务器
npm run dev

# 4. 构建生产版本
npm run build
```

## 配置说明

### 数据库配置

修改 `api/config.php` 文件：

```php
<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// 管理员密码
define('ADMIN_PASSWORD', 'your_admin_password');
?>
```

### 更换背景图片

1. 将新背景图片放置到 `src/assets/` 目录
2. 重命名为 `bg.webp`（或修改CSS中的引用路径）
3. 重新构建项目

## API
在此版项目中提供了一个API接口用于查询待处理的玩家，返回json格式的数据

### 接口说明
- 接口地址：https://example.com/api/get-pending-applications.php
- 请求方式：GET
- 响应格式：JSON

### 响应示例

```json
// 有待处理申请时
{
    "success": true,
    "message": "获取成功",
    "data": [
        {
            "id": "1",
            "username": "player1",
            "created_at": "2025-01-07 10:00:00",
            "index": 1
        },
        {
            "id": "2",
            "username": "player2",
            "created_at": "2025-01-07 11:00:00",
            "index": 2
        }
    ]
}

// 无待处理申请时
{
    "success": true,
    "message": "无待处理的申请"
}

// 发生错误时
{
    "success": false,
    "message": "错误信息"
}
```

## 传统部署方式

### 方式一：面板部署（以宝塔面板为例）

1. **环境要求**
   - PHP 7.4+ （需要 mysqli、pdo_mysql 扩展）
   - Nginx 或 Apache
   - MySQL 5.7+ 或 MariaDB 10.2+

2. **面板配置**
   - 登录宝塔面板
   - 安装必要组件：Nginx、PHP、MySQL
   - 创建站点，选择PHP版本
   - 上传构建后的文件到站点目录

3. **数据库配置**
   - 创建数据库和用户
   - 修改 `api/config.php` 配置文件

4. **访问测试**
   - 打开浏览器访问你的域名
   - 测试用户申请和管理员功能


### 方式二：Docker部署（推荐）

```bash
# 1. 创建docker-compose.yml
version: '3.8'
services:
  web:
    image: php:8.0-apache
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: whitelistrms
      MYSQL_USER: whitelistrms_user
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:

# 2. 启动服务
docker-compose up -d
```

### 方式三：命令行部署

**Ubuntu/Debian:**
```bash
# 1. 安装环境
sudo apt update
sudo apt install nginx php php-fpm php-mysql mysql-server nodejs npm

# 2. 部署项目
git clone your-repo /var/www/html/whitelistRMSweb
cd /var/www/html/whitelistRMSweb
npm install && npm run build

# 3. 配置权限
sudo chown -R www-data:www-data /var/www/html/whitelistRMSweb
sudo chmod -R 755 /var/www/html/whitelistRMSweb
```

**CentOS/RHEL:**
```bash
# 1. 安装环境
sudo yum install epel-release
sudo yum install nginx php php-fpm php-mysql mysql-server nodejs npm

# 2. 启动服务
sudo systemctl enable --now nginx php-fpm mysqld
```

## 环境要求

### 最低配置
- **前端**: 现代浏览器（Chrome 80+, Firefox 75+, Safari 13+）
- **后端**: PHP 7.4+ （需要 mysqli、pdo_mysql 扩展）
- **数据库**: MySQL 5.7+ 或 MariaDB 10.2+
- **Web服务器**: Nginx 1.18+ 或 Apache 2.4+

### 推荐配置
- **操作系统**: Ubuntu 20.04+ / CentOS 8+ / Docker
- **PHP**: 8.0+
- **内存**: 512MB+
- **存储**: 1GB+

## 故障排除

### 常见问题

**构建失败**
```bash
# 清除缓存重新安装
rm -rf node_modules package-lock.json
npm install
npm run build
```

**API接口错误**
- 检查 `api/config.php` 数据库配置
- 确认PHP扩展已安装：`php -m | grep mysql`
- 查看PHP错误日志

**权限问题**
```bash
# 设置正确的文件权限
sudo chown -R www-data:www-data /path/to/project
sudo chmod -R 755 /path/to/project
```

**跨域问题**
- 检查 `vite.config.js` 中的代理配置
- 确认API接口返回正确的CORS头

## 升级指南

从旧版本升级：
1. 备份现有数据库
2. 使用GitHub Actions构建新版本
3. 替换文件但保留 `api/config.php`
4. 测试功能正常性

## 贡献

欢迎提交PR和Issues！

- Fork项目
- 创建特性分支：`git checkout -b feature/your-feature`
- 提交更改：`git commit -am 'Add some feature'`
- 推送分支：`git push origin feature/your-feature`
- 提交Pull Request
