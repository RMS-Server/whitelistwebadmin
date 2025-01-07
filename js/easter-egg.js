// 彩蛋功能
let easterEggActivated = false;

// 检查是否需要显示收回动画
window.addEventListener('load', () => {
    if (localStorage.getItem('showReturnAchievement')) {
        localStorage.removeItem('showReturnAchievement');
        const achievement = showAchievement('新成就：回到起点');
        // 3秒后收回
        setTimeout(() => {
            achievement.classList.add('slide-out');
            setTimeout(() => {
                achievement.remove();
            }, 500);
        }, 3000);
    }
});

function checkEasterEgg(username) {
    // 转换为小写进行比较
    const lowerUsername = username.toLowerCase();
    if (lowerUsername === 'mita' || lowerUsername === 'miside') {
        if (!easterEggActivated) {
            // 第一次触发
            document.documentElement.style.setProperty('--easter-egg-color', '#FF69B4');
            document.body.classList.add('easter-egg-active');
            const achievement = showAchievement('新成就：欸？这是什么');
            // 3秒后收回
            setTimeout(() => {
                achievement.classList.add('slide-out');
                setTimeout(() => {
                    achievement.remove();
                }, 500);
            }, 3000);
            easterEggActivated = true;
        } else {
            // 第二次触发，执行掉落动画
            triggerFallAnimation();
        }
        return true;
    }
    return false;
}

function showAchievement(text) {
    // 创建成就弹窗
    const achievement = document.createElement('div');
    achievement.className = 'achievement-popup';
    achievement.textContent = text;
    document.body.appendChild(achievement);
    return achievement;
}

function triggerFallAnimation() {
    // 获取所有需要添加动画的元素，包括版权信息
    const elements = document.querySelectorAll('.card, .card-header, .btn, tr, td, th, h1, .alert, .copyright');
    
    // 为每个元素添加掉落动画类
    elements.forEach(element => {
        element.classList.add('falling-element');
    });
    
    // 显示第一个成就弹窗
    const achievement = showAchievement('新成就：一切的终结');
    
    // 2秒后让第一个成就弹窗掉落
    setTimeout(() => {
        achievement.classList.add('falling-element');
    }, 2000);

    // 4秒后显示雪花屏效果
    setTimeout(() => {
        createColorStaticEffect();
    }, 4000);

    // 设置标记，在页面刷新后显示收回动画
    localStorage.setItem('showReturnAchievement', 'true');
    
    // 6秒后刷新页面
    setTimeout(() => {
        location.reload();
    }, 10000);
}

// 创建彩色雪花屏效果
function createColorStaticEffect() {
    const container = document.createElement('div');
    container.className = 'tv-static';
    
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { alpha: false }); // 禁用alpha通道以提高性能
    
    // 设置canvas大小，但使用较小的实际渲染尺寸来创建大像素效果
    const pixelSize = 4; // 像素块大小
    
    // 优化：根据设备性能调整渲染尺寸
    const performanceMultiplier = window.devicePixelRatio > 1 ? 0.75 : 1;
    canvas.width = Math.floor(window.innerWidth * performanceMultiplier);
    canvas.height = Math.floor(window.innerHeight * performanceMultiplier);
    const renderWidth = Math.ceil(canvas.width / pixelSize);
    const renderHeight = Math.ceil(canvas.height / pixelSize);
    
    // 创建离屏canvas用于缓存
    const offscreenCanvas = document.createElement('canvas');
    offscreenCanvas.width = renderWidth;
    offscreenCanvas.height = renderHeight;
    const offscreenCtx = offscreenCanvas.getContext('2d', { alpha: false });
    
    container.appendChild(canvas);
    document.body.appendChild(container);

    // 预计算颜色数组以提高性能
    const colors = [
        '#FF69B4', '#FF69B4', '#FF69B4', '#FF69B4', '#FF69B4', // 紫粉色 x5
        '#32CD32', '#32CD32', // 绿色 x2
        '#4169E1', '#4169E1', // 蓝色 x2
        '#FFD700'  // 金色 x1
    ].map(color => {
        const rgb = hexToRgb(color);
        return [rgb.r, rgb.g, rgb.b];
    });
    
    let frameCount = 0;
    let lastTime = 0;
    const targetFPS = 60;
    const frameInterval = 1000 / targetFPS;
    let animationFrameId = null;

    // 创建一次性的ImageData对象
    const imageData = offscreenCtx.createImageData(renderWidth, renderHeight);
    const data = imageData.data;
    const dataLength = data.length;

    function animate(currentTime) {
        if (!lastTime) lastTime = currentTime;
        const deltaTime = currentTime - lastTime;

        if (deltaTime >= frameInterval) {
            frameCount++;
            
            // 添加随机闪烁效果
            const flashIntensity = Math.random() > 0.95 ? 1.5 : 1;
            
            // 优化：使用定型数组和预计算的颜色
            for (let i = 0; i < dataLength; i += 4) {
                const color = colors[Math.floor(Math.random() * colors.length)];
                const noise = (Math.random() - 0.5) * 50 * flashIntensity;
                
                data[i] = Math.max(0, Math.min(255, color[0] + noise));     // R
                data[i + 1] = Math.max(0, Math.min(255, color[1] + noise)); // G
                data[i + 2] = Math.max(0, Math.min(255, color[2] + noise)); // B
                data[i + 3] = 255;
            }
            
            // 使用离屏渲染
            offscreenCtx.putImageData(imageData, 0, 0);
            
            // 清除主画布并填充黑色背景
            ctx.fillStyle = '#000';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // 将离屏canvas的内容绘制到主画布
            ctx.imageSmoothingEnabled = false;
            ctx.drawImage(offscreenCanvas, 0, 0, renderWidth, renderHeight, 0, 0, canvas.width, canvas.height);
            
            // 添加扫描线效果（优化：减少重绘）
            if (frameCount % 2 === 0) {
                const scanLineY = (frameCount * 4) % canvas.height;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
                ctx.fillRect(0, scanLineY, canvas.width, pixelSize);
                
                // 随机添加水平干扰线（降低频率）
                if (Math.random() > 0.8) {
                    const glitchY = Math.random() * canvas.height;
                    const glitchHeight = Math.random() * 20 + 5;
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.2)';
                    ctx.fillRect(0, glitchY, canvas.width, glitchHeight);
                }
            }

            lastTime = currentTime;
        }
        
        if (container.parentNode) {
            animationFrameId = requestAnimationFrame(animate);
        } else {
            cancelAnimationFrame(animationFrameId);
        }
    }
    
    // 添加清理函数
    container.cleanup = function() {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }
        container.remove();
    };
    
    animationFrameId = requestAnimationFrame(animate);
    return container;
}

// 优化hexToRgb函数
const hexToRgbCache = new Map();
function hexToRgb(hex) {
    if (hexToRgbCache.has(hex)) {
        return hexToRgbCache.get(hex);
    }
    
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (!result) return {r: 0, g: 0, b: 0};
    
    const rgb = {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    };
    
    hexToRgbCache.set(hex, rgb);
    return rgb;
}
