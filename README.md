# WhitelistWebAdmin

## 项目简介
这是一个在线白名单管理系统

## 项目结构
```
whitelistRMSweb/
├── admin/         # 管理员相关功能
├── api/          # API 接口文件
├── assets/       # 静态资源文件
├── css/          # 样式文件
├── images/       # 图片资源
├── js/           # JavaScript 文件
└── index.html    # 主页面
```

## 更换背景图片？

1. 上传图片到 `images/` 目录
2. 将图片改名为 `bg.jpg`
3. 刷新页面（Ctrl+F5）

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

## 部署方式

### 方式一：面板部署（以宝塔面板为例）

1. 面板环境配置
   - 登录宝塔面板
   - 在软件商店中安装：
     - Nginx 或 Apache（选择一个即可）
     - PHP 7.4（勾选 mysqli、pdo_mysql 扩展）
     - MySQL 5.7 或 MariaDB 10.2+
   - 在面板首页"网站-PHP项目"中点击"添加站点"
     - 填写域名（如没有域名可填写 IP）
     - 选择 PHP 版本（推荐PHP82）
     - 点击“确定”

2. 项目部署
   - 在站点目录中上传项目文件
     - 通过面板的文件管理器上传压缩包
     - 解压文件到站点根目录

3. 数据库配置
   - 在面板数据库管理中创建数据库
   - 修改 `api/config.php` 中的数据库连接信息

4. 管理员密码配置
   - 在`api/config.php`文件中配置管理员密码
   ```
   define('ADMIN_PASSWORD', 'YOUR_ADMIN_PASSWORD');
   ```

### 方式二：命令行部署

1. 服务器环境配置
   ```bash
   # CentOS
   # 1. 安装 EPEL 源
   sudo yum install epel-release
   
   # 2. 安装 Nginx、PHP、MySQL
   sudo yum install nginx php php-fpm php-mysql mysql-server
   
   # 3. 启动服务
   sudo systemctl start nginx
   sudo systemctl start php-fpm
   sudo systemctl start mysqld
   
   # 4. 设置开机自启
   sudo systemctl enable nginx
   sudo systemctl enable php-fpm
   sudo systemctl enable mysqld
   ```

   ```bash
   # Ubuntu/Debian
   # 1. 更新包列表
   sudo apt update
   
   # 2. 安装必要软件
   sudo apt install nginx php php-fpm php-mysql mysql-server
   
   # 3. 启动服务
   sudo systemctl start nginx
   sudo systemctl start php-fpm
   sudo systemctl start mysql
   
   # 4. 设置开机自启
   sudo systemctl enable nginx
   sudo systemctl enable php-fpm
   sudo systemctl enable mysql
   ```

2. 项目部署
   ```bash
   # 1. 创建项目目录
   sudo mkdir -p /var/www/html/whitelistRMSweb
   
   # 2. 克隆或上传项目文件
   # 方式一：使用 Git（如果有仓库）
   sudo git clone [repository_url] /var/www/html/whitelistRMSweb
   
   # 方式二：手动上传
   # 在本地使用 scp 命令上传
   scp -r /path/to/local/whitelistRMSweb/* user@server:/var/www/html/whitelistRMSweb/
   
   # 3. 设置目录权限
   sudo chown -R www-data:www-data /var/www/html/whitelistRMSweb
   sudo chmod -R 755 /var/www/html/whitelistRMSweb
   ```

3. 数据库配置
   ```bash
   # 1. 登录 MySQL
   sudo mysql -u root -p
   
   # 2. 创建数据库和用户
   CREATE DATABASE whitelistrms;
   CREATE USER 'whitelistrms_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON whitelistrms.* TO 'whitelistrms_user'@'localhost';
   FLUSH PRIVILEGES;
   
   # 3. 导入数据库（如果有）
   mysql -u whitelistrms_user -p whitelistrms < /path/to/database.sql
   ```

4. 配置 Web 服务器
   ```bash
   # Nginx 配置
   sudo nano /etc/nginx/sites-available/whitelistrms
   
   # 添加以下配置
   server {
       listen 80;
       server_name your_domain.com;
       root /var/www/html/whitelistRMSweb;
       index index.html index.php;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
       }
   }
   
   # 创建符号链接并重启 Nginx
   sudo ln -s /etc/nginx/sites-available/whitelistrms /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

5. 验证部署
   ```bash
   # 检查 Nginx 状态
   sudo systemctl status nginx
   
   # 检查 PHP-FPM 状态
   sudo systemctl status php-fpm
   
   # 检查错误日志
   sudo tail -f /var/log/nginx/error.log
   sudo tail -f /var/log/php/error.log
   ```

## 部署说明

### 环境要求
- PHP 7.0 或更高版本
- Web 服务器（Apache 2.4+ 或 Nginx 1.12+）
- MySQL 5.7+ 或 MariaDB 10.2+

### 常见问题解决
1. 500 错误
   - 检查 PHP 配置文件权限
   - 检查数据库连接配置
   - 查看服务器错误日志

2. 404 错误
   - 检查 URL 重写规则
   - 确认文件权限设置
   - 验证文件路径是否正确

3. 数据库连接错误
   - 确认数据库服务是否运行
   - 验证数据库用户名和密码
   - 检查数据库主机地址

## 有问题仍然无法解决？
欢迎提出[issues](https://github.com/mita-x/whitelistwebadmin/issues)
