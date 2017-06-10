<?php
namespace KWS\Widget;


use Think\Controller;

class DepartmentWidget extends Controller
{
    public function department($children)
    {
        $children = explode(',', $children);
        array_shift($children);
        array_pop($children);
        $map['DepartId'] = ['in', $children];
        $list = M('Department')->where([$map])->select();
        if(!empty($list)) {
            $this->assign('list', $list);
            layout(false);
            echo $this->fetch('Department/department');
        }
    }
}