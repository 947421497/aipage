{include file='public/header.html'}
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar" id="sidebar">
            {include file='public/sidebar.html'}
        </div>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main" id="main">

            <h3 class="page-header">标题</h3>
            <div class="panel panel-default">
                <div class="panel-heading">标题<span class="pull-right"><a href="javascript:history.back(-1);">返回</a></span></div>
                <div class="panel-body">
                    内容
                </div>
                <div class="panel-footer">页脚</div>
            </div>

        </div>
    </div>
</div>
{include file='public/footer.html'}
</body>
</html>