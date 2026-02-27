<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{if $status==1:}操作成功{else:}操作失败{/if}！</title>
    <style>
        * {margin:0;padding:0;box-sizing:border-box;}
        body {font-family:system-ui,-apple-system,sans-serif;background:#f5f7fa;color:#333;min-height:100vh;padding:40px 20px;}
        a {color:#3b82f6;text-decoration:none;}
        .status-container {max-width:420px;width:100%;background:white;border-radius:12px;box-shadow:0 6px 24px rgba(0,0,0,0.12);padding:55px 35px 35px;text-align:center;margin:0 auto;border:1px solid #e1e8f0;}
        .status-icon {width:65px;height:65px;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:bold;margin:0 auto 25px;}
        .status-1 .status-icon {background:linear-gradient(135deg,#10b981,#34d399);box-shadow:0 4px 12px rgba(16,185,129,0.2);}
        .status-0 .status-icon {background:linear-gradient(135deg,#ef4444,#f87171);box-shadow:0 4px 12px rgba(239,68,68,0.2);}
        .status-message {font-size:18px;font-weight:500;margin-bottom:20px;line-height:1.5;padding:0 10px;}
        .status-1 .status-message {color:#10b981;}
        .status-0 .status-message {color:#ef4444;}
        .status-detail {color:#64748b;font-size:15px;margin-bottom:15px;line-height:1.4;padding:0 5px;}
        .countdown-number {color:#3b82f6;font-weight:600;}
        .button-group {display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:25px;}
        .btn {display:inline-block;text-decoration:none;font-weight:500;font-size:0.9rem;padding:10px 24px;border-radius:6px;transition:all 0.2s;min-width:120px;text-align:center;}
        .btn-primary {background:#2563eb;color:white;}
        .btn-primary:hover {background:#3b82f6;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,0.2);}
        .btn-secondary {background:#f1f5f9;color:#4b5563;border:1px solid #e5e7eb;}
        .btn-secondary:hover {background:#e5e7eb;transform:translateY(-1px);}
        .footer {margin-top:15px;padding-top:20px;border-top:2px solid #f0f4f8;color:#94a3b8;font-size:0.9rem;}
        @media (max-width:480px) {body {padding:30px 15px;}
            .status-container {padding:35px 25px 25px;box-shadow:0 4px 16px rgba(0,0,0,0.1);}
            .status-icon {width:55px;height:55px;font-size:26px;margin-bottom:20px;}
            .status-message {font-size:17px;margin-bottom:18px;}
            .status-detail {font-size:14px;margin-bottom:20px;}
            .button-group {flex-direction:column;align-items:center;gap:10px;}
            .btn {font-size:0.85rem;padding:12px 20px;width:80%;}
        }
    </style>
</head>
<body>

<div class="status-container status-{$status}">
    {if $status==1:}
        <div class="status-icon">✓</div>
        <div class="status-message">{$msg}</div>
        <div class="status-detail">
            <span id="wait" class="countdown-number">5</span> 秒后自动 <a id="href" href="{$url}">跳转</a>
            <script>
                (function () {
                    const wait = document.getElementById('wait');
                    const href = document.getElementById('href').href;
                    window.setInterval(function() {
                        const time = --wait.innerHTML;
                        if (time <= 0) {
                            location.href = href;
                        }
                    }, 1000);
                })();
            </script>
        </div>
    {else:}
        <div class="status-icon">✗</div>
        <div class="status-message">{$msg}</div>
        <div class="status-detail">tips：失败了...</div>
    {/if}
    <div class="button-group">
        <a href="{$url}" class="btn btn-primary">确认</a>
        <a href="{:url('index/index')}" class="btn btn-secondary">返回首页</a>
    </div>
    <div class="footer">
        <a href="https://xphp.net" target="_blank">XPHP</a> © {:date('Y')} • 轻量设计 • 高效开发
    </div>
</div>
</body>
</html>