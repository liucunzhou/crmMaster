<?php
namespace KWS\Controller;

class OrderController extends BaseController
{
    protected $customerType = [];
    protected $orderType = [];
    protected $where = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->customerType = [1 => '网单', 2 => '自然进店', 3 => '邀约进店', 4 => '退款', 5 => '电话订单',];
        $this->assign('customerType', $this->customerType);
        $this->orderPay = [1 => '天猫', 2 => '支付宝', 3 => '银行卡', 4 => '现金', 5 => '大众点评闪汇', 6 => 'POS机刷卡', 7 => '团购'];
        $this->assign('orderPay', $this->orderPay);
        $this->orderType = [1 => '全款', 2 => '保留金', 3 => '尾款', 4 => '定金','5'=>'退款'];
        $this->assign('ordertype', $this->orderType);

        if (in_array($this->user['roleid'], [6, 7])) {
            // 大区总裁
            $this->where['Kid'] = $this->user['kid'];
        } else if (in_array($this->user['roleid'], [5, 4, 3])) {
            // 客服经理，客服主管，客服组长
            $tree = D('Department')->getTree($this->departments, $this->department);
            $pids = array_keys($tree);
            $this->assign('log', $this->user['roleid']);
            if (!empty($pids)) {
                $this->where['DepartId'] = ['in', $pids];
            } else {
                $this->where['DepartId'] = $this->user['departid'];
            }

        } else if ($this->user['roleid'] == '1') {
            // 客服组员
            $this->where['SellerId'] = $this->user['userid'];
        }
    }

    public function index()
    {
        $map = [];
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($this->where, $map);
        $options = [
            'map' => $map,
            'order' => 'OrderTime desc'
        ];

        // 获取所有订单
        $list = $this->page('order', $options);

        $Customer = M('customer');
        $CustomerToday = M('customerToday');
        $PayLog = M('PayLog');
        $CustFields = 'CustNo,Mobile,QQ,Wechat,WeiboName,Keywords,StoreId,CustName,SourceFrom,Opeartor,salseId';
        $PayLogFields = 'Money,PayMoney,OrderType';
        foreach ($list as $k => $v) {
            // 获取客户信息
            $customer = $CustomerToday->field($CustFields)->find($v['custid']);
            if (empty($customer)) $customer = $Customer->field($CustFields)->find($v['custid']);
            $list[$k]['customer'] = $customer;

            // 获取支付日志
            $paylog = $PayLog->field($PayLogFields)->where(['OrderNo' => $v['orderno']])->select();
            $list[$k]['paylog'] = $paylog;
        }
        $this->assign('list', $list);

        // 回去推广列表
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);


        // 获取销售部门
        $sells = D('Department')->getSellDepartment($this->user['kid']);
        $this->assign('sells', $sells);
        $this->display();
    }

    public function _search()
    {
        $map = [];
        $get = I('get.');

        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        !empty($get['OrderNo']) && $map['OrderNo'] = $get['OrderNo'];
        !empty($get['Mobile']) && $map['Mobile'] = trim($get['Mobile']);
        $get['Opeartor'] > 0 && $map['PromoterId'] = $get['Opeartor'];
        $get['salseId'] > 0 && $map['SellerId'] = ['in', $get['salseId']];
        !empty($get['SourceFrom']) && $map['SourceFrom'] = ['in', $get['SourceFrom']];
        $get['OrderType'] > 0 && $map['OrderType'] = $get['OrderType'];
        $get['CustomerType'] > 0 && $map['CustomerType'] = $get['CustomerType'];
        $get['IsOrder'] > -1 && $map['IsOrder'] = $get['IsOrder'];
        if ($get['CustType'] != '-1' && !empty($get['Customer'])) {
            $map[$get['CustType']] = $get['Customer'];
        }

        if ($get['DepartId'] > 0) {

            $Departments = D("Department")->getTree($this->departments, $this->departments[$get['DepartId']]);
            if (!empty($Departments)) {
                $departIds = array_keys($Departments);
                !empty($departIds) && $map['DepartId'] = ['in', $departIds];
            }
        }

        if (empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] = '0000-00-00 00:00:00';
        } else {
            $get['StartInsertTime'] = $get['StartInsertTime'].' 00:00:00';
        }

        if (empty($get['EndInsertTime'])) {
            $get['EndInsertTime'] = date('Y-m-d H:i:s', strtotime('+10years'));
        } else {
            $get['EndInsertTime'] = date('Y-m-d', strtotime($get['EndInsertTime'])) . ' 23:59:59';
        }
        $map['OrderTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];
        /*$orders = M('order')->field('OrderId')->where($map)->select();
        $ids = array_column($orders, 'orderid');
        $ids = array_unique($ids);

         !empty($ids) && $map['OrderId'] = ['in',$ids];*/

        return $map;
    }

    public function expOrder()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map = [];
        $_GET['sf']=='yes' && $map = $this->_search();
        //!empty($map['OrderId']) && $map
        $map = array_merge($map, $this->where);
        if($map['OrderTime'][1][0]=='0000-00-00 00:00:00'||empty($map)){
            $map['OrderTime'][0][0] = "between";
            $map['OrderTime'][1][0] = date("Y-m-d")." 00:00:00";
            $map['OrderTime'][1][1] = date("Y-m-d")." 23:59:59";
        }
        if($this->user['userid']==1473){
            print_r($map);exit;
        }
        $this->assign('log', $map);
        $endId = I('endId');
        if (!$endId) {
            return false;
        }elseif($endId==1){
            S('expNum-order2' . $this->user['userid'],0);
        }
        $listNum = I('listNum');
        $payLogModel = M('PayLog');
        $count = $payLogModel->where($map)->order('LogId')->count();
        $listNum = $listNum ? $listNum : 1000;
        if ($endId > 1) {
            $map['LogId'] = ['gt', $endId];
        }
        $field = 'LogId,OrderId,OrderNo,Money,PayMoney,OrderType,Remarks,AddUser,SellerId,OrderTime,CustId,Remarks,Source';
        $list = M('PayLog')->field($field)->where($map)->order('LogId,OrderTime desc')->limit($listNum)->select();
        $data = [];
        $data1 = $list;

        $num = S('expNum-order2' . $this->user['userid']);
        $num = $num + count($list) + I('num');
        S('expNum-order2' . $this->user['userid'], $num, 10000);
        $lastData = array_pop($data1);
        $filename = S('expFile-order2' . $this->user['userid']);
        if (!$filename) {
            $filename = './expfile/expFile-order' . $this->user['useraccount'] . '-' . date('YmdHis') . '.csv';
            S('expFile-order2' . $this->user['userid'], $filename);
            $FieldName = ['订单号', '咨询门店', '交易门店', '客户姓名', '手机号', '微信号', 'QQ号', '微博昵称', '订单金额', '已付金额', '付款类别', '订单类别', '订单时间', '来源', '邀约手', '接单销售','部门', '关键字', '备注'];
            foreach ($FieldName as $key => $val) {
                $FieldName[$key] = iconv('utf-8', 'gbk', $val);
            }
        }

        $Order = M('Order');
        $User = D('User');
        $Customer = M('customer');
        $CustomerToday = M('customerToday');
        $field = 'CustId,WeChat,WeiboName,QQ,StoreId,CustName,SourceFrom,Opeartor,salseId,Mobile,Keywords,Company,CustName,InsertTime,OrderTime';
        if (!empty($list)) {
            $output = fopen($filename, 'a') or die('can not open' . $filename);
            $flush = 0;
            $limit = $listNum;
            if ($num <= $listNum) {
                fputcsv($output, $FieldName);
            }

            foreach ($list as $key => $val) {
                $order = $Order->where(['OrderNo' => $val['orderno']])->find();
                $customer = $CustomerToday->field($field)->where(['CustId' => $val['custid']])->find();
                if (empty($customer)) $customer = $Customer->field($field)->find($val['custid']);
                $data[$key]['OrderNo'] = iconv('utf-8', 'gbk', $val['orderno']);
                $data[$key]['StoreName'] = iconv('utf-8', 'gbk', $this->brands[$this->stores[$customer['storeid']]['brandid']]['brandname'] . $this->stores[$customer['storeid']]['storename']);
                $data[$key]['RealStore'] = iconv('utf-8', 'gbk', $this->brands[$this->stores[$order['storeid']]['brandid']]['brandname'] . $this->stores[$order['storeid']]['storename']);
                $data[$key]['CustomerName'] = iconv('utf-8', 'gbk', $customer['custname']);
                $data[$key]['Mobile'] = iconv('utf-8', 'gbk', $customer['mobile']);
                if (empty($data[$key]['Mobile'])) {
                    $data[$key]['Mobile'] =  iconv('utf-8', 'gbk',$order['mobile']);
                }
                $data[$key]['WeiboName'] = iconv('utf-8', 'gbk', $customer['weiboname']);
                $data[$key]['WeChat'] = iconv('utf-8', 'gbk', $customer['wechat']);
                $data[$key]['QQ'] = iconv('utf-8', 'gbk', $customer['qq']);
                $data[$key]['Money'] = iconv('utf-8', 'gbk', $val['money']);
                if($val['ordertype'] == '5') {
                    $data[$key]['PayMoney'] = iconv('utf-8', 'gbk', '-'.abs($val['paymoney']));
                } else {
                    $data[$key]['PayMoney'] = iconv('utf-8', 'gbk', $val['paymoney']);
                }
                $data[$key]['OrderType'] = iconv('utf-8', 'gbk', $this->orderType[$val['ordertype']]);
                $data[$key]['CustomerType'] = iconv('utf-8', 'gbk', $this->customerType[$order['customertype']]);
                $time = explode(' ', $val['ordertime']);
                $data[$key]['OrderTime'] = iconv('utf-8', 'gbk', $time[0]);
                if (!empty($val['source'])) {
                    $data[$key]['Source'] = iconv('utf-8', 'gbk', $this->sources[$val['source']]['sourcename']);
                } else {
                    $data[$key]['Source'] = iconv('utf-8', 'gbk', $this->sources[$customer['sourcefrom']]['sourcename']);
                }
                empty($data[$key]['Source']) && $data[$key]['Source'] = iconv('utf-8', 'gbk', '朋友转介绍');
                $data[$key]['Promoter'] = iconv('utf-8', 'gbk', $User->getUser($customer['opeartor'], 'realname'));
                if (empty($val['sellerid'])) {
                    $data[$key]['Seller'] = iconv('utf-8', 'gbk', $User->getUser($customer['salseid'], 'realname'));
                } else {
                    $data[$key]['Seller'] = iconv('utf-8', 'gbk', $User->getUser($val['sellerid'], 'realname'));
                }
                $department=D("Department")->getDepartment($order['kid'], 'departname').D("Department")->getDepartment($order['departid'], 'departname');
                $data[$key]['Department'] = iconv('utf-8', 'gbk',$department);
                $data[$key]['Keywords'] = iconv('utf-8', 'gbk', $val['keywords']);
                $data[$key]['Remarks'] = iconv('utf-8', 'gbk', $val['remarks']);
                fputcsv($output, $data[$key]);

                $flush++;
                if ($limit == $flush) {
                    ob_flush();
                    flush();
                    $flush = 0;
                }
            }
            fclose($output) or die("can not close");
            $num1= $num;
            unset($num);
            unset($list);
            unset($data);
            unset($data1);
            //unset($count1);
            $this->ajaxReturn([
                'num' => $num1,
                'endId' => $lastData['logid'],
                'listNum' => $listNum,
                'count' => $count,

            ]);
        } else {
            // 操作记录
            operateLog($this->user['userid'], $this->user['realname'], '导出了时间为'.$map['OrderTime'][1][0].'-'. $map['OrderTime'][1][1].'订单文件名称为'.$filename,4);
            $fileExp = $filename;
            //$count1 = $count;
            unset($filename);
            unset($num);
            unset($num1);
            //unset($count);
            unset($list);
            unset($data);
            unset($data1);
            S('expFile-order2' . $this->user['userid'], null);
            S('expNum-order2' . $this->user['userid'], null);
            $this->ajaxReturn([
                'num' => 0,
                'endId' => $lastData['logid'],
                'listNum' => $listNum,
                'count' => $count,
                'expUrl' => $fileExp
            ]);
        }

    }


    /**
     * 编辑订单
     */
    public function editOrder()
    {
        // 获取订单信息
        $OrderId = I('OrderId');
        $order = M('order')->where(['OrderId' => $OrderId])->find();
        $this->assign('d', $order);
        $fields = 'CustId,CustName,Mobile,QQ,WeChat,WeiboName,StoreId,Company,salseId,SourceFrom';
        // 获取客户信息
        $customer = M('customer')->field($fields)->where(['CustId' => $order['custid']])->find();
        $this->assign('customer', $customer);

        // 获取支付日志
        $map['OrderNo'] = $order['orderno'];
        $log = M('PayLog')->where($map)->order('InsertTime desc')->select();
        $this->assign('log', $log);

        //平台、来源
        $platForm = [
            '传统平台' => '传统平台',
            '微博平台' => '微博平台',
            '微信平台' => '微信平台',
            '官网平台' => '官网平台',
            '网转介绍' => '网转介绍',
        ];
        $this->assign('platForm', $platForm);
        $sources = D('Source')->getAllSource();
        $this->assign('sources', $sources);

        $sources1 = $this->getPSource();
        $this->assign('sources1', $sources1);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 保存订单修改
     *
     */
    public function doEditOrder()
    {
        $CustId = I('CustId');
        // 判断客户ID是否存在
        if (empty($CustId)) $this->ajaxReturn(['code' => '400', 'msg' => '请选择要录入的订单']);

        $OrderId = I('OrderId');
        // 验证订单的有效性
        $Morder = M('Order');
        $order = $Morder->find($OrderId);
        if (empty($order)) $this->ajaxReturn(['code' => '400', 'msg' => '该订单不存在']);

        // 检查该订单该支付类型是否已经支付
        $OrderType = I('OrderType');
        // $OrderTypes = [1 => '全款', 2 =>
        // '首款', 3 => '尾款', 4 => '定金'];
        $OrderTypes = [1 => '全款', 2 => '首款'];
        if (in_array($OrderType, [1, 2])) {
            $result = M('PayLog')->where(['OrderId' => $OrderId, 'OrderType' => $OrderType])->find();
            if (!empty($result)) $this->ajaxReturn([
                'code' => '400',
                'msg' => '该订单的' . $OrderTypes[$OrderType] . '已经支付录入过了！'
            ]);
        }

        // 保存支付日志
        $PayLog = D('PayLog');
        $PayLog->create();
        $PayLog->Source = I('SourceFrom');
        $res2 = $PayLog->add();

        // 更新订单信息
        $Order = D('Order');
        $Order->create();
        $Order->save();

        if ($res2) {
            $arr = [
                'code' => '200',
                'msg' => '订单保存成功',
                'layer' => 'yes',
            ];

            // 操作记录
            operateLog($this->user['userid'], $this->user['realname'], '编辑了客户ID' . $CustId . '的订单信息' . $OrderId, 1);
        } else {
            $arr = [
                'code' => '400',
                'msg' => '订单保存失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 添加订单
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
            $customerModel = M('customer');
            $customer = $customerModel->where($map)->select();
            //echo $customerModel->_sql();
            //exit;
            $this->assign('customer', $customer);
        }
        $sources1 = $this->getPSource();
        $this->assign('sources1', $sources1);

        $ksellers = D('User')->getUserOfKid($this->user['kid'], 'seller');
        $this->assign('ksellers', $ksellers);

        layout('Layout/win');
        $this->display();
    }

    /**
     * 添加非录入咨询的订单
     */
    public function addOrderExt()
    {
        $ksellers = D('User')->getUserOfKid($this->user['kid'], 'seller');
        $this->assign('ksellers', $ksellers);

        layout('Layout/win');
        $this->display();
    }

    public function doAddOrderExt()
    {
        $post = I("post.");
        $Customer = M('Customer');
        // 检测该用户是否已经在系统里存在
        $map['StoreId'] = $post['StoreId'];
        $map['Mobile'] = $post['Mobile'];
        $map['salseId'] = $post['salseId'];
        $data = $Customer->where($map)->find();

        unset($map);
        /*if(!empty($data)) {
            $result = [
                'code'  => '400',
                'msg'   => '该咨询已经存在,请从更换录入订单入口'
            ];
            $this->ajaxReturn($result);
        }*/

        // 构造客户信息
        $data = [];
        $data['CustNo'] = date('YmdHis') . rand(1000, 9999);
        $data['CustName'] = $post['CustName'];
        $data['StoreId'] = $post['StoreId'];
        $data['SourceFrom'] = $post['SourceFrom'];
        $data['Mobile'] = $post['Mobile'];
        $data['InsertTime'] = $post['CInsertTime'];
        $data['salseId'] = $post['salseId'];
        $data['Status'] = 8080;
        $data['IsOrder'] = 1;
        $data['Del'] = 2;
        // 获取销售客服的部门信息
        $seller = D('User')->getUser($post['salseId']);
        $data['Kid'] = $seller['kid'];
        $data['DepartId'] = $seller['departid'];
        $Customer->startTrans();
        $rs = $Customer->add($data);
        // 添加订单、支付记录信息
        if ($rs) {
            $CustId = $Customer->getLastInsID();
            // 构造订单数据,并写入订单数据
            $post['CustId'] = $CustId;
            $post['CustomerName'] = $post['CustName'];
            $post['Add_User'] = $this->user['userid'];
            $post['SellerId'] = $data['salseId'];
            $post['Kid'] = $seller['kid'];
            $post['DepartId'] = $seller['departid'];
            $post['PdepartId'] = -2;
            $post['PromoterId'] = -2;
            $post['InsertTime'] = date('Y-m-d H:i:s');
            $rs1 = M('Order')->add($post);
            $OrderId = M('Order')->getLastInsID();

            // 构造支付记录数据，
            $post['OrderId'] = $OrderId;
            $post['AddUser'] = $this->user['userid'];
            $rs2 = M('PayLog')->add($post);

            if ($rs1 && $rs2) {
                $Customer->commit();
                $result = [
                    'code' => '200',
                    'msg' => '录入客户信息成功,订单录入成功',
                    'layer' => 'yes'
                ];
                $this->ajaxReturn($result);
            } else {
                // 录入客户信息失败
                $Customer->rollback();
                $result = [
                    'code' => '500',
                    'msg' => '录入客户信息失败,订单信息失败,请与系统管理员联系'
                ];
                $this->ajaxReturn($result);
            }

        } else {
            // 录入客户信息失败
            $Customer->rollback();
            $result = [
                'code' => '500',
                'msg' => '录入客户信息失败,订单信息失败,请与系统管理员联系'
            ];
            $this->ajaxReturn($result);
        }
    }

    /**
     * 检测订单号的唯一性
     */
    public function checkOrderNoUnique()
    {
        $OrderNo = I('OrderNo');
        $result = M('Order')->where(['OrderNo' => trim($OrderNo)])->find();

        if ($result) {

            $json = [
                'code' => '400',
                'msg' => '该订单号已经在'
            ];
        } else {
            $json = [
                'code' => '200',
                'msg' => '该订单号有效'
            ];
        }

        $this->ajaxReturn($json);
    }

    /**
     * 保存订单信息
     */
    public function doAddOrder()
    {
        $CustId = I('CustId');
        // 获取客户信息
        if (empty($CustId)) $this->ajaxReturn(['code' => '400', 'msg' => '请选择要录入的订单']);

        // 添加订单
        $Order = D('Order');
        $Order->create();
        $Order->Add_User = $this->user['userid'];
        $res1 = $Order->add();
        $OrderId = $Order->getLastInsID();

        // 获取支付日志
        $PayLog = D('PayLog');
        $PayLog->create();
        $PayLog->OrderId = $OrderId;
        $PayLog->Kid = $this->user['kid'];
        $PayLog->AddUser = $this->user['userid'];
        $PayLog->Source = I('SourceFrom');
        $res2 = $PayLog->add();

        if ($res1 && $res2) {
            M('customer')->where(['CustId' => I('CustId')])->save(['IsOrder' => 1]);
            $arr = [
                'code' => '200',
                'msg' => '订单保存成功',
                'layer' => 'yes',
            ];
            // 操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了客户ID' . $CustId . '的订单信息' . $OrderId, 1);
        } else {
            $arr = [
                'code' => '400',
                'msg' => '订单保存失败',
            ];
        }
        $this->ajaxReturn($arr);
    }

    /**
     * 删除订单
     */
    public function  delOrder()
    {
        $id = I('OrderId');
        $res = M('order')->where(['OrderId' => $id])->delete();
        if ($res) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了订单信息' . $id, 2);
            M('PayLog')->where(['OrderId' => $id])->delete();
            $data['code'] = '200';
            $data['alert'] = '删除成功';
            $data['redirect'] = U('Order/index');

        } else {
            $data['code'] = '400';
            $data['alert'] = '删除失败';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 删除支付日志
     */
    public function delPayLog()
    {
        $result = M('PayLog')->delete(I("LogId"));
        if ($result) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了订单明细信息' . I("LogId"), 2);
            $arr = [
                'code' => '200',
                'msg' => '删除成功',
                'reload' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '500',
                'msg' => '删除失败',
            ];
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 获取客户信息
     */
    public function getCustomer()
    {
        $id = $_GET['id'];
       // $id&&$map['CustId'] = $id;
        //$this->user['kid']&&$map['Kid'] = $this->user['kid'];
        $customer = M('customer')->field('CustId,Mobile,CustName,salseId,StoreId,Opeartor,DepartId,PdepartId,Kid,SourceFrom')->where(['CustId' => $id])->find();
        $seller = D('User')->getUser($customer['salseid'], 'realname');
        $customer['seller'] = $seller;
        $customer['add_user'] = $customer['salseid'];
        $this->ajaxReturn($customer);
    }

    public function updateOrder()
    {
        $get = I("get.");
        $Order = M("Order");
        $get['OrderId']&&$customer = $Order->field('CustId')->where(['OrderId'=>$get['OrderId']])->find();
        $map = [];
        $get['OrderNo']&&$map['OrderNo'] = $get['OrderNo'];
        $customer['custid']&&$map['CustId'] = ['neq',$customer['custid']];
        $res = M('Order')->where($map)->find();
        if(!empty($res)){
            $this->ajaxReturn([
                'code' => '500',
                'msg' => '订单号重复,更新失败'
            ]);
        }
        $Order->startTrans();
        $result1 = $Order->where(['OrderId' => $get['OrderId']])->save(['SourceFrom' => $get['SourceFrom'],'OrderNo'=>$get['OrderNo']]);
        $result2 = M("PayLog")->where(['OrderId' => $get['OrderId']])->save(['Source' => $get['SourceFrom'],'OrderNo'=>$get['OrderNo']]);

        if ($result1 || $result2) {
            $Order->commit();
            $this->ajaxReturn([
                'code' => '200',
            ]);
        } else {
            $Order->rollback();
            $this->ajaxReturn([
                'code' => '500',
                'msg' => '更新失败'
            ]);
        }
    }

    /**
     * @return array
     */
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