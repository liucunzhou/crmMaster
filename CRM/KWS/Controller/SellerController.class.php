<?php
namespace KWS\Controller;

/**
 * Class SellerController
 * 客服销售人员相关功能
 * @package KWS\Controller
 */
class SellerController extends BaseController
{
    private $where = [];

    public function _initialize()
    {
        parent::_initialize();

        if (in_array($this->user['roleid'], [6, 7])) {
            // 大区总裁
            $this->where['Kid'] = $this->user['kid'];
        } else if (in_array($this->user['roleid'], [5, 4, 3])) {
            // 客服经理，客服主管，客服组长
            $tree = D('Department')->getTree($this->departments, $this->department);
            $pids = array_keys($tree);
            if (!empty($pids)) {
                $this->where['DepartId'] = ['in', $pids];
            } else {
                $this->where['DepartId'] = $this->user['departid'];
            }
            /**
             * $this->where['salseId'] = $this->user['userid'];
             * $this->where['_logic'] = 'or';
             */
        } else if ($this->user['roleid'] == '1') {

            // 客服组员
            $this->where['salseId'] = $this->user['userid'];
        }

        $this->where['Del'] = 0;
    }

    /**
     * 所有客户列表
     */
    public function index()
    {
        $map = [];
        /**
         * $_GET['sf'] && $map = $this->_search();
         * $map = array_merge($this->where, $map);
         **/
        if ($_GET['sf']) {
            $map = $this->_search();
            /**
             * if(isset($this->where['_logic'])) {
             * $map['_complex'] = $this->where;
             * } else {
             * $map = array_merge($this->where, $map);
             * }
             */

            $map = array_merge($this->where, $map);
        } else {
            $map = $this->where;
        }
        $start = time();
        $fields = 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Remark,LastVisitTime,IsOrder,Keywords,IsWashing';
        $options = [
            'map' => $map,
            'order' => 'InsertTime desc',
            'field' => $fields
        ];
        $this->page('customer', $options);
        // $this->assign('log', time() - $start);

        // 回去推广列表
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);

        $ksellers1 = D('Department')->getKSellers($this->user['kid']);

        $this->assign('ksellers1', $ksellers1);
        $this->display();
    }

    /**
     * 获取检索条件
     */
    private function _search()
    {
        $map = [];
        $get = I('get.');
        $get['CustId'] > 0 && $map['CustId'] = (int)$get['CustId'];
        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        !empty($get['Status']) && $map['Status'] = ['in', $get['Status']];
        !empty($get['Opeartor']) && $map['Opeartor'] = ['in', $get['Opeartor']];
        !empty($get['salseId']) && $map['salseId'] = ['in', $get['salseId']];
        !empty($get['SourceFrom']) && $map['SourceFrom'] = ['in', $get['SourceFrom']];
        $get['IsOrder'] > -1 && $map['IsOrder'] = $get['IsOrder'];
        if ($get['CustType'] != '-1' && !empty($get['Customer'])) {
            $map[$get['CustType']] = $get['Customer'];
        }

        if (empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] = date('Y-m-d', 0);//date('Y-m-d',strtotime('-1day')).' 21:00:00';
        } else {
            //$get['StartInsertTime'] = date('Y-m-d H:i', strtotime('-1 day 21:00', strtotime($get['StartInsertTime'])));
            $get['StartInsertTime'] = date('Y-m-d 00:00:00', strtotime($get['StartInsertTime']));
        }

        if (empty($get['EndInsertTime'])) {
            $get['EndInsertTime'] = date('Y-m-d 23:59:59');
        } else {
            $get['EndInsertTime'] = date('Y-m-d 23:59:59', strtotime($get['EndInsertTime']));
        }
        // $map['Del'] = 0;
        $map['InsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];
        $get['CustName'] = trim($get['CustName']);
        if (!empty($get['CustName'])) {
            $where['CustName'] = ['like', $get['CustName']];
            $where['Mobile'] = ['like', '%' . $get['CustName'] . '%'];
            $where['WeiboName'] = ['like', $get['CustName'] . '%'];
            $where['WeChat'] = ['like', $get['CustName'] . '%'];
            $where['QQ'] = ['like', $get['CustName'] . '%'];
            $where['Wang'] = ['like', $get['CustName'] . '%'];
            $where['Keywords'] = ['like', $get['CustName'] . '%'];
            $where['_logic'] = 'OR';
        }
        !empty($where) && $map['_complex'] = $where;
        return $map;
    }

    /**
     * 销售客户信息
     * 销售添加的手机号、微信号、qq号、微博号
     * 销售客服是不能修改的
     */
    public function addCustomer()
    {
        //生成新加客户编号 时间+邀约手UId+最大订单号
        $user = $this->user;
        $n = M('customer')->count() + 1;
        $custNo = date("Ymd", time()) . $user['userid'] . $n;

        $this->assign('custNo', $custNo);

        $kid = D('User')->getDepartId($this->user['departid']);
        $this->assign('kid', $user['kid']);
        $users = D('User')->getUserOfDepartId($this->user['departid']);

        $this->assign('users', $users);
        $stores = M('store')->field('StoreId,StoreName')->select();
        $this->assign('stores', $stores);

        $sources = M('source')->field('sourceId,sourceName')->select();
        $this->assign('sources', $sources);
        $this->display();
    }

    /**
     * 编辑客户信息
     */
    public function editCust()
    {
        $id = I('id');
        $d = M('customer')->where(['CustId' => $id])->find();

        // 获取当前客户咨询的门店信息
        $CurrentStore = M('Store')->field('BrandId,StoreName,Business')->find($d['storeid']);
        $this->assign('CurrentStore', $CurrentStore);

        // 获取对应业务的信息
        $data = $this->_getBusinessData($d['custid'], $CurrentStore['business']);
        !empty($data) && $d = array_merge($d, $data);

        // 合并基础数据
        $this->assign('d', $d);

        // 获取化妆团队、摄影团队
        $photoTeam = M('photoTeam')->where(['TypeId' => 0])->select();
        $makeupTeam = M('photoTeam')->where(['TypeId' => 1])->select();
        $this->assign('photoTeam', $photoTeam);
        $this->assign('makeupTeam', $makeupTeam);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 获取业务信息
     * @param $CustId
     * @param $business
     * @return array|mixed
     */
    private function _getBusinessData($CustId, $business)
    {
        $data = [];
        if ($business == 'photo') {
            // 婚纱摄影业务
            $data = M('CustomerPhoto')->find($CustId);
        } elseif ($business == 'hall') {
            // 婚礼堂
            $data = M('CustomerHall')->find($CustId);
        } elseif ($business == 'baby') {
            // 宝宝摄影
            $data = M('CustomerBaby')->find($CustId);
        } elseif ($business == 'wedding') {
            // 婚纱礼服
            $data = M('CustomerWedding')->find($CustId);
        } elseif ($business == 'dress') {
            // 男士西服
            $data = M('CustomerDress')->find($CustId);
        } elseif ($business == 'birth') {
            // 月子会所
            $data = M('CustomerBirth')->find($CustId);
        }

        return $data;
    }

    /**
     * 保存客户信息
     */
    public function doEditCust()
    {
        // 更新客户信息
        $CustomerModel = D('Customer');
        $valid = $CustomerModel->create();
        if (!$valid) {
            $error = $CustomerModel->getError();
            $keys = array_keys($error);
            $messages = array_values($error);
            $this->ajaxReturn([
                'code' => '400',
                'id' => $keys[0],
                'msg' => $messages[0]
            ]);
        }
        $res1 = $CustomerModel->save();

        $id = I('CustId');
        // 更新客户业务信息
        $ModelName = '';
        $business = I('Business');
        if ($business == 'photo') {
            $ModelName = 'CustomerPhoto';
        } elseif ($business == 'baby') {
            $ModelName = 'CustomerBaby';
        } elseif ($business == 'wedding') {
            $ModelName = 'CustomerWedding';
        } elseif ($business == 'dress') {
            $ModelName = 'CustomerDress';
        } elseif ($business == 'birth') {
            $ModelName = 'CustomerBirth';
        }

        $gotoStore = I('GotoStore');
        if ($gotoStore) {
            $id = I('CustId');
            $model = D('GotoStore');
            $into = $model->where(['CustId' => $id])->find();
            $model->create();
            if (!empty($into)) {
                $model->where(['CustId' => $id])->save();
                //echo $model->_sql();
            } else {
                $model->add();
            }
        }
        if (!empty($ModelName)) {
            $Business = M($ModelName)->find($id);
            $Model = D($ModelName);
            $Model->create();
            if (empty($Business)) {
                $res2 = $Model->add();
            } else {
                $res2 = $Model->save();
            }
        }

        // 更新分配表
        $res3 = M('assign')->where(['CustId' => $id])->save(['Status' => 1]);

        // 操作记录
        operateLog($this->user['userid'], $this->user['realname'], '修改了客户ID为' . $id . '的信息', 3);
        if ($res1) {
            $arr = [
                'code' => '200',
                'msg' => '客户信息保存成功',
                'layer' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '客户信息保存失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    public function doAddCustomer()
    {
        $CustomerModel = D('Customer');
        $CustomerModel->startTrans();
        $valid = $CustomerModel->create();
        if (!$valid) {
            $error = $CustomerModel->getError();
            $keys = array_keys($error);
            $messages = array_values($error);
            $this->ajaxReturn([
                'code' => '400',
                'id' => $keys[0],
                'msg' => $messages[0]
            ]);
        }

        $kid = D('User')->getDepartId($this->user['departid']);
        $CustomerModel->Kid = $kid;
        $data = I('post.');
        $CustomerModel->DepartId = D('User')->getUser($_POST['salseId'], 'departid');
        $CustomerModel->Opeartor = $this->user['userid'];
        $CustomerModel->PdepartId = $this->user['departid'];
        $CustomerModel->InsertTime = date("Y-m-d H:i:s");
        $CustomerModel->Kid = $this->user['kid'];
        $res = $CustomerModel->add();

        //操作记录
        operateLog($this->user['userid'], $this->user['realname'], '添加了客户信息' . I('RealName'), 1);
        if ($res) {
            $assign = [
                'CustId' => $res,
                'CustNo' => $data['CustNo'],
                'InitUser' => $data['salseId'],
                'NowUser' => $data['salseId'],
                'InsertTime' => date('Y-m-d H:i:s'),
                'UserCount' => 1,
                'DepartId' => D('User')->getUser($_POST['salseId'], 'departid'),
                'Invitor' => $this->user['userid'],
                'InvitDepart' => D('User')->getUser($this->user['userid'], 'departid'),
                'AppointType' => $data['AppointType'],
            ];
            M('assign')->add($assign);
            $CustomerModel->commit();

            $arr = [
                'code' => '200',
                'msg' => '客户信息保存成功',
                'layer' => 'yes'
            ];
        } else {
            $CustomerModel->rollback();
            $arr = [
                'code' => '400',
                'msg' => '客户信息保存失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 回访管理(个人)
     */
    public function visited()
    {
        $custid = I('id');
        $list = M('visit')->where(['CustId' => $custid])->select();
        $model = D('Customer');
        foreach ($list as $k => $v) {
            $customer = $model->where(['CustId' => $v['custid']])->find();
            $list[$k]['custname'] = $customer['custname'];
            $list[$k]['mobile'] = $customer['mobile'];
            $list[$k]['storeid'] = $customer['storeid'];
            $list[$k]['isorder'] = $customer['isorder'];
            $list[$k]['lastvisittime'] = $customer['lastvisittime'];
        }
        $this->assign('list', $list);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 删除回访记录(m某个)
     */
    public function delVisited()
    {
        $result = M('Visit')->where(['VisitId' => $_GET['id']])->delete();
        if ($result) {
            $data['alert'] = '删除回访记录成功';
            $data['reload'] = 'yes';
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '在回访管理删除了客户信息' . $_GET['id'], 2);
        } else {
            $data['alert'] = '删除回访记录失败';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 回访管理(所有人)
     */
    public function backList()
    {
        $custName = I('custname');
        $custId = I('custid');
        $mobile = I('mobile');
        $status = I('status');
        $VisitTimeStart = I('VisitTimeStart');
        $VisitTimeEnd = I('VisitTimeEnd');
        $storeId = I('storeId');
        !empty($_GET['custname']) && $map['CustName'] = I($custName);
        $custId && $map['CustId'] = trim($custId);
        $mobile && $map['Mobile'] = trim($mobile);
        $status && $map['Status'] = trim($status);
        $storeId && $map['StoreId'] = trim($storeId);


        // 只能看自己k的数据
        $this->user['kid'] && $map['Kid'] = $this->user['kid'];
        $departId = $this->user['departid'];
        $role = D('Department')->getDepartment($departId, 'role');
        if ($role == 'seller') {
            if ($this->user['roleid'] == 1) {
                $map['salseId'] = $this->user['userid'];
            } else {
                $departmentIds = D('Department')->getSonDepartIds($departId);
                $map['DepartId'] = ['in', $departmentIds];
            }
        } elseif ($role == 'seller-manage') {
            $departmentIds = D('Department')->getSonDepartIds($departId);
            $map['DepartId'] = array('in', $departmentIds);
        }
        $options = [
            'map' => $map,
            'order' => 'InsertTime desc'
        ];
        $list = $this->page('customer', $options);

        $visit = M('Visit');
        foreach ($list as $k => $v) {
            $where['CustId'] = $v['custid'];
            $arr = $visit->field('isPhoto')->where($where)->find();
            $list[$k]['isPhoto'] = $arr['isphoto'];
        }

        if ($VisitTimeStart && $VisitTimeEnd) {
            $where['InsertTime'] = array('between', array($VisitTimeStart, $VisitTimeEnd));
            $visit_list = $this->page('visit', $where);
            $customerModel = M('customer');
            foreach ($visit_list as $k => $v) {
                // $customerModel->
            }
        }
        $this->assign('list', $list);

        $this->display();
    }

    /**
     *添加回访
     */
    public function addVisit()
    {
        $id = I('id');
        $customer = M('customer')->where(['CustId' => $id])->find();
        $this->assign('customer', $customer);

        $visit = M('visit')->where(['CustId' => $customer['custid']])->select();
        $this->assign('visit', $visit);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 保存回访信息
     */
    public function doAddVisit()
    {
        $data = I("post.");
        $CustomerModel = M('customer');
        $VisitModel = M('Visit');
        $data['LastVisitTime'] = date("Y-m-d H:i:s");
        $data['Operator'] = $this->user['userid'];
        //$data['Status']&&$data['Status'] = $data['Status'];

        $res1 = $CustomerModel->where(['CustId' => $data['CustId']])->save($data);
        $data['InsertTime'] = date('Y-m-d H:i:s');
        $res2 = $VisitModel->add($data);
        if ($data['yuezi'] == 1) {
            unset($data['Status']);
            M('customerBirth')->where(['CustId' => $data['CustId']])->save($data);
        }
        if ($res1 || $res2) {
            //如果assign里面status为0要改为1
            $assign = M('assign')->field('Status')->where(['CustId' => $data['CustId']])->find();
            if ($assign['status'] == 0) {
                M('assign')->where(['CustId' => $data['CustId']])->save(['Status' => 1]);
            }
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了客户回访信息' . I('CustId'), 1);
            $arr = [
                'code' => '200',
                'msg' => '提交成功',
                'layer' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '500',
                'msg' => '保存失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 进店排期
     */
    public function custPlan()
    {
        $options = [
            'order' => 'InsertTime desc'
        ];
        $list = $this->page('customer', $options);

        $this->assign('list', $list);
        $this->display();
    }

    /**
     *进店改期
     */
    public function custChgPlan()
    {
        $options = [
            'order' => 'InsertTime desc'
        ];
        $list = $this->page('customer', $options);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 删除cust
     */
    public function delCust()
    {
        $d['Del'] = 1;
        $rid = M('customer')->where(['CustId' => $_GET['id']])->save($d);
       /* $Customer = M('Customer');
        $result = $Customer->field('QQ,Mobile,WeChat,WeiboName')->where(['CustId' => $_GET['id']])->find();
        foreach($result as $k=>$v){
            if($v){
                if($k == 'qq'){
                    $map['QQ'] = $v;
                }elseif($k == 'mobile'){
                    $map['Mobile'] = $v;
                }elseif($k == 'wechat'){
                    $map['WeChat'] = $v;
                }elseif($k == 'weiboname'){
                    $map['WeiboName'] = $v;
                }
                $n = $Customer->where($map)->count();
                unset($map);
                if($n>=2){
                    $rid = $Customer->where(['CustId' => $_GET['id']])->save($d);
                }
            }

        }*/
        if ($rid) {
            $data['alert'] = '删除成功';
            $data['redirect'] = U('Seller/index');
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了客户信息Id' . $_GET['id'], 2);
        } else {
            $data['alert'] = '删除失败';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 改期
     */
    public function chgPlan()
    {
        layout('Layout/win');
        $this->display();
    }


    /**
     * 接受邀约的分配
     */
    public function accpet()
    {
        // $assignId = I('AssignId');
        // $map['Assignid'] = $assignId;
        // $map['CustId'] = I('CustId');
        $map['NowUser'] = $this->user['userid'];
        $res = M('assign')->where($map)->save(['Status' => 1]);

        if ($res) {
            // $salse = M('assign')->field('InitUser')->where(['Assignid' => $assignId])->find();
            /**
             * $expireTime = 43200 - date("h") * 3600 - date("i") * 60 - date("s");
             * $acceptNum = (int)S('Count-' . $salse['inituser']);
             * S('Count-' . $salse['inituser'], $acceptNum + 1, ['expire' => $expireTime]);
             **/
            $this->ajaxReturn([
                'code' => '200',
                'msg' => '客户接收成功'
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '500',
                'msg' => '客户推送异常'
            ]);
        }
    }

    /**
     * 添加回访
     */
    public function addBack()
    {
        $custId = I('id');
        $customer = M('Customer')->where(['CustId' => $custId])->find();
        $this->assign('d', $customer);
        $visit = M('Visit')->where(['CustId' => $custId])->select();
        $this->assign('visit', $visit);
        $causeStatus = [
            1 => '已定',
            2 => '微信/微商',
            3 => '应聘',
            4 => '18岁不到',
            5 => '重复',
            6 => '空号',
            7 => '城市错误(1次)',
            8 => '拉黑',
        ];
        $this->assign('causeStatus', $causeStatus);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 编辑回访
     */
    public function editBack()
    {
        $custId = I('id');
        $d = M('Customer')->where(['CustId' => $custId])->find();
        $this->assign('d', $d);
        layout('Layout/win');
        $this->display();
    }

    public function doEditBack()
    {
        $model = D('Customer');
        $model->create();
        $custid = I('id');
        $model->LastEditTime = date("Y-m-d H;i:s");
        $model->LastEditUser = $this->user['realname'];
        $res = $model->where(['CustId' => $custid])->save();
        if ($res) {
            $assign = M('assign')->field('Status')->where(['CustId' => $custid])->find();
            if ($assign['status'] == 0) {
                M('assign')->where(['CustId' => $custid])->save(['Status' => 1]);
            }
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '在回访管理编辑了客户信息' . I('CustId') . 'Seller/editBack', 3);
            $this->ajaxReturn([
                'code' => '200',
                'msg' => '信息保存成功',
                'layer' => 'yes'
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '500',
                'msg' => '信息保存失败'
            ]);
        }
    }

    public function doAddBack()
    {
        $data = I('post.');
        $custId = I('id');
        $data['LastVisitTime'] = date("Y-m-d H;i:s");
        $res = M('Customer')->where(['CustId' => $custId])->save($data);
        $assign['status'] = 1;
        M('assign')->where(['CustId' => $custId])->save($assign);
        $data['CustId'] = $custId;
        $data['Operator'] = $this->user['userid'];
        $data['InsertTime'] = date("Y-m-d H:i:s");
        $data['InsertTime'] = date("Y-m-d H:i:s");
        $res1 = M('Visit')->add($data);
        if ($res || $res1) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '在回访管理添加了回访记录' . I('CustId'), 1);
            $this->ajaxReturn([
                'code' => '200',
                'msg' => '信息保存成功',
                'layer' => 'yes'
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '500',
                'msg' => '信息保存失败'
            ]);
        }
    }

    /**
     * 编辑月子中心客户信息
     */
    public function editBCust()
    {
        $id = I('id');
        if ($id) {
            $res = M('customer')->where(['CustId' => $id])->find();
            $this->assign('d', $res);
        }
        $storeList = M('store')->field('StoreId,StoreName')->select();
        $this->assign('storeList', $storeList);
        layout('Layout/win');
        $this->display();
    }

    /**
     *
     * 添加月子中心客户回访
     */
    public function addBVisit()
    {
        $id = I('id');
        $customer = M('customer')->where(['CustId' => $id])->find();
        $this->assign('customer', $customer);
        $visit = M('visit')->where(['CustId' => $customer['custid']])->select();
        $this->assign('visit', $visit);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 修改离职客服列表页面
     */
    public function dimission()
    {
        $map = [];
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($map, $this->where);
        $lockSellers = M('User')->field('UserId')->where(['isLock' => 2, 'RoleId' => 1])->select();
        $arrSeller = array_column($lockSellers, 'userid');
        $map['salseId'] = ['in', $arrSeller];
        //权限限制每个人用户只能看自己所在k的用户数据
        $options = [
            'map' => $map,
            'field' => 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Remark,LastVisitTime,IsOrder'
        ];
        $this->page('customer', $options);
        // 回去推广列表
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);
        $this->display();
    }

    /**
     * 修改离职客服的客户
     */
    public function changeSeller()
    {
        $id = I('id');
        $customer = M('customer')->where(['CustId' => $id])->find();
        $this->assign('d', $customer);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 修改离职客服的客户
     */
    public function doChangeSeller()
    {
        $custId = I('CustId');
        $salseId = I('salseId');
        $salseId && $data['salseId'] = $salseId;
        $salseId && $data['DepartId'] = D('User')->getuser($salseId, 'departid');
        $data['ModifyTime'] = date("Y-m-d H:i:s");
        $custId && $res = M('customer')->where(['CustId' => $custId])->save($data);
        if ($res) {
            $salseId && $d['NowUser'] = I('salseId');
            $rs = M('assign')->where(['CustId' => $custId])->save($d);
            /* $order = M('order')->where(['CustId'=>$custId])->find();
             if(!empty($order)){
                // M('order')->where(['CustId'=>$custId])->save();
             }*/
            if ($rs) {
                //操作记录
                operateLog($this->user['userid'], $this->user['realname'], '修改离职客服' . D('User')->getUser($data['salseId'], 'realname') . '的客户' . $custId, 1);
                $arr = [
                    'code' => '200',
                    'msg' => '修改成功',
                    'layer' => 'yes'
                ];
            } else {
                $arr = [
                    'code' => '400',
                    'msg' => '修改失败'
                ];
            }
        } else {
            $arr = [
                'code' => '400',
                'msg' => '修改失败'
            ];
        }

        $this->ajaxReturn($arr);
    }

    public function intoStore()
    {
        $map['CustId'] = $_GET['id'];
        $d = M('GotoStore')->where(['CustId' => $_GET['id']])->find();
        $this->assign('d', $d);

        layout('Layout/win');
        $this->display();
    }

    public function doEditIntoStore()
    {
        $id = I('CustId');
        $model = D('GotoStore');
        $id && $into = $model->where(['CustId' => $id])->find();
        $model->create();

        if (!empty($into)) {
            $res = $model->where(['CustId' => $id])->save();
        } else {
            $res = $model->add();
        }

        $store = M('store')->field('Business')->find($into['storeid']);
        $this->setStoreBusinessData($store['business'], $id);
        if ($res) {
            $arr = [
                'code' => '200',
                'msg' => '修改成功',
                'redirect' => U('Seller/gotoStore'),
                'layer' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '修改失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    protected function _toStoreSearch()
    {
        $get = I('get.');
        $get['GotoStore'] && $map['GotoStore'] = $get['GotoStore'];
        $get['IntoType'] && $map['IntoType'] = $get['IntoType'];
        !empty($get['SourceFrom']) && $map['SourceFrom'] = ['in', $get['IntoType']];
        !empty($get['Opeartor']) && $map['Opeartor'] = ['in', $get['Opeartor']];
        !empty($get['salseId']) && $map['salseId'] = ['in', $get['salseId']];
        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        if ($get['StartPresetTime'] && $get['EndPresetTime']) {
            $map['PresetTime'] = ['between', [$get['StartPresetTime'] . ' 00:00:00', $get['EndPresetTime'] . ' 23:59:59']];
        }
        if (empty($get['StartInsertTime'])) {
            // $get['StartInsertTime'] = date('Y-m-d', 0);//date('Y-m-d',strtotime('-1day')).' 21:00:00';
        } else {
            $get['StartInsertTime'] = date('Y-m-d 00:00:00', strtotime($get['StartInsertTime']));
        }

        if (empty($get['EndInsertTime'])) {
            // $get['EndInsertTime'] = date('Y-m-d 23:59:59');
        } else {
            $get['EndInsertTime'] = date('Y-m-d 23:59:59', strtotime($get['EndInsertTime']));
        }
        // $map['Del'] = 0;
        if ($get['StartInsertTime'] && $get['EndInsertTime']) {
            $map['CustInsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];
        }

        $get['CustName'] = trim($get['CustName']);
        if (!empty($get['CustName'])) {
            $_where['CustName'] = ['like', $get['CustName']];
            $_where['Mobile'] = ['like', '%' . $get['CustName'] . '%'];
            $_where['WeiboName'] = ['like', $get['CustName'] . '%'];
            $_where['WeChat'] = ['like', $get['CustName'] . '%'];
            $_where['QQ'] = ['like', $get['CustName'] . '%'];
            $_where['_logic'] = 'OR';
        }
        !empty($_where) && $map['_complex'] = $_where;
        return $map;
    }

    /**
     * 进店管理
     */
    public function gotoStore()
    {
        $map = [];
        $where = $this->_toStoreSearch();
        !empty($where) && $map = $where;
        !empty($this->where) && $map = array_merge($this->where, $map);
        $options = [
            'map' => $map,
            'order' => 'PresetTime desc',
            //'field' => $fields
        ];
        $list = $this->page('gotoStore', $options);
        $model = M('customer');
        $fields = 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Keywords,IsWashing';
        foreach ($list as $k => $v) {
            $customer = $model->field($fields)->where(['CustId' => $v['custid']])->find();
            $list[$k]['customer'] = $customer;
        }
        $this->assign('list', $list);
        $ksellers1 = D('Department')->getKSellers($this->user['kid']);
        $this->assign('ksellers1', $ksellers1);
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);
        $this->display();
    }

    /**
     * 编辑进店
     */
    public function editGotoStore()
    {

        $custId = I('id');
        $d = M('GotoStore')->where(['CustId' => $custId])->find();
        $this->assign('d', $d);
        layout('Layout/win');
        $this->display();
    }

    public function doEditGotoStore()
    {
        $id = I('CustId');
        $model = D('GotoStore');

        $into = $model->where(['CustId' => $id])->find();
        $model->create();
        if (!empty($into)) {
            $res = $model->where(['CustId' => $id])->save();
        } else {
            $res = $model->add();
        }

        $store = M('store')->field('Business')->find($into['storeid']);
        $this->setStoreBusinessData($store['business'], $id);
        if ($res) {
            $arr = [
                'code' => '200',
                'msg' => '修改成功',
                'layer' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '修改失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    /**delInto
     *
     */
    public function delInto()
    {
        $id = I('id');
        $gotoStore = M('GotoStore');
        $res = $gotoStore->where(['CustId' => $id])->delete();
        $d['GotoStore'] = 0;
        $d['PresetTime'] = 0;
        $d['IntoTime'] = 0;
        M('customer')->where(['CustId' => $id])->save($d);
        if ($res) {
            $arr = [
                'code' => '200',
                'msg' => '删除成功',
                'reload' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '删除失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    /**
     *
     */
    public function addOrder()
    {
        $map1['RoleId'] = ['in', [1, 3, 4, 5]];
        if ($this->user['roleid'] == 1) {
            $map1['DepartId'] = $this->user['departid'];
        } else {
            $map1['DepartId'] = $this->user['userid'];
        }

        $sellers = M('user')->field('UserId,RealName')->where($map1)->select();
        $this->assign('sellers', $sellers);

        $customerModel = M('customer');
        if (!empty($_GET['search'])) {
            $get = I('get.');
            $get = array_filter($get);
            !empty($get['CustNo']) && $map['CustNo'] = $get['CustNo'];
            $map['Del'] = 0;
            $where['Mobile'] = ['like', '%' . $get['Keyword'] . '%'];
            $where['QQ'] = ['like', '%' . $get['Keyword'] . '%'];
            $where['WeChat'] = ['like', '%' . $get['Keyword'] . '%'];
            $where['WeiboName'] = ['like', '%' . $get['Keyword'] . '%'];
            $where['_logic'] = 'OR';
            $map['_complex'] = $where;
            $customer = $customerModel->where($map)->select();
            //echo $customerModel->_sql();
            //exit;
            $this->assign('customer', $customer);
        } elseif ($_GET['gotoStore'] == 1) {
            $map = [];
            $map['CustId'] = $_GET['id'];
            $customer = $customerModel->where($map)->select();
            $this->assign('customer', $customer);
        }
        $sources1 = $this->getPSource();
        $this->assign('sources1', $sources1);

        $ksellers = D('User')->getUserOfKid($this->user['kid'], 'seller');
        $this->assign('ksellers', $ksellers);

        layout('Layout/win');
        $this->display();
    }

    public function expSeller()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map = [];
        if ($_GET['sf']) {
            $map = $this->_search();
            if (isset($this->where['_logic'])) {
                $map['_complex'] = $this->where;
            } else {
                $map = array_merge($this->where, $map);
            }
        } else {
            $map = $this->where;
        }

        $GotoStore = [
            1 => '已预约',
            2 => '已进店',
            3 => '已改期',
        ];

        $endId = I('endId');
        if (!$endId) {
            return false;
        } elseif ($endId == 1) {
            S('expNum-seller' . $this->user['userid'], 0);
        }
        $listNum = I('listNum');
        $customerModel = M('customer');
        $count = $customerModel->where($map)->order('CustId')->count();
        $listNum = $listNum ? $listNum : 1000;
        $map['CustId'] = ['gt', $endId];
        $customers = $customerModel->where($map)->order('CustId')->limit($listNum)->select();
        $data = [];
        $data1 = $customers;

        $num = S('expNum-seller' . $this->user['userid']);
        $num = $num + count($customers) + I('num');
        if ($count > 60000) {
            $this->ajaxReturn([
                'num' => $num,
                'error' => '导出量不能大于60000',
                'count' => $count,
            ]);
        }
        S('expNum-seller' . $this->user['userid'], $num);
        $lastData = array_pop($data1);

        $filename = S('expFile-seller' . $this->user['userid']);
        if (!$filename) {
            $filename = './expfile/customer-seller' . $this->user['useraccount'] . '-' . date('YmdHis') . '.csv';
            S('expFile-seller' . $this->user['userid'], $filename);
            $FieldName = ['城市品牌', '客户姓名', '咨询门店', '手机号', '微信号', '微博昵称', 'QQ号', 'QQ验证', '旺旺号', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '客服部门', '接单销售', '是否进店', '备注', '回访状态和时间'];
            // $FieldName = ['客户ID', '城市品牌', '咨询门店', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '接单销售','是否进店','备注','回访状态和时间'];
            foreach ($FieldName as $key => $val) {
                $FieldName[$key] = iconv('utf-8', 'gbk', $val);
            }
        }

        if (!empty($customers)) {
            $output = fopen($filename, 'a') or die('can not open' . $filename);
            $flush = 0;
            $limit = $listNum;
            if ($num <= $listNum) {
                fputcsv($output, $FieldName);
            }

            foreach ($customers as $key => $val) {
                // $data['CustNo'] = iconv('utf-8', 'gbk', $val['custno']);
                $data['Company'] = iconv('utf-8', 'gbk', $val['company']);
                $data['CustName'] = iconv('utf-8', 'gbk', $val['custname']);
                $data['StoreId'] = iconv('utf-8', 'gbk', $this->brands[$this->stores[$val['storeid']]['brandid']]['brandname'] . $this->stores[$val['storeid']]['storename']);
                $data['Mobile'] = iconv('utf-8', 'gbk', $val['mobile']);
                $data['Wechat'] = iconv('utf-8', 'gbk', $val['wechat']);
                $data['WeiboName'] = iconv('utf-8', 'gbk', $val['weiboname']);
                $data['QQ'] = iconv('utf-8', 'gbk', $val['qq']);
                $data['QQCode'] = iconv('utf-8', 'gbk', $val['qqcode']);
                $data['Wang'] = iconv('utf-8', 'gbk', $val['wang']);
                $data['Keywords'] = iconv('utf-8', 'gbk', $val['keywords']);
                $data['AssignStatus'] = iconv('utf-8', 'gbk', D('Assign')->getFieldInfo($val['custno'], 'status'));
                $data['Status'] = iconv('utf-8', 'gbk', $this->status[$val['status']] ? $this->status[$val['status']] : '未回访');
                $data['VisitTimes'] = iconv('utf-8', 'gbk', D('Customer')->getVisitTimes($val['custno']));
                $data['IsOrder'] = iconv('utf-8', 'gbk', $val['isorder'] > 0 ? '是' : '否');
                $time = explode(' ', $val['inserttime']);
                if ($this->user['kid'] == 4) {
                    $data['InsertTime'] = $time[0];
                } else {
                    $data['InsertTime'] = $val['inserttime'];
                }
                $time = explode(' ', $val['lastvisittime']);
                $data['LastVisitTime'] = $time[0];
                $data['SourceFrom'] = iconv('utf-8', 'gbk', $this->sources[$val['sourcefrom']]['sourcename']);
                $data['Opeartor'] = iconv('utf-8', 'gbk', D('User')->getUser($val['opeartor'], 'realname'));
                $department = D('Department')->getDepartment($val['departid'], 'departname');
                $data['DepartId'] = iconv('utf-8', 'gbk', $department);
                $data['Seller'] = iconv('utf-8', 'gbk', D('User')->getUser($val['salseid'], 'realname'));
                $data['GotoStore'] = iconv('utf-8', 'gbk', $GotoStore[$val['gotostore']]);
                $data['Mark'] = iconv('utf-8', 'gbk', D('Visit')->getVisitRemark($val['custid']));
                // $data['VisitStatus'] = iconv('utf-8', 'gbk', D('Visit')->getVisitTimeAndStatus($val['custid']));
                fputcsv($output, $data);

                $flush++;
                if ($limit == $flush) {
                    ob_flush();
                    flush();
                    $flush = 0;
                }
            }

            fclose($output) or die("can not close");
            $num1 = $num;
            unset($num);
            unset($customers);
            unset($data);
            unset($data1);
            $this->ajaxReturn([
                'num' => $num1,
                'endId' => $lastData['custid'],
                'listNum' => $listNum,
                'count' => $count,

            ]);
        } else {

            //$this->outDown($customers);
            $fileExp = $filename;
            unset($filename);
            unset($num);
            unset($data);
            unset($data1);
            unset($customers);
            S('expFile-seller' . $this->user['userid'], null);
            S('expNum-seller' . $this->user['userid'], 0);
            operateLog($this->user['userid'], $this->user['realname'], '导出了时间段为' . $map['InsertTime'][1][0] . '-' . $map['InsertTime'][1][1] . '销售客咨' . $fileExp, 4);
            $this->ajaxReturn([
                'num' => 0,
                'endId' => $lastData['custid'],
                'listNum' => $listNum,
                'count' => $count,
                'expUrl' => $fileExp
            ]);

        }
    }


    public function getMessage()
    {
        // 设置时间区间
        $yestoday = date('Y-m-d H:i:s', strtotime('-1 day 21:00'));
        $today = date('Y-m-d H:i:s', strtotime('Today 23:59'));
        // 设置检索条件
        $map['NowUser'] = $this->user['userid'];
        $map['Status'] = 0;
        $map['InsertTime'] = ['between', [$yestoday, $today]];

        $data = M('Assign')->where($map)->order('InsertTime asc')->find();

        if (empty($data)) {
            $arr = [
                'code' => '200',
                'msg' => '没有可提示的信息'
            ];
        } else {
            $arr = [
                'code' => '100',
                'AssignId' => $data['assignid'],
                'CustId' => $data['custid']
            ];
        }

        $this->ajaxReturn($arr);
    }

    protected function setStoreBusinessData($business, $custId)
    {
        /*$gotoStore = I('GotoStore');
        $presetTime = I('PresetTime');
        $gotoStore&&$custData['GotoStore'] = $gotoStore;
        $presetTime&&$custData['PresetTime'] = $presetTime;
        !empty($custData)&&M('Customer')->where(['CustId'=>$custId])->save($custData);*/

        $ModelName = '';
        if ($business == 'photo') {
            $ModelName = 'CustomerPhoto';
        } elseif ($business == 'baby') {
            $ModelName = 'CustomerBaby';
        } elseif ($business == 'wedding') {
            $ModelName = 'CustomerWedding';
        } elseif ($business == 'dress') {
            $ModelName = 'CustomerDress';
        } elseif ($business == 'birth') {
            $ModelName = 'CustomerBirth';
        }

        if (!empty($ModelName)) {
            $Business = M($ModelName)->find($custId);
            $Model = D($ModelName);
            $Model->create();
            if (empty($Business)) {
                $res2 = $Model->add();
            } else {
                $res2 = $Model->save();
            }
        }
    }

    protected function getPSource()
    {
        $sources = [];
        // $sources = D('Source')->getPromoterSource();
        // $map['sourceId'] = ['in',$sources];
        $rows = M('source')->order('OrderNo')->select();
        foreach ($rows as $key => $val) {
            if ($val['sourcename']) {
                $sources[$val['platform']][] = $val;
            }
        }
        return $sources;
    }

}