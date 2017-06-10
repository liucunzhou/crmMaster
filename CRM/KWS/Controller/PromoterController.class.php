<?php
namespace KWS\Controller;
use Org\Util\String;

/**
 * Class PromoterController
 * 推广人员功能管理（邀约手）
 * @package KWS\Controller
 */
class PromoterController extends BaseController
{
    private $where = [];

    public function _initialize()
    {
        parent::_initialize();
        // print_r($_GET);
        /**
         * if ($this->user['roleid'] != '12') {
         * // 不是管理员账号
         * $this->where['Kid'] =  $this->user['kid'];
         * }
         ***/
        if (in_array($this->user['roleid'] ,[6,7,11])) {
            // 不是管理员账号
            $this->where['Kid'] = $this->user['kid'];
            $promoters = D('Department')->getPromoters();
            $this->assign('promoters', $promoters);
        } else if (in_array($this->user['roleid'],[9,10])) {
            // 推广经理，推广主管，推广组长
            $tree = D('Department')->getTree($this->departments, $this->department);
            $pids = array_keys($tree);
            if (!empty($pids)) {
                //  $pdepartment = ['in', $pids];
                $users = M('user')->field('UserId,UserAccount,RealName')->where(['DepartId' => ['in', $pids]])->select();
            } else {
                $pdepartment = $this->user['departid'];
                $users = M('user')->field('UserId,UserAccount,RealName')->where(['PdepartId' => $pdepartment])->select();
                $pids = array_push($pids,$pdepartment);
            }

            $promoters = array_column($users, 'userid');
           // if (!empty($promoters)) $this->where['Opeartor'] = ['in', $promoters];
            if (!empty($pids)) $this->where['PdepartId'] = ['in', $pids];
            $this->assign('promoters', $users);
            $this->where['Kid'] = $this->user['kid'];


        } else if ($this->user['roleid'] == '2') {

            // 客服组员
            $this->where['Opeartor'] = $this->user['userid'];
            $sourceFrom = explode(',', $this->user['sourcefrom']);
            // if(!empty($sourceFrom)) $this->where['SourceFrom'] = ['in', $sourceFrom];
        } else if ($this->user['roleid'] == '12') {
            $promoters = D('Department')->getPromoters();
            $this->assign('promoters', $promoters);
        }

        $this->where['Del'] = 0;
        $this->sells = D('Department')->getSellDepartment($this->user['kid']);

    }

    /**
     * 所有客户信息
     */
    public function index()
    {
        $map = [];
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($this->where, $map);
        $options = [
            'map' => $map,
            'order' => 'InsertTime desc',
            'field' => 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Remark,PdepartId,DepartId,Color,LastVisitTime,IsWashing'
        ];

        $this->page('customer', $options);
        $sources1 = $this->getPSource();
        $this->assign('sources1', $sources1);
        $this->assign('sells', $this->sells);
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
        is_array($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        is_array($get['Status']) && $map['Status'] = ['in', $get['Status']];
        is_array($get['Opeartor']) && $map['Opeartor'] = ['in', $get['Opeartor']];
        is_array($get['salseId']) > 0 && $map['salseId'] = ['in', $get['salseId']];
        is_array($get['SourceFrom']) > 0 && $map['SourceFrom'] = ['in', $get['SourceFrom']];
        $get['IsOrder'] > -1 && $map['IsOrder'] = $get['IsOrder'];
        $get['DepartId'] > 0 && $pids = $this->selectByDepartId($get['DepartId']);
        !empty($pids) && $map['DepartId'] = ['in', $pids];
        if (empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] = date('Y-m-d', 0);
        } else {
           // $get['StartInsertTime'] = date('Y-m-d H:i', strtotime('-1 day 21:00', strtotime($get['StartInsertTime'])));
            $get['StartInsertTime'] = date('Y-m-d 00:00:00',  strtotime($get['StartInsertTime']));
        }
        if (empty($get['EndInsertTime'])) {
            //$get['EndInsertTime'] = date('Y-m-d H:i', strtotime('today 21:00'));
            $get['EndInsertTime'] = date('Y-m-d 23:59:59');
        } else {
            //$get['EndInsertTime'] = date('Y-m-d H:i', strtotime('21:00', strtotime($get['EndInsertTime'])));
            $get['EndInsertTime'] = date('Y-m-d 23:59:59', strtotime($get['EndInsertTime']));
        }
        // $map['Del'] = 0;
        $map['InsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];

        if (!empty($get['CustName'])) {
            $where['CustName'] = ['like', $get['CustName'] . '%'];
            $where['Mobile'] = ['like', $get['CustName'] . '%'];
            $where['WeiboName'] = ['like', $get['CustName'] . '%'];
            $where['WeChat'] = ['like', $get['CustName'] . '%'];
            $where['QQ'] = ['like', $get['CustName'] . '%'];
            $where['Wang'] = ['like', $get['CustName'] . '%'];
            $where['_logic'] = 'OR';
        }
        !empty($where) && $map['_complex'] = $where;
        return $map;
    }

    /**
     * 按部门选择
     * @param $DepartId
     * @return array
     */
    private function selectByDepartId($DepartId)
    {
        //按部门选择
        $pids = [];
        $department = D('Department')->getDepartment($DepartId);
        if (!empty($department)) {
            $dpath = $department['dpath'];
            $pathArr = explode('-', $dpath);
            if ($pathArr[2]) {
                $kid = $pathArr[2];
            } else {
                $kid = $department['dpartid'];
            }
        }
        $sells = D('Department')->getSellDepartment($this->user['kid']);
        $tree = D('Department')->getTree($sells, $department);
        $pids = array_keys($tree);
        $DepartId > -1 && array_push($pids, $DepartId);
        return $pids;
        //$get['DepartId'] > -1 && $map['DepartId'] = ['in', $pids];
    }

    /**
     * 邀约手客户信息
     * 邀约手添加的手机号、微信号、qq号、微博号
     * 销售客服是不能修改的
     */
    public function addCustomer()
    {
        $department = D('Department')->getDepartment($this->user['departid']);
        $dpath = explode('-', $department['dpath']);
        $kid = $dpath[2];
        $this->assign('kid', $kid);
        $sources = [];
        $rows = M('source')->order('OrderNo')->select();
        foreach ($rows as $key => $val) {
            $sources[$val['platform']][] = $val;
        }

        $this->assign('sources', $sources);
        $this->display();
    }

    /**
     * 邀约手邀约
     */
    public function doAddCustomer()
    {
        $kid = $this->user['kid'];
        $this->assign('kid', $kid);

        // 添加咨询
        $CustomerModel = D('Customer');
        $Customer = M('Customer');
        $User = D('User');
        $valid = $CustomerModel->create();
        if (!$valid) {
            $error = $CustomerModel->getError();
            $arr = [
                'code' => '400',
                'msg' => '重复客资......',
            ];
            $this->ajaxReturn($arr);
        }

        // 自动分配销售客服 0 自动分配
        $AppointType = I('AppointType');
        if ($AppointType == 0) {
            // 自动分配
            $sales = $this->_autoAssign($kid);
        } elseif ($AppointType == 2) {
           /* $acceptNum1 = (int)S('Count-' . I('salseId'));
            $maxOrder = $User->getUser(I('salseId'),'maxorder');
            if(($maxOrder>0)||($acceptNum1>=$maxOrder)){
                $this->ajaxReturn([
                    'code' => '400',
                    'msg' => $acceptNum1.'指定咨询不成功'.$maxOrder
                ]);
            }*/
            $sales = I('salseId');
        }

        if (empty($sales) || $sales < 0) {
            // 提示无客服, 并提示邀约手是哪个部门没有客服
            $StoreId = I('StoreId');
            $this->ajaxReturn([
                'code' => '500',
                'msg' => $this->brands[$this->stores[$StoreId]['brandid']]['brandname'] . $this->stores[$StoreId]['storename'] . '无客服服务'
            ]);
        } else {
            // 客服接单数累加
            // $expireTime = 43200 - date("h") * 3600 - date("i") * 60 - date("s");
            $acceptNum = (int)S('Count-' . $sales);
            if(in_array($acceptNum,[10,20,30,40,50,60])){
                $start = date('Y-m-d 00:00:00');
                $end = date('Y-m-d 23:59:59');
                $accepMap['InsertTime'] = ['between',[$start,$end]];
                $accepMap['salseId'] = $sales;
                $acceptNum = $Customer->where($accepMap)->count();
            }
            S('Count-' . $sales, $acceptNum + 1);
        }
        $CustomerModel->startTrans();
        // 设置销售
        $CustomerModel->salseId = $sales;
        $DepartId = D('User')->getUser($sales, 'departid');
        $CustomerModel->DepartId = $DepartId;

        // 设置客户号
        $custNo = date("Ymdhis", time()) . rand(1000, 9999);
        $CustomerModel->CustNo = $custNo;
        $rs1 = $CustomerModel->add();

        // 分配表添加记录
        $lastId = $CustomerModel->getLastInsID();
        $assign = ['CustId' => $lastId, 'CustNo' => $custNo, 'InitUser' => $sales, 'NowUser' => $sales,
            'Status' => 0, 'InsertTime' => date('Y-m-d H:i:s'), 'UserCount' => 1, 'DepartId' => 0,
            'Invitor' => $this->user['userid'], 'InvitDepart' => $this->user['departid'],
            'IsReward' => 0, 'IsWashing' => 0, 'AppointType' => I('AppointType'),
        ];
        $rs2 = M('assign')->add($assign);
        $assignId = M('assign')->getLastInsID();

        // 宝宝店的信息
        if ($_POST['StoreId'] == 65) {
            $Department = D('Department');
            $birth = [
                'CustId' => $lastId, 'KId' => $kid, 'PDepartId' => $this->user['departid'], 'SDepartId' => $DepartId,
                'PromoterId' => $this->user['userid'], 'SellerId' => $sales, 'IsMarried' => $_POST['IsMarried'],
                'IsHasVisa' => $_POST['IsHasVisa'], 'Pregnancy' => $_POST['Pregnancy'], 'BirthType' => $_POST['BirthType'],
                'KName' => $Department->getDepartment($kid, 'departname'), 'PDepartName' => $Department->getDepartment($this->user['departid'], 'departname'),
                'SDepartName' => $Department->getDepartment($DepartId, 'departname'), 'PromoterName' => $this->user['realname'],
                'SellerName' => D('User')->getUser($sales, 'realname'), 'InsertTime' => time(), 'area' => $_POST['area']
            ];
            M('CustomerBirth')->add($birth);
        }

        if ($rs1 && $rs2) {
            $CustomerModel->commit();
            $arr = ['code' => '200', 'msg' => '客户分配成功', 'redirect' => $this->referer];
        } else {

            $arr = ['code' => '500', 'msg' => '客户分配失败,请稍后重试...'];
            $CustomerModel->rollback();
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 新的分配模式
     */
    private function _autoAssign()
    {
        // echo "开始计算...\n";
        $get = I("post.");
        $store = $this->stores[$get['StoreId']];
        // 负责该门店的所有销售部门
        $StoreDepartments = explode(',', $store['sellids']);
        // 多部门的情况
        $sales = 0;
        $count = count($StoreDepartments);
        if ($count > 1) {

            $amounts = [];
            $Customer = M('Customer');
            //$yestoday = date('Y-m-d H:i:s', strtotime('-1 day 21:00'));
            $yestoday = date('Y-m-d 00:00:00');
            //$today = date('Y-m-d H:i:s', strtotime('Today 21:00'));
            $today = date('Y-m-d 23:59:59');
            foreach ($StoreDepartments as $key => $val) {
                $map['StoreId'] = $get['StoreId'];
                $map['DepartId'] = $val;
                // $map['SourceFrom'] = $get['SourceFrom'];
                $map['Del'] = 0;
                $map['InsertTime'] = ['between', [$yestoday, $today]];
                $amounts[$val] = $Customer->where($map)->count();
            }

            $max = max($amounts);
            $min = min($amounts);
            if ($max - $min >= $count) {
                // 总数相差个部门总数的情况，直接将咨询分配给最少的那一组
                $department = 0;
                foreach ($amounts as $key => $val) {
                    if ($min == $val) {
                        $department = $key;
                        break;
                    }
                }
                if ($department == 0) return 0;
                $sales = $this->departmentSellerQueue($department);

            } else {
                // 否则按照门店队列来
                $department = $this->storeDepartmentQueue($get['StoreId'], $get['SourceFrom']);
                $sales = $this->departmentSellerQueue($department);

            }

        } else {
            // 单个部门
            $department = $store['sellids'];
            $sales = $this->storeSellerQueue($get['StoreId']);
        }

        if ($sales == '0') {
            $this->ajaxReturn(['code' => '400', 'msg' => $this->departments[$department]['departname'] . ' 无客服在线']);
        }

        return $sales;
    }

    /**
     * 非PK门店，接单客服队列
     */
    private function storeSellerQueue($StoreId)
    {
        $sales = 0;
        $QueueName = "StoreSellerQueue-" . $StoreId;
        $queue = S($QueueName);
        if (empty($queue)) {
            $queue = explode(",", $this->stores[$StoreId]['users']);
            S($QueueName, $queue);
        }

        $User = D('User');
        $onlineName = 'UserOnline-';
        foreach ($queue as $key => $val) {

            if (S($onlineName.$val)==1) {

                $MaxOrder = $User->getUser($val, 'maxorder');
                $acceptNum = (int)S('Count-' . $val);
                if ($MaxOrder > 0 && $acceptNum >= $MaxOrder) {
                    continue;
                }
               /* if(in_array($val,[1425,1036,1025,1483,1003])){
                    //echo $val.','.S($onlineName.$val).',';
                    file_put_contents('1.txt',$MaxOrder.'m---v'.$val.'--a'.$acceptNum,FILE_APPEND);
                }*/
                unset($queue[$key]);
                array_push($queue, $val);
                $queue = array_values($queue);
                S($QueueName, $queue);
                $sales = $val;
                break;
            }
        }

        return $sales;
    }

    /**
     * PK门店，销售部门队列
     * @param $StoreId 门店ID
     * @param $source 来源ID
     * @return int
     */
    private function storeDepartmentQueue($StoreId, $source)
    {
        $QueueName = "queue-store-department-" . $StoreId . '-' . $source;
        $queue = S($QueueName);

        if (empty($queue)) {
            $store = $this->stores[$StoreId];
            $queue = explode(',', $store['sellids']);
            S($QueueName, $queue);
        }

        $department = array_shift($queue);
        array_push($queue, $department);
        S($QueueName, $queue);

        return $department;
    }


    private function departmentSellerQueue($department)
    {
        $sales = 0;
        $QueueName = "queue-department-seller-" . $department;
        $queue = S($QueueName);
        if (empty($queue)) {
            $users = M("user")->field("UserId")->where(['DepartId' => $department])->select();
            $queue = array_column($users, 'userid');
            S($QueueName, $queue);
        }

        $StoreId = I("StoreId");
        $store = $this->stores[$StoreId];
        $users = explode(',', $store['users']);

        $User = D('User');
        $onlineName = 'UserOnline-';
        foreach ($queue as $key => $val) {
            if (S($onlineName.$val)==1 && in_array($val, $users)) {
                $MaxOrder = $User->getUser($val, 'maxorder');
                $acceptNum = (int)S('Count-' . $val);
                if ($MaxOrder > 0 && $acceptNum >= $MaxOrder) {
                    continue;
                }

                unset($queue[$key]);
                array_push($queue, $val);
                $queue = array_values($queue);
                S($QueueName, $queue);
                $sales = $val;
                break;
            }
        }
        return $sales;
    }



    /**
     * load data local infile '~/user.csv' into table tk_user_copy fields terminated by ',' lines terminated by '\r\n' (UserId,UserNo,UserAccount,UserPwd,RoleId,RealName,Sex,Age,DepartId,Email,Tel,Mobile,IsOnline,isLock,StoreId,LastActiveTime,MaxOrder,ManageArea,SourceFrom,LastIP);
     * 邀约手变价客户信息
     */
    public function editCustomer()
    {
        $where = array('CustId' => $_GET['id']);
        $data = M('customer')->where($where)->find();

        $this->assign('d', $data);
        $stores = M('store')->field('StoreId,StoreName')->select();
        $this->assign('stores', $stores);
        $sources = M('source')->field('sourceId,sourceName')->select();
        $this->assign('sources', $sources);
        layout('Layout/win');
        $this->display();
    }

    /**
     * 执行添加
     */
    public function doEditCustomer()
    {
        //操作记录
        operateLog($this->user['userid'], $this->user['realname'], '编辑了客户个人信息' . I('CustName'), 3);
        $this->edit('Customer', true);

    }

    /**
     * 删除品牌
     */
    public function delCustomer()
    {
        $d['Del'] = 1;
        $Customer = M('Customer');
       /* $result = $Customer->field('QQ,Mobile,WeChat,WeiboName')->where(['CustId' => $_GET['id']])->find();
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
        $rid = $Customer->where(['CustId' => $_GET['id']])->save($d);
        if ($rid) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了客户个人信息' . $_GET['id'], 2);
            $arr['alert'] = '删除成功';
            $arr['redirect'] = U('promoter/index');
        } else {
            $arr['alert'] = '删除失败';
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 获取指定门店客服列表
     */
    public function getStoreSales()
    {
        if ($this->user['kid'] != '67' && $this->user['kid'] != '12') {
            $storeId = I('storeId');
            $storeUsers = explode(',', $this->stores[$storeId]['users']);
        } else {
            $storeUsers = array_column($this->ksellers, 'userid');
           // $this->user['kid'] == '67' && $storeUsers[] = 1105;
        }

        $this->assign('StoreSalses', $storeUsers);

        layout(false);
        $this->display();

    }

    /**
     * 检测手机号、微博、微信、QQ的唯一性
     */
    public function unique()
    {
        $mobile = I('mobile');
        $qq = I('QQ');
        $wechat = I('WeChat');
        $weiboName = I('WeiboName');
        $customerModel = M('customer');
        if ($mobile) {
            $resMobile = $customerModel->field('Mobile,QQ,WeiboName,WeChat')->where(['Mobile' => $mobile])->find();
            if (!empty($resMobile)) {
                $data['vmobile'] = 1;
                $data['code'] = 200;
            } else {
                $data['vmobile'] = 0;
            }
        }
        if ($qq) {
            $resQq = $customerModel->field('Mobile,QQ,WeiboName,WeChat')->where(['QQ' => $qq])->find();
            if (!empty($resQq)) {
                $data['vqq'] = 1;
                $data['code'] = 200;
                //$data['redirect'] = U('Promoter/index',['qq'=>$qq]);
            } else {
                $data['vqq'] = 0;
            }
        }
        if ($wechat) {
            $resWechat = $customerModel->field('Mobile,QQ,WeiboName,WeChat')->where(['WeChat' => $wechat])->find();
            if (!empty($resWechat)) {
                //$data['vweChat'] =1;
                $data['code'] = 200;
                // $data['redirect'] = U('Promoter/index',['weChat'=>$wechat]);
            } else {
                $data['vweChat'] = 0;
            }
        }
        if ($weiboName) {
            $resWeiboName = $customerModel->field('Mobile,QQ,WeiboName,WeChat')->where(['WeiboName' => $weiboName])->find();
            if (!empty($resWeiboName)) {
                $data['vweiboName'] = 1;
                $data['code'] = 200;
                //$data['redirect'] = U('Promoter/index',['weiboName'=>$weiboName]);
            } else {
                $data['vweiboName'] = 0;
            }
        }
        //$data['url'] = U('Promoter/repeatCustomerLoad');
        $this->ajaxReturn($data);
    }

    public function expromoter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map = [];
        $endId = I('endId');
        if (!$endId) {
            return false;
        }elseif($endId == 1){
            S('expNum-promoter' . $this->user['userid'],0);
        }
        $listNum = I('listNum');
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($this->where, $map);
        $customerModel = M('customer');
        $count = $customerModel->where($map)->order('CustId')->count();
        $endId = $endId;
        $listNum = $listNum ? $listNum : 1000;

        $map['CustId'] = ['gt', $endId];
        $field = 'CustId,CustNo,Company,CustName,StoreId,Mobile,Wechat,WeiboName,QQ,QQCode,Wang,Keywords,Status,IsOrder,InsertTime,LastVisitTime,SourceFrom,Opeartor,salseId,DepartId';

        $customers = M('customer')->field($field)->where($map)->order('CustId')->limit($listNum)->select();
        $data = [];
        $data1 = $customers;
        $num = S('expNum-promoter' . $this->user['userid']);
        $num = $num + count($customers) + I('num');
        if($count>60000){
            $this->ajaxReturn([
                'num' => $num,
                'error' => '导出量不能大于60000',
                'count' => $count,
            ]);
        }
        S('expNum-promoter' . $this->user['userid'], $num);
        $lastData = array_pop($data1);

        $filename = S('expFile-promoter' . $this->user['userid']);
        if (!$filename) {
            $filename = './expfile/customer-promoter' . $this->user['useraccount'] . '-' . date('YmdHis') . '.csv';
            S('expFile-promoter' . $this->user['userid'], $filename);
        }
        if (!empty($customers)) {
            $output = fopen($filename, 'a') or die('can not open' . $filename);
            if($this->user['roleid']!=7){
                $FieldName = ['城市品牌', '咨询门店', '客户姓名', '手机号', '微信号', '微博昵称', 'QQ号', 'QQ验证', '旺旺号', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '销售部门','接单销售','备注'];
            }else{
                $FieldName = [ '城市品牌', '咨询门店','客户姓名', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '销售部门','接单销售'];
            }

            foreach ($FieldName as $key => $val) {
                $FieldName[$key] = iconv('utf-8', 'gbk', $val);
            }

            $flush = 0;
            $limit = $listNum;
            if ($num <= $listNum) {
                fputcsv($output, $FieldName);
            }

            foreach ($customers as $key => $val) {
                // $data['CustNo'] = iconv('utf-8', 'gbk', $val['custno']);
                $data['Company'] = iconv('utf-8', 'gbk', $val['company']);
                $data['StoreId'] = iconv('utf-8', 'gbk', $this->brands[$this->stores[$val['storeid']]['brandid']]['brandname'] . $this->stores[$val['storeid']]['storename']);
                $data['CustName'] = iconv('utf-8', 'gbk', $val['custname']);

                if($this->user['roleid']!=7){
                    $data['Mobile'] = iconv('utf-8', 'gbk', $val['mobile']);
                    $data['Wechat'] = iconv('utf-8', 'gbk', $val['wechat']);
                    $data['WeiboName'] = iconv('utf-8', 'gbk', $val['weiboname']);
                    $data['QQ'] = iconv('utf-8', 'gbk', $val['qq']);
                    $data['QQCode'] = iconv('utf-8', 'gbk', $val['qqcode']);
                    $data['Wang'] = iconv('utf-8', 'gbk', $val['wang']);
                }

                $data['Keywords'] = iconv('utf-8', 'gbk', $val['keywords']);
                $data['AssignStatus'] = iconv('utf-8', 'gbk', D('Assign')->getFieldInfo($val['custno'], 'status'));
                $data['Status'] = iconv('utf-8', 'gbk', $this->status[$val['status']] ? $this->status[$val['status']] : '未回访');
                $data['VisitTimes'] = iconv('utf-8', 'gbk', D('Customer')->getVisitTimes($val['custno']));
                $data['IsOrder'] = iconv('utf-8', 'gbk', $val['isorder'] > 0 ? '是' : '否');
                $data['InsertTime'] = $val['inserttime'];

                //$time = explode(' ', $val['lastvisittime']);
                $data['LastVisitTime'] = $val['lastvisittime'];
                $data['SourceFrom'] = iconv('utf-8', 'gbk', $this->sources[$val['sourcefrom']]['sourcename']);
                $data['Opeartor'] = iconv('utf-8', 'gbk', D('User')->getUser($val['opeartor'], 'realname'));
                // 获取销售的部门信息
                $department = D('Department')->getDepartment($val['departid'], 'departname');
                $data['DepartId'] = iconv('utf-8', 'gbk', $department);
                $data['Seller'] = iconv('utf-8', 'gbk', D('User')->getUser($val['salseid'], 'realname'));
                $data['Mark'] = iconv('utf-8', 'gbk', D('Visit')->getVisitRemark($val['custid']));
                fputcsv($output, $data);

                $flush++;
                if ($limit == $flush) {
                    ob_flush();
                    flush();
                    $flush = 0;
                }
            }

            fclose($output) or die("can not close");
            // $this->outDown($customers);
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
            operateLog($this->user['userid'], $this->user['realname'], '导出了时间段为' . $map['InsertTime'][1][0] . '-' . $map['InsertTime'][1][1] . '推广客咨' . $filename, 4);
            $fileExp = $filename;
            unset($filename);
            unset($num);
            S('expFile-promoter' . $this->user['userid'], null);
            S('expNum-promoter' . $this->user['userid'], 0);
            $this->ajaxReturn([
                'num' => 0,
                'endId' => $lastData['custid'],
                'listNum' => $listNum,
                'count' => $count,
                'expUrl' => $fileExp
            ]);

        }

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
     * @return array
     */
    public function getPSource()
    {

        $sources = [];
        $sources = D('Source')->getPromoterSource();
        if (empty($sources)) {
            $rows = M('source')->order('OrderNo')->select();
            foreach ($rows as $key => $val) {
                if ($val['sourcename']) {
                    $sources[$val['platform']][] = $val;
                }
            }
        }

        return $sources;
    }


}