{include file='public/header.html'}
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar" id="sidebar">
            {include file='public/sidebar.html'}
        </div>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main" id="main">

            <h3 class="page-header">添加[xx]</h3>
            <div class="panel panel-default">
                <div class="panel-heading">添加[xx]<span class="pull-right"><a href="javascript:history.back(-1);">返回</a></span></div>
                <div class="panel-body">
                    <form class="form-horizontal submit-ajax" role="form" action="{:url('add')}" method="post">

                        <div class="form-group">
                            <label for="title" class="col-sm-1 control-label">*标题</label>
                            <div class="col-sm-11">
                                <input type="text" id="title" name="title" class="form-control" placeholder="请输入标题" value="" />
                            </div>
                        </div>
                        <!--添加字段表单-->

                        <div class="form-group">
                            <label class="col-sm-1 control-label">&nbsp;</label>
                            <div class="col-sm-11">
                                <input type="submit" id="submit" value="添加" class="btn btn-primary" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
{include file='public/footer.html'}
</body>
</html>