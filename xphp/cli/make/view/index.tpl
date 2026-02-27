<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XPHP - 欢迎使用 XPHP 框架</title>
    <link rel="shortcut icon" href="__ROOT__/favicon.ico" type="image/x-icon">
    <style>
        * {margin:0;padding:0;box-sizing:border-box;}
        body {font-family:system-ui,-apple-system,sans-serif;background:#fff;color:#222;min-height:100vh;display:flex;flex-direction:column;}
        a {color:#2575fc;text-decoration:none;}
        .header {background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);padding:20px 20px 60px;}
        .header-inner {max-width:600px;margin:0 auto;position:relative;}
        .nav {display:flex;justify-content:space-between;margin-bottom:30px;}
        .nav-left,.nav-right {display:flex;gap:20px;}
        .nav a {color:rgba(255,255,255,0.85);text-decoration:none;font-size:0.96rem;padding:5px 0;transition:color 0.2s;position:relative;}
        .nav a:hover {color:white;}
        .nav a:hover::after {content:'';position:absolute;bottom:0;left:0;width:100%;height:2px;background:white;border-radius:1px;}
        .header-content {text-align:center;}
        .logo {font-size:2.8rem;font-weight:800;color:white;margin-bottom:6px;}
        .desc {color:rgba(255,255,255,0.9);font-size:1.15rem;margin-bottom:18px;}
        .header-buttons {display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
        .quick-start-btn {display:inline-block;background:#0da271;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;padding:9px 22px;border-radius:20px;transition:all 0.2s;box-shadow:0 4px 12px rgba(0,0,0,0.15);}
        .quick-start-btn:hover {background:#10b981;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.2);color:white;}
        .qq-group-btn {display:inline-block;background:#0fa5e0;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;padding:9px 22px;border-radius:20px;transition:all 0.2s;box-shadow:0 4px 12px rgba(0,0,0,0.15);}
        .qq-group-btn:hover {background:#12b7f5;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.2);color:white;}
        .card {max-width:600px;width:90%;margin:-25px auto 25px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);padding:25px;}
        .status {display:inline-flex;align-items:center;gap:8px;color:#10b981;margin-bottom:20px;padding:7px 16px;border:2px solid #10b98155;border-radius:20px;font-size:0.9rem;font-weight:bold;}
        .status::before {content:"✓";font-weight:bold;}
        .section-title {font-size:1.1rem;font-weight:600;color:#1f2937;margin:20px 0 12px;padding-bottom:6px;border-bottom:2px solid #f0f5ff;}
        .section-title:first-of-type {margin-top:0;}
        .commands {margin-top:5px;margin-bottom:15px;}
        .command {font-family:'Monaco','Menlo',monospace;font-size:0.85rem;padding:11px 14px;margin:8px 0;border-left:4px solid #3b82f6;background:#f8fafc;border-radius:0 6px 6px 0;overflow-x:auto;}
        code {color:#6a11cb;font-size:1rem;}
        .features {display:grid;grid-template-columns:repeat(2,1fr);gap:12px;}
        .feature {background:#f8fafc;padding:14px;border-radius:8px;border-left:4px solid #3b82f6;}
        .feature-title {font-weight:600;color:#1f2937;margin-bottom:5px;font-size:1rem;}
        .feature-desc {color:#1f2937;font-size:0.85rem;line-height:1.4;}
        .footer {background:#f8fafc;padding:25px 20px;margin-top:auto;}
        .footer-inner {max-width:600px;margin:0 auto;}
        .copyright {text-align:right;color:#6b7280;font-size:0.9rem;padding-top:15px;margin-top:15px;border-top:1px solid #e5e7eb;}
        @media (max-width:640px) {
            .header {padding:15px 15px 45px;}
            .header-inner {padding:0 10px;}
            .nav {gap:15px;margin-bottom:25px;}
            .nav-left,.nav-right {gap:15px;}
            .nav a {font-size:0.85rem;}
            .logo {font-size:2.3rem;}
            .desc {font-size:0.95rem;margin-bottom:15px;}
            .header-buttons {gap:10px;}
            .quick-start-btn,.qq-group-btn {font-size:0.85rem;padding:8px 18px;}
            .card {width:95%;padding:20px 18px;margin-top:-22px;}
            .features {grid-template-columns:1fr;gap:10px;}
            .feature {padding:12px;}
            .section-title {font-size:1rem;margin:18px 0 10px;}
            .copyright {text-align:center;font-size:0.78rem;}
        }
        @media (max-width:480px) {
            .nav {flex-direction:column;gap:12px;}
            .nav-left,.nav-right {justify-content:center;}
            .header-buttons {flex-direction:column;align-items:center;}
            .quick-start-btn,.qq-group-btn {width:80%;text-align:center;}
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-inner">
        <nav class="nav">
            <div class="nav-left">
                <a href="{:url('index/index')}">首页</a>
                <a href="{:url('index/test')}">404</a>
                <a href="{:url('index/msg?ok=1')}">成功</a>
                <a href="{:url('index/msg')}">失败</a>
            </div>
            <div class="nav-right">
                <a href="https://xphp.net/docs" target="_blank">文档</a>
                <a href="https://gitee.com/xphpnet/xphp" target="_blank">Gitee</a>
                <a href="https://github.com/xphpnet/xphp" target="_blank">GitHub</a>
                <a href="https://xphp.net" target="_blank">官网</a>
            </div>
        </nav>

        <div class="header-content">
            <div class="logo">XPHP</div>
            <div class="desc">规范化、轻量级PHP开发框架</div>
            <div class="header-buttons">
                <a href="https://xphp.net/docs" title="查看开发文档" target="_blank" class="quick-start-btn">快速开始</a>
                <a href="https://qm.qq.com/cgi-bin/qm/qr?k=U7SzseDDXSbG9sB1CTEf5U10oFJOKR8-&jump_from=webapi" title="交流Q群：325825297" target="_blank" class="qq-group-btn">加入QQ群</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="status">应用<span style="color:green;">{{$app}}</span>初始化成功！</div>

    <h3 class="section-title">基本配置</h3>
    <div class="commands">
        <div class="command">创建<code>.env</code>本地配置环境：<code>rename env.example.env .env</code></div>
        <div class="command">调试开启或关闭：配置 <code>debug</code> 和 <code>trace</code></div>
        <div class="command">配置数据库：在 <code>.env</code> 或 <code>config/database.php</code> 中配置</div>
    </div>

    <h3 class="section-title">常用命令 <code>php xphpcli</code></h3>
    <div class="commands">
        <div class="command"><code>php xphpcli make:ctrl index@test</code> #生成控制器</div>
        <div class="command"><code>php xphpcli clear:runtime</code> #清空缓存目录</div>
        <div class="command"><code>php xphpcli index@index:do</code> #运行控制器方法</div>
    </div>

    <h3 class="section-title">框架特性</h3>
    <div class="features">
        <div class="feature">
            <div class="feature-title">轻量高效</div>
            <div class="feature-desc">按需加载<200KB，支持 Redis 缓存</div>
        </div>
        <div class="feature">
            <div class="feature-title">快速生成</div>
            <div class="feature-desc">MVC分层清晰，支持 CLI 快速生成</div>
        </div>
        <div class="feature">
            <div class="feature-title">简化开发</div>
            <div class="feature-desc">ORM+模板标签，自动验证过滤处理</div>
        </div>
        <div class="feature">
            <div class="feature-title">规范安全</div>
            <div class="feature-desc">规范命名，CSRF-防XSS-防SQL注入</div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="footer-inner">
        <div class="copyright">
            XPHP © {:date('Y')} • 轻量设计 • 高效开发 • Powered by <a href="https://xphp.net" target="_blank">__POWERED__</a>
        </div>
    </div>
</footer>
</body>
</html>