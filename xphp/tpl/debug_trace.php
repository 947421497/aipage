<style>
    div#xphp_trace{position:fixed;bottom:0;right:0;font-size:14px;width:100%;z-index:999999;text-align:left}
    div#xphp_trace_tab{display:none;background:white;margin:0;height:250px}
    div#xphp_trace_title{background-color:#f0f1f3;overflow:hidden;height:35px;line-height:35px;padding:0 12px;border-bottom:1px solid #ccc;border-top:1px solid #ccc;font-size:16px}
    div#xphp_trace_title span{color:#000;padding-right:12px;height:35px;line-height:35px;display:inline-block;margin-right:3px;cursor:pointer;font-weight:700}
    div#xphp_trace_content{overflow:auto;height:212px;padding:0;line-height:25px}
    div#xphp_trace_content ul{padding:0;margin:0}
    div#xphp_trace_content ul li{border-bottom:1px solid #ddd;font-size:14px;padding:0 12px}
    div#xphp_trace_content ul li pre{line-height:15px;margin:5px 0}
    div#xphp_trace_close{display:none;text-align:right;height:18px;position:absolute;top:10px;right:12px;cursor:pointer}
    div#xphp_trace_close span{height:18px;vertical-align:top}
    div#xphp_trace_open{height:30px;z-index:9999;float:right;text-align:right;overflow:hidden;position:fixed;bottom:0;right:0;line-height:30px;cursor:pointer}
    div#xphp_trace_open .runtime{background:#232323;color:#FFF;padding:0 6px;float:right;line-height:30px;font-size:14px}
    .xphp_icon {width:32px;height:32px;display:inline-block;text-align:center;background-color:#0da271;color:#fff; }
</style>
<div id="xphp_trace">
    <div id="xphp_trace_tab">
        <div id="xphp_trace_title">
            <?php foreach ($tabs as $title):?>
            <span><?php echo $title?></span>
            <?php endforeach?>
        </div>
        <div id="xphp_trace_content">
            <?php foreach ($trace as $name => $items):?>
            <div style="display:none;">
                <ul>
                    <?php
                    foreach ($items as $key => $val) {
                        echo '<li>';
                        if (!is_numeric($key)) {
                            echo $key.'：';
                        } elseif ($name != 'base' && $name != 'debug') {
                            echo ++$key.'. ';
                        }
                        echo $val.'</li>';
                    }
                    ?>
                </ul>
            </div>
            <?php endforeach?>
        </div>
    </div>
    <div id="xphp_trace_close">
        <span>✗</span>
    </div>
</div>
<div id="xphp_trace_open">
    <span class="xphp_icon">✗</span>
    <div class="runtime"><?php echo $runtime?> <span style="color:red"><?php echo $errors?></span></div>
</div>
<script type="text/javascript">
    (function () {
        let tab_tit = document.getElementById('xphp_trace_title').getElementsByTagName('span');
        let tab_cont = document.getElementById('xphp_trace_content').getElementsByTagName('div');
        let open = document.getElementById('xphp_trace_open');
        let close = document.getElementById('xphp_trace_close').children[0];
        let trace = document.getElementById('xphp_trace_tab');
        let cookie = document.cookie.match(/xphp_show_page_trace=(\d\|\d)/);
        let history = (cookie && typeof cookie[1] != 'undefined' && cookie[1].split('|')) || [0, 0];
        open.onclick = function () {
            trace.style.display = 'block';
            this.style.display = 'none';
            close.parentNode.style.display = 'block';
            history[0] = 1;
            document.cookie = 'xphp_show_page_trace=' + history.join('|')
        }
        close.onclick = function () {
            trace.style.display = 'none';
            this.parentNode.style.display = 'none';
            open.style.display = 'block';
            history[0] = 0;
            document.cookie = 'xphp_show_page_trace=' + history.join('|')
        }
        for (let i = 0; i < tab_tit.length; i++) {
            tab_tit[i].onclick = (function (i) {
                return function () {
                    for (let j = 0; j < tab_cont.length; j++) {
                        tab_cont[j].style.display = 'none';
                        tab_tit[j].style.color = '#999';
                    }
                    tab_cont[i].style.display = 'block';
                    tab_tit[i].style.color = '#000';
                    history[1] = i;
                    document.cookie = 'xphp_show_page_trace=' + history.join('|')
                }
            })(i)
        }
        parseInt(history[0]) && open.click();
        tab_tit[history[1]].click();
    })();
</script>