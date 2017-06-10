<?php
namespace KWS\Controller;

/**
 * Class DepartmentController
 * 部门管理
 * @package KWS\Controller
 */
class DepartmentController extends BaseController
{
    public function _initialize(){
        parent::_initialize();

        // 获取所有门店信息
        $store = D('Store')->getAllStore();
        $this->assign('store', $store);

        // 获取所有来源信息
        $source = D('Source')->getAllSource();
        $this->assign('source', $source);

        // 获取所有状态
        $status = D('StatusType')->getAllStatus();
        $this->assign('status', $status);

        // 獲取所有品牌
        $brand = D("Brand")->getAllBrand();
        $this->assign('brand', $brand);
    }

    /**
     * 部门信息列表
     */
    public function index()
    {
        $departId = D('User')->getUser($this->user['userid'],'departid');
        if($departId){
            $department = D('Department')->getDepartment($departId);
            $dpath = explode('-', $department['dpath']);
            $where['dpath'] = ['like',$department['dpath'].$departId.'-%'];
            $selectDeparts = D('Department')->where($where)->select();
            $kid = $dpath[2];
            foreach($selectDeparts as $k=>$v){
                $Departments[] = $v['departid'];
            }

            //print_r($Departments);exit;
            if(!in_array($departId,$Departments)){
                array_push($dpath,$departId);
            }

            foreach($dpath as $k=>$v){
                if($v >= $departId){
                    $Departments[] = $v;
                }
            }
        }
        !empty($Departments)&& $map['DepartId'] = array('in',$Departments);
        //$map['OrderNo'] = ['gt', 0];
        $map['OrderNo'] = ['neq',4];
        $options = [
             'map' => $map,
            'order' => 'OrderNo,dpath',
            'display' => 100
        ];
        $this->page('department', $options);

        $Department = D('Department');
        $this->assign('Department', $Department);
        $this->display();
    }

    /**
     * 添加部门信息
     */
    public function addDepartment()
    {
        $Department = D('department');
        $map['OrderNo'] = ['neq',4];
        $parents = $Department->where($map)->getAllDepartment();
        $this->assign('parents', $parents);

        $this->display();
    }

    /**
     * 编辑部门信息
     */
    public function editDepartment()
    {
        /* 获取无限极分类 */
        $Department = D('department');
        $map['OrderNo'] = ['neq',4];
        $parents = $Department->where($map)->getAllDepartment();
        $this->assign('parents', $parents);

        // 获取部门基本信息
        $id = I('id');
        $data = $Department->where(['DepartId'=>$id])->find();
        $this->assign('data', $data);

        //部门所属门店
       /* $department = D('Department')->getDepartment($id);
        $dpath = explode('-', $department['dpath']);
        $kid = $dpath[2] ? $dpath[2]:$id;
        $where['dpath'] = ['like',$department['dpath'].$id.'%'];
        $where['Role'] = ['in',['manage','seller-manage','seller']];
        $deStoers = M('store')->field('StoreId,StoreName,BrandId')->where(['DepartId'=>$kid])->select();*/
        $_GET['department'] = $id;
        $deStoers = D('Department')->getDepartmentStores($_GET['department']);
        $this->assign('deStores',$deStoers);

        $this->display();
    }

    /**
     * 执行编辑、添加
     */
    public function doEditDepartment()
    {
        $Department = D('Department');
        $valid = $Department->create();
        if (!$valid) {
            $error = $Department->getError();
            $keys = array_keys($error);
            $messages = array_values($error);

            $this->ajaxReturn([
                'code' => '400',
                'id' => $keys[0],
                'msg' => $messages[0]
            ]);
        }

        $DepartId = I('DepartId');
        $dpath = $Department->dpath;
        $dpath = explode("-", $dpath);
        if ($DepartId) {
            $result = $Department->save();
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'修改了部门信息'.I('HallName'),3);
        } else {
            $result = $Department->add();
            // $DepartId = $Department->getLastInsID();
            //操作记录
            operateLog($this->user['userid'],$this->user['realname'],'添加了部门信息'.I('HallName'),1);
        }

        if(isset($dpath[2])) {
            $storeIds = I("store_id");
            /**
            $DepartmentStore = M('DepartmentStore');
            foreach ($storeIds as $key => $val) {
                $data = [
                    'k_id'  => $dpath[2],
                    'department_id' => $DepartId,
                    'store_id' => $val
                ];

                $rs = $Department->where($data)->find();
                if(!$rs) {
                    $Department->add($data);
                }
            }
            ***/
        }

        if ($result) {
            $arr = [
                'code' => '200',
                'msg' => '保存成功',
                'redirect' => $this->referer
            ];
        } else {
            $arr = [
                'code' => '500',
                'msg' => '保存失败'
            ];
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 删除门店信息
     */
    public function delDepartment()
    {
        $this->display();
    }

    /**
     * 显示门店信息详情
     */
    public function showDepartment()
    {
        $this->display();
    }

    /**
     *  导出部门
     */
    public function expDepartment()
    {
        set_time_limit(0);

        $departId = D('User')->getUser($this->user['userid'],'departid');
        if($departId){
            $department = D('Department')->getDepartment($departId);
            $dpath = explode('-', $department['dpath']);
            $where['dpath'] = ['like',$department['dpath'].$departId.'-%'];
            $selectDeparts = D('Department')->where($where)->select();
            $kid = $dpath[2];
            foreach($selectDeparts as $k=>$v){
                $Departments[] = $v['departid'];
            }
            //print_r($Departments);exit;
            if(!in_array($departId,$Departments)){
                array_push($dpath,$departId);
            }
            foreach($dpath as $k=>$v){
                if($v >= $departId){
                    $Departments[] = $v;
                }
            }
        }
        !empty($Departments)&& $map['DepartId'] = array('in',$Departments);
        $field = 'DepartId,ParentId,Role,DepartNo,DepartName,Stores,OrderNo,dpath';
        $list = M('Department')->field($field)->where($map)->order('DepartId desc')->select();
        $roles = [
            'manager' => '管理',
            'promoter-manager' => '邀约手管理',
            'seller-manager' => '销售管理',
            'seller' => '销售',
            'promoter' => '邀约手',
            'data' => '数据',
            'finance' => '财务',
        ];
        $xlsCell = [
            ['DepartId','部门编号'],['DepartName','部门名称'],  ['ParentId','上级部门'],['Role','部门性质'], ['OrderNo','排序'],
        ];
        $data = [];
        foreach($list as $key=>$val){
            $data[$key]['DepartId'] = $val['departid'];
            $data[$key]['DepartName'] = $val['departname'];
            $data[$key]['ParentId'] = D('Department')->getDepartment($val['parentid'],'departname');
            $data[$key]['Role'] = $roles[$val['role']];
            $data[$key]['OrderNo'] = $val['orderno'];
        }

        exportExcel('部门'.'-'.$this->user['userid'],'部门统计', $xlsCell, $data);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统部门数据', 4);
    }
}