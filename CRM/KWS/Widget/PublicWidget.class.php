<?php
namespace KWS\Widget;

use Think\Controller;

class PublicWidget extends Controller
{
    // 头部导航
    public function head()
    {
        layout(false);
        $this->display("Common/head");
    }

    // 左侧菜单
    public function menu()
    {
        layout(false);
        $this->display('Common/menu');
    }

    // 面包屑
    public function breadcrumbs($action)
    {
        layout(false);
        $this->display("Common/breadcrumbs");
    }
}