<?php
namespace KWS\Controller;

class AjaxController extends BaseController
{
    /**
     * 获取门店列表
     */
    public function getStores()
    {
        $get = I('get.');
        // 通过品牌获取门店
        if (isset($get['brand'])) {
            $brands = explode(",", $get['brand']);
            $map['BrandId'] = ['in', $brands];
        }

        // 通过部门获取
        if (isset($get['department'])){
            $stores = M('Department')->where(['DepartId'=>$get['department']])->getField('Stores');
            // echo M()->_sql();
            $map['StoreId'] = ['in', $stores];
        }

        // 通过k获取
        if(isset($get['ks'])){
            $ks = explode(",", $get['ks']);
            $map['DepartId'] = ['in', $ks];
        }

       //  print_r($map);
        $store = M('store')->where($map)->order('BrandId')->getField('StoreId,StoreName,BrandId,DepartId,Business');
        $this->assign('store', $store);

        layout(false);
        $this->display();
    }

    public function getStoresByKid()
    {
        $get = I("get.");
        // 通过k获取
        if(isset($get['ks'])){
            $ks = explode(",", $get['ks']);
            $map['DepartId'] = ['in', $ks];
        }

        $store = M('store')->where($map)->order('BrandId')->getField('StoreId,StoreName,BrandId,DepartId,Business');
        $storeSortable = [];
        foreach($store as $key=>$val) {
            $storeSortable[$val['business']][] = $val;
        }
        $this->assign('store', $storeSortable);

        layout(false);
        $this->display();
    }

    /**
     * 获取部门列表
     */
    public function getDepartments()
    {
        $pid = I('pid');
        $Department = D('Department');
        $map['Role'] = ['in',['seller','seller-manage']];
        $map['dpath'] = ['like', "0-1-{$pid}-%"];
        $list = $Department->where($map)->order('OrderNo,dpath')->select();
        $this->assign('list', $list);
        layout(false);
        $this->display();
    }

    /**
     * 获取部门列表 根据角色关联部门
     * 时间 2016-10-10
     *
     */
    public function getDeparts()
    {
        $Role = I('Role');
        $Department = D('Department');

        $map['Role'] = $Role;
        $list = $Department->where($map)->order('OrderNo,dpath')->select();
        $this->assign('parents',$list);

        layout(false);
        $this->display();
    }

    /**
     * 组长指定销售
     */
    public function getSellers()
    {
        $storeId = I('storeId');
        $kid = D('User')->getDepartId($this->user['departid']);
    }

    public function updateOrder()
    {
        $result = M('order')->save(I("get."));

        if($result) {
            $arr = [
                'code' => '200',
                'msg' => '保存成功'
            ];
        } else {
            $arr = [
                'code' => '500',
                'msg' => '更新失败'
            ];
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 根据门店选择对应的门店
     */
    public function business()
    {
        $StoreId = I('StoreId');
        $store = M('store')->find($StoreId);

        if($store['business'] == 'birth') {
            layout(false);
            $this->display('AjaxForm/birth');
        }
    }

    public function getSellerDetail()
    {
        $sells = D('Department')->getSellDepartment($this->user['kid']);
        $this->assign('sells',$sells);
        $ksellers = D('User')->getUserOfKid($this->user['kid'],'seller');
        $this->assign('ksellers',$ksellers);
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);
        $sellerId = I('seller');
        $this->assign('seller',$sellerId);
        $departId = D('User')->getUser($sellerId,'departid');
        $kid = $this->user['kid'];
        $this->assign('departId',$departId);
        $this->assign('kid',$kid);

        layout(false);
        $this->display();
    }

    public function getDepartmentStores()
    {
        $departStores = D('Department')->getDepartmentStores();
        $this->assign(store,$departStores);
        //print_r($departStores);//grant all privileges on *.* to root@192.168.10.588 identified by '123456'
        layout(false);
        $this->display();
    }

    /**
     *
     */
    public function getKdepartUser()
    {
        $post = I("post.");

        // 获取当前门店的所有销售部门
        isset($post['StoreId']) && $store = M("Store")->find($post['StoreId']);
        if(!empty($store)) {
            $this->assign('d', $store);
            // 已选择的客服部门
            $this->assign('selectedDepartments', explode(',', $store['sellids']));
            // 已选择的客服
            $this->assign('selectedSales', explode(',', $store['users']));
        }

        // 负责该门店的所有销售部门
        if($post['pid'] < 1) {
            $this->ajaxReturn(['code'=>'400', 'msg'=>'请选择大区']);
        }
        $departModel = D('Department');
        $department = M('department')->find($post['pid']);
        $departments = $departModel->getAllDepartment($department);
        $tree = $departModel->getTree($departments,$department);
        // print_r($tree);
        //exit;
        //$departments = $departModel->getFunDepartment($post['pid']);
        $this->assign('departments', $tree);

        layout(false);
        $this->display();
    }

    public function getDepartmentsOfKid()
    {
        $pid = I('pid');
        $Department = D('Department');
        $map['Role'] = ['in',['seller','seller-manage']];
        $map['dpath'] = ['like', "0-1-{$pid}-%"];
        $list = $Department->where($map)->order('OrderNo,dpath')->select();
        $this->assign('list', $list);
        layout(false);
        $this->display();
    }
}