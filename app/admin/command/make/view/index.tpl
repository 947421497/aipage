{include file='public/header.html'}
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar" id="sidebar">
            {include file='public/sidebar.html'}
        </div>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main" id="main">

            <h3 class="page-header">[xx]管理</h3>
            <div class="panel panel-default">
                <div class="panel-heading">[xx]管理<span class="pull-right"><a href="javascript:history.back(-1);">返回</a></span></div>
                <div class="panel-body">
                    <form class="form-inline form-top" method="get" action="{:url('index')}">
                        <div class="form-group">
                            <label for="title">标题：</label>
                            <input type="text" id="title" name="title" value="{:input('title','','clear_html')}" class="form-control" placeholder="搜索标题"/>
                        </div>
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="{:url('index')}" class="btn btn-default">全部</a>
                    </form>
                    <a href="{:url('add')}" class="btn btn-primary">添加</a>
                    <button type="button" class="btn btn-success" onclick="actionConfirm('启用','{:url('state?params=status-1')}');">启用</button>
                    <button type="button" class="btn btn-info" onclick="actionConfirm('停用','{:url('state?params=status-0')}');">停用</button>
                    <button type="button" class="btn btn-danger" onclick="actionConfirm('删除', '{:url('del')}');">删除</button>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <th width="50"><input type="checkbox" onclick="selectAll(this.checked)" /></th>
                        <th>ID</th>
                        <th>标题</th>
                        <!--<th>字段</th>-->
                        <th>状态</th>
                        <th>操作</th>
                        </thead>
                        <tbody>
                        {foreach $list as $vo}
                            <tr>
                                <td><input type="checkbox" name="ids" value="{$vo.id}" /> </td>
                                <td>{$vo.id}</td>
                                <td><a href="{:url('edit',['id'=>$vo['id']])}">{$vo.title}</a></td>
                                <!--<td>$vo.field</td>-->
                                <td>
                                    {if $vo['status']==1:}
                                        <a href="javascript:ajaxConfirm('{:url('state?params=status-0',['ids'=>$vo['id']])}','停用',2);" class="btn btn-xs btn-success">已启用</a>
                                    {else:}
                                        <a href="javascript:ajaxConfirm('{:url('state?params=status-1',['ids'=>$vo['id']])}','启用',2);" class="btn btn-xs btn-default">已停用</a>
                                    {/if}
                                </td>
                                <td>
                                    <a href="{:url('edit',['id'=>$vo['id']])}" class="btn btn-xs btn-primary">编辑</a>
                                    <a href="javascript:ajaxConfirm('{:url('del',['ids'=>$vo['id']])}','删除',2);" class="btn btn-xs btn-danger">删除</a>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="panel-footer">{empty $list->toArray():}暂无记录！{/empty}{$list->links()}</div>
            </div>

        </div>
    </div>
</div>
{include file='public/footer.html'}
</body>
</html>