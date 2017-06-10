<?php
namespace KWS\Controller;

/**
 * Class CustomerController
 * 客户基础信息管理
 * @package KWS\Controller
 */
class AssignController extends BaseController
{
    private $where = [];

    public function _initialize()
    {
        parent::_initialize();

        if($this->user['roleid'] == '6') {
            // 大区总裁
            $this->where['Kid'] =  $this->user['kid'];
        } else if (in_array($this->user['roleid'], [5, 4, 3])) {
            // 客服经理，客服主管，客服组长
            $tree = D('Department')->getTree($this->departments, $this->department);
            $pids = array_keys($tree);
            if(!empty($pids)) {
                $this->where['DepartId'] = ['in', $pids];
            } else {
                $this->where['DepartId'] = $this->user['departid'];
            }
            /**
            $this->where['salseId'] = $this->user['userid'];
            $this->where['_logic'] = 'or';
             */
        } else if ($this->user['roleid'] == '1') {

            // 客服组员
            $this->where['salseId'] = $this->user['userid'];
        }
        $this->where['Del'] = 0;
    }
    
    /**
     * 获取检索条件
     */
    /**
     * 获取检索条件
     */
    private function _search()
    {
        $map = [];
        $get = I('get.');

        $get['StoreId'] > 0 && $map['StoreId'] = ['in',$get['StoreId']];
        $get['Opeartor'] > 0 && $map['Opeartor'] = ['in',$get['Opeartor']];
        $get['salseId'] > 0 && $map['salseId'] = ['in',$get['salseId']];
        $get['SourceFrom'] > 0 && $map['SourceFrom'] = ['in',$get['SourceFrom']];
        $get['IsOrder'] > -1 && $map['IsOrder'] = $get['IsOrder'];
        $get['Status'] > -1 && $map['Status'] = ['in',$get['Status']];
        if ($get['CustType'] != '-1' && !empty($get['Customer'])) {
            $map[$get['CustType']] = $get['Customer'];
        }

        if(empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] = date('Y-m-d', 0);//date('Y-m-d',strtotime('-1day')).' 21:00:00';
        } else {
            $get['StartInsertTime'] = date('Y-m-d H:i', strtotime('-1 day 21:00', strtotime($get['StartInsertTime'])));
        }

        if(empty($get['EndInsertTime'])) {
            $get['EndInsertTime'] = date('Y-m-d H:i', strtotime('today 21:00'));
        } else {
            $get['EndInsertTime'] = date('Y-m-d H:i', strtotime('21:00', strtotime($get['EndInsertTime'])));
        }
        // $map['Del'] = 0;
        $map['InsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];
        if(!empty($get['CustName'])) {
            $where['CustName'] = ['like', $get['CustName']];
            $where['Mobile'] = ['like', $get['CustName'].'%'];
            $where['WeiboName'] = ['like', $get['CustName'].'%'];
            $where['WeChat'] = ['like', $get['CustName'].'%'];
            $where['QQ'] = ['like', $get['CustName'].'%'];
            $where['_logic'] = 'OR';
        }

        !empty($where) && $map['_complex'] = $where;
        return $map;
    }

    /**
     * 客资列表
     */
    public function menuAssign()
    {
        $map = [];

        if($_GET['sf']) {
            $map = $this->_search();
            $map = array_merge($this->where, $map);
        } else {
            $map = $this->where;
        }
        $options = [
            'map' => $map,
            'order' => 'InsertTime desc'
        ];
        $list = $this->page('customer', $options);

        $assign = M('assign');
        foreach($list as $k=>$v){
            $assigns = $assign->where(['CustId'=>$v['custid']])->find();
            $list[$k]['assignid'] = $assigns['assignid'];
        }
        $this->assign('list',$list);
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);
        $this->display();
    }

    public function deAssign(){
        $CustId = I('CustId');
        $assign = M('Assign')->where(['CustId'=>$CustId])->find();
        if(empty($assign)){
            $assign['custid'] = $CustId;
        }
        $this->assign('d',$assign);

        $department = D('Department')->getDepartment($this->user['departid']);
        $dpath = explode('-', $department['dpath']);
        $kid = $dpath[2];
        $map['Kid'] = $kid;
        $map['RoleId'] = 1;
        $sellers = M('user')->field('UserId,RealName')->where($map)->select();
        $this->assign('sellers',$sellers);
        layout('Layout/win');
        $this->display();
    }

    public function doEditDeAssign(){
        $custId = I('CustId');
        $data = I('post.');
        $DepartId = D('User')->getUser($data['NowUser'],'departid');
        
        $data['Status'] = 1;
        $DepartId && $data['DepartId'] = $DepartId;
        $assignModel = M('assign');
        $custId && $IsHaveAssign = $assignModel->where(['CustId'=>$custId])->find();
        if(!empty($IsHaveAssign)){
            $res = $assignModel->where(['CustId'=>$custId])->save($data);
        }else{
            $res =$assignModel->add($data);
            //echo $assignModel->_sql();
        }

        if($res){
            $data['NowUser'] && $d['salseId'] = $data['NowUser'];
            $DepartId && $d['DepartId'] = $DepartId;
            M('customer')->where(['CustId'=>$custId])->save($d);

            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '重新分配了客户' .$custId , 3);
            $arr = [
                'code' => '200',
                'msg' => '重新分配成功',
                'layer' => 'yes'
            ];
        }else{
            $arr = [
                'code' => '400',
                'msg' => '重新分配失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    //c重新分配

    public function doEditDimission()
    {

        $seller = I('seller');
        $custids = I('ids');
        $custs = explode(',', $custids);

        $customerModel = M('customer');
        $assignModel = M('assign');
        $d['salseId'] = $seller;
        foreach ($custs as $k => $v) {
            $map['CustId'] = $v;
            $this->user['kid']&& $data['Kid'] = $this->user['kid'];
            $data['salseId'] = $seller;
            $data['DepartId'] = D('User')->getUser($seller, 'departid');
            $res = $customerModel->where($map)->save($data);
            unset($data);
            $IsHaveAssign = $assignModel->where($map)->find();
            //分配表
            if($IsHaveAssign){
                $assign = [
                    'NowUser' => $seller,
                    'DepartId' =>  D('User')->getUser($seller, 'departid'),
                ];
                $res1 = $assignModel->where($map)->save($assign);
                unset($assign);
            }

            operateLog($this->user['userid'], $this->user['realname'], '重新分配客咨给客服'.$seller, 3);
        }

        if ($res) {
            $arr = [
                'code' => '200',
                'msg' => '重新分配成功',
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '重新分配失败',
            ];
        }
        $this->ajaxReturn($arr);

    }

}