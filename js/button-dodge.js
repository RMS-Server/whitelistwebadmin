// 为提交按钮添加点击事件监听器
document.addEventListener('DOMContentLoaded', function() {
    const submitButton = document.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.addEventListener('click', function(event) {
            // 20%的概率触发掉落动画
            if (Math.random() < 0) {
                event.preventDefault(); // 阻止表单提交
                triggerFallAnimation1();
            }
        });
    }
});

function triggerFallAnimation1() {
    // 获取所有需要添加动画的元素，包括版权信息
    const elements = document.querySelectorAll('.card, .card-header, .btn, tr, td, th, h1, .alert, .copyright');
    
    // 为每个元素添加掉落动画类
    elements.forEach(element => {
        element.classList.add('falling-element');
    });
    
    // 显示第一个成就弹窗
    const achievement2 = showAchievement('新成就：坏……坏了？');
    
    // 2秒后收回第一个成就弹窗
    setTimeout(() => {
        achievement2.classList.add('slide-out');
        setTimeout(() => {
            achievement2.remove();
        }, 500);
    }, 3000);

    // 2秒后使所有元素（除了弹窗）消失
    setTimeout(() => {
        elements.forEach(element => {
            if (!element.classList.contains('achievement-popup')) {
                element.style.display = 'none';
            }
        });
    }, 2000);
}