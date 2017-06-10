<?php
namespace KWS\Controller;

class AuthController extends BaseController
{
    /**
     * 节点列表
     */
    public function index()
    {
        $AuthRule = M('AuthRule');
        $list = $AuthRule->order('name')->select();
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加节点
     */
    public function addNode()
    {
        $this->display();
    }

    /**
     * 添加节点
     */
    public function doAddNode()
    {
      $data =   M('authRule')->create();
        $data['status'] = 1;
        $res =  M('authRule')->add($data);
        if($res){
            $arr = [
                'code' => '200',
                'msg' => '保存成功',
                'layer' => 'yes'
            ];
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'添加了节点信息Auth/addNode'.I('title'),1);
        }else{
            $arr = [
                'code' => '400',
                'msg' => '保存失败',

            ];
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 显示编辑节点
     */
    public function editNode()
    {
        $d = M('AuthRule')->find($_GET['id']);
        $this->assign('d', $d);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑节点信息
     */
    public function doEditNode()
    {
        $this->edit('AuthRule', true);
        //操作记录
        operateLog($this->user['userid'],$this->user['realname'],'修改了节点信息'.I('title'),3);
    }

    /**
     * 删除节点信息
     */
    public function delNode()
    {
        $this->delete('AuthRule');
    }

    /**
     * 分组|角色管理
     */
    public function group()
    {
        $list = M('AuthGroup')->order('status')->select();
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加分组|角色
     */
    public function addGroup()
    {
        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑分组|角色
     */
    public function editGroup()
    {
        $id = I("id");
        $d = M('AuthGroup')->find($id);
        $this->assign('d', $d);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 执行添加、编辑分组
     */
    public function doEditGroup()
    {
        //操作记录
        operateLog($this->user['userid'],$this->user['realname'],'修改了分组信息'.I('title'),3);
        $this->edit('AuthGroup', true);

    }

    /**
     * 分配功能
     */
    public function assignGroupNode()
    {
        $rules = M('AuthRule')->select();
        $nrules = [];
        foreach ($rules as $key => $val) {
            $arr = explode('/', $val['name']);
            $controller = ucfirst($arr[0]);
            $nrules[$controller][] = $val;
        }
        $this->assign("rules", $nrules);

        $controller = [
            'Department' => '部门管理',
            'Auth' => '权限管理',
            'Store' => '门店管理',
            'Brand' => '品牌管理',
            'Hall' => '婚礼堂管理',
            'User' => '用户管理',
            'Intention' => '意向管理',
            'Source' => '来源管理',
            'Promoter' => '邀约手管理',
            'Seller' => '销售管理',
            'Order' => '销售管理',
            'Count' => '数据统计',
        ];
        $this->assign("controllers", $controller);


        $id = I('id');
        $this->assign('id', $id);
        $data = M('authGroup')->field('rules')->where(['id' => $id])->find();
        $myrules = explode(',', $data['rules']);
        $this->assign('myrules', $myrules);
        $this->display();
    }

    public function getAllNode()
    {
        echo "<pre>";
        $allNode = [];
        $dir = __DIR__;
        $dh = opendir($dir);
        $Black = ['Base', 'Customer', 'Finance', 'Webkit', 'Public', 'Queue', 'Socket', 'Ajax'];
        while (false !== ($filename = readdir($dh))) {
            $controller = substr($filename, 0, -20);
            if ($filename == '.' || $filename == '..' || in_array($filename, $Black))
            continue;
            $fileStr = file_get_contents($dir . '/' . $filename);
            $pattern = "/public function (.*)\(/";
            // $pattern = "/\/\*\n(.*)\n\*\//s";
            preg_match_all($pattern, $fileStr, $mathes);
            print_r($mathes);
            $allNode[$controller] = $this->getControllerNode($controller, $mathes[1]);

        }

        print_r($allNode);
    }

    protected function getControllerNode($controller, $nodes)
    {
        $AuthRule = M('AuthRule');
        $actions = [];
        foreach ($nodes as $val) {
            if ($val == '_initialize' || stripos($val, 'doEdit') == 0 || stripos($val, 'show') == 0) {
                continue;
            }
            $action = $controller . '/' . $val;
            $result = $AuthRule->where(['name' => $action])->find();
            if (empty($result)) $AuthRule->add([
                'name' => $action,
                'title' => $controller . '|' . $val,
            ]);
        }

        return $actions;
    }

    public function  doEditAuth()
    {
        $nodes = I('node');
        $id = I('id');
        $d['rules'] = implode(',', $nodes);
        $res = M('authGroup')->where(['id' => $id])->save($d);

        if ($res) {
            operateLog($this->user['userid'], $this->user['realname'], '修改了角色权限数据', 3);
            $data = array(
                'code' => '200',
                'msg' => '保存成功'
            );
        } else {
            $data = array(
                'code' => '400',
                'msg' => '保存失败'
            );
        }
        $this->ajaxReturn($data);
    }

    /**
     * 导出功能
     */
    public function expNode()
    {
        $list = M('AuthRule')->order('Id desc')->select();
        $xlsCell = [
            ['Id','编号'],['title','节点名称'],  ['name','节点']
        ];
        $data = [];

        foreach($list as $key=>$val){
            $data[$key]['Id'] = $val['id'];
            $data[$key]['title'] = $val['title'];
            $data[$key]['name'] = $val['name'];
        }

        exportExcel('功能'.'-'.$this->user['userid'],'功能统计', $xlsCell, $data);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统节点参数数据', 4);
    }

    /**
     * 导出角色
     */
    public function expRole()
    {
        $list = M('AuthGroup')->order('Id desc')->select();
        $xlsCell = [
            ['Id','编号'],['title','分组名称']
        ];
        $data = [];

        foreach($list as $key=>$val){
            $data[$key]['Id'] = $val['id'];
            $data[$key]['title'] = $val['title'];
        }

        exportExcel('角色'.'-'.$this->user['userid'],'角色统计', $xlsCell, $data);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统角色参数数据', 4);
    }
}