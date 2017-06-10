<?php
namespace KWS\Controller;

/**
 * Class SellerController
 * 客服销售人员相关功能
 * @package KWS\Controller
 */
class AdminController extends BaseController
{
    /**
     * 所有客户
     */
    public function index()
    {

        //高级搜索部门的部门和门店
        $stores = $this->user['storeid'];
        $storeArr = explode(',', $stores);
        $this->assign('storeArr', $storeArr);
        $departId = $this->user['departid'];
        $dpath = D('department')->getDepartment($departId, 'dpath');
        $dpatharr = explode('-',$dpath);
        if($dpatharr[2]){
            $kid = $dpatharr[2];
            $dpath = str_replace('0-1-'.$kid,'',$dpath);
        }

        $department = array_filter(explode('-', $dpath));
        $this->assign('department', $department);

        //权限限制每个人用户只能看自己所在k的用户数据
        $this->user['kid'] && $map['Kid'] = $kid;
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
            $map['DepartId'] = ['in', $departmentIds];
        }

        // 快捷搜索
        !empty($_GET['StoreId']) && $map['StoreId'] = I('StoreId');
        !empty($_GET['CustNo']) && $map['CustNo'] = trim(I('CustNo'));
        !empty($_GET['Mobile']) && $map['Mobile'] = trim(I('Mobile'));
        !empty($_GET['WeChat']) && $map['WeChat'] = trim(I('WeChat'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['QQ']) && $map['QQ'] = trim(I('QQ'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['seller']) && $seller = D('User')->getUserByName(trim($_GET['seller']),'userid');
        if($seller) $map['salseId'] = $seller;
        !empty($_GET['opeartor']) && $opeartor = D('User')->getUserByName(trim($_GET['opeartor']),'userid');
        if($opeartor) $map['Opeartor'] = $opeartor;
        !empty($_GET['DepartId']) && $map['DepartId'] = I('DepartId');
        !empty($_GET['Status']) && $map['Status'] = I('Status');
        !empty($_GET['SourceFrom']) && $map['SourceFrom'] =  I('SourceFrom');
        $InsertStart = trim(I('InsertStart'));
        $InsertEnd = trim(I('InsertEnd'));
        if ($InsertStart && $InsertEnd) {
            $map['InsertTime'] = ['between', array($InsertStart.' 00:00:00', $InsertEnd.' 23:59:59')];
        }
        !empty($_GET['CustName']) && $map['CustName'] = '%'.trim(I('CustName')).'%';

        //高级搜索部分
        !empty($_REQUEST['store']) && $map['StoreId'] = array('in', $_REQUEST['store']);
        !empty($_REQUEST['status']) && $map['Status'] = array('in', $_REQUEST['status']);
        !empty($_REQUEST['source']) && $map['SourceFrom'] = ['in', $_REQUEST['source']];
        !empty($_REQUEST['IsOrder']) && $map['IsOrder'] = $_REQUEST['IsOrder'];
        !empty($_REQUEST['AssignStatus']) && $map['AssignStatus'] = ['in', $_REQUEST['AssignStatus']];



        $options = [
            'map' => $map,
            'order'=> 'InsertTime desc',
            'field' => 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Remark,LastVisitTime,IsOrder'
        ];
        $this->page('customer', $options);
        S('mapseller'.$this->user['userid'],$map,60);

        $this->display();
    }




    public function expSeller()
    {
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new \PHPExcel();
        $cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
        $FieldName = ['序号', '城市品牌', '客户姓名', '咨询门店', '手机号', '微信号', '微博昵称', 'QQ号', 'QQ验证', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '接单销售','备注'];

        $Sheet = $PHPExcel->setActiveSheetIndex(0);
        $Sheet->getRowDimension(2)->getRowHeight(19);
        $Sheet->getColumnDimension()->setAutoSize(true);

        foreach ($FieldName as $key => $val) {
            $Sheet->setCellValue($cellName[$key] . '1', $val);
            $Fill = $Sheet->getStyle($cellName[$key] . '1');
            $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $Fill->getFill()->getStartColor()->setRGB('999999');
            $Fill->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        }
        $options = [];
        $map = S('mapseller'.$this->user['userid']);
        $customers = M('customer')->where($map)->select();
        // print_r($customers);.
        $User = D('User');
        $orderModel = M('order');
        $field = 'OrderNo,Money,PayMoney,OrderType,OrderPay,Remarks,CustomerType';
        foreach ($customers as $key => $val) {
            $order = $orderModel->field($field)->find($val['custid']);
            $storeName = $this->brand[$this->store[$val['storeid']]['brandid']]['brandname'] . $this->store[$val['storeid']]['storename'];
            $source = $this->source[$val['sourcefrom']];
            //录入时间不要时分秒
            $insertTime = explode(' ',$val['inserttime']);

            $user = $User->getUser($val['salseid']);
            $department = M('Department')->field('DepartId,dpath')->find($user['departid']);
            $dpath = explode('-', $department['dpath']);
            $Sheet->setCellValue('A' . ($key + 2), $key + 1);
            $Sheet->getColumnDimension('A')->setAutoSize(true);
            $Sheet->setCellValue('B' . ($key + 2), $val['company']);
            $Sheet->getColumnDimension('B')->setAutoSize(true);
            $Sheet->setCellValue('C' . ($key + 2), $val['custname']);
            $Sheet->getColumnDimension('C')->setAutoSize(true);
            $Sheet->setCellValue('D' . ($key + 2), $this->brand[$this->store[$val['storeid']]['brandid']]['brandname'] . $this->store[$val['storeid']]['storename']);
            $Sheet->getColumnDimension('D')->setAutoSize(true);
            $Sheet->setCellValue('E' . ($key + 2), $val['mobile']);
            $Sheet->getColumnDimension('E')->setAutoSize(true);
            $Sheet->setCellValue('F' . ($key + 2), $val['wechat']);
            $Sheet->getColumnDimension('F')->setAutoSize(true);
            $Sheet->setCellValue('G' . ($key + 2), $val['weiboname']);
            $Sheet->getColumnDimension('G')->setAutoSize(true);
            $Sheet->setCellValue('H' . ($key + 2), $val['qq']);
            $Sheet->getColumnDimension('H')->setAutoSize(true);
            $Sheet->setCellValue('I' . ($key + 2), $val['qqcode']);
            $Sheet->getColumnDimension('I')->setAutoSize(true);
            $Sheet->setCellValue('J' . ($key + 2), $val['keywords']);
            $Sheet->getColumnDimension('J')->setAutoSize(true);
            $Sheet->setCellValue('K' . ($key + 2), D('Assign')->getFieldInfo($val['custid'], 'status'));
            $Sheet->getColumnDimension('K')->setAutoSize(true);
            $Sheet->setCellValue('L' . ($key + 2), $this->status[$val['status']]);
            $Sheet->getColumnDimension('L')->setAutoSize(true);
            $Sheet->setCellValue('M' . ($key + 2), D('Customer')->getVisitTimes($val['custid']));
            $Sheet->getColumnDimension('M')->setAutoSize(true);
            $Sheet->setCellValue('N' . ($key + 2), $val['isorder'] ? '是' : '否');
            $Sheet->getColumnDimension('N')->setAutoSize(true);
            $Sheet->setCellValue('O' . ($key + 2), $insertTime[0]);
            $Sheet->getColumnDimension('O')->setAutoSize(true);
            $Sheet->setCellValue('P' . ($key + 2), $val['lastvisittime']);
            $Sheet->getColumnDimension('P')->setAutoSize(true);
            $Sheet->setCellValue('Q' . ($key + 2), $this->source[$val['sourcefrom']]);
            $Sheet->getColumnDimension('Q')->setAutoSize(true);
            $Sheet->setCellValue('R' . ($key + 2), D('User')->getUser($val['opeartor'], 'realname'));
            $Sheet->getColumnDimension('R')->setAutoSize(true);
            $Sheet->setCellValue('S' . ($key + 2), D('Assign')->getAssigndata($val['custid'], 'nowuser'));
            $Sheet->getColumnDimension('S')->setAutoSize(true);
            $Sheet->setCellValue('T' . ($key + 2), D('Visit')->getVisitRemark($val['custid']));
            $Sheet->getColumnDimension('T')->setAutoSize(true);

        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="导出.xls"');
        header("Content-Disposition:attachment;filename=导出.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');
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
        $stores = M('store')->field('StoreId,StoreName')->select();
        $this->assign('stores', $stores);

        $sources = M('source')->field('sourceId,sourceName')->select();
        $this->assign('sources', $sources);
        $this->display();
    }

    /**
     * 编辑客户信息
     *
     */
    public function editCust()
    {
        layout('Layout/win');
        $id = I('id');
        if ($id) {
            $res = M('customer')->where(['CustId' => $id])->find();
            $this->assign('d', $res);
        }
        $storeList = M('store')->field('StoreId,StoreName')->select();
        $this->assign('storeList', $storeList);
        $photoTeam = M('photoTeam')->where(['TypeId' => 0])->select();
        $makeupTeam = M('photoTeam')->where(['TypeId' => 1])->select();
        $this->assign('photoTeam', $photoTeam);
        $this->assign('makeupTeam', $makeupTeam);
        $this->display();
    }

    /**
     * 保存客户信息
     *
     */
    public function doEditCust()
    {

        $customerModel = D('Customer');
        $valid = $customerModel->create();

        $kid = D('User')->getDepartId($this->user['departid']);
        $customerModel->Kid = $kid;
        $id = I('id');
        if ($id) {
            $customerModel->LastEditUser = $this->user['userid'];
            $customerModel->LastEditTime = date("Y-m-d H:i:s");
            $res = $customerModel->where(['CustId' => $id])->save();
            $d['Status'] = 1;
            M('assign')->where(['CustId' => $id])->save($d);
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '修改了客户信息' . I('RealName'), 'Seller/editCust-3');
        } else {
            $res = $customerModel->add();
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了客户信息' . I('RealName'), 'Seller/addCustomer-1');
        }

        if ($res) {
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

    /**
     * 回访管理
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

        //只能看自己k的数据
        $kid = D('User')->getDepartId($this->user['departid']);
        $kid && $map['Kid'] = trim($kid);
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
        $data['InsertTime'] = date("Y-m-d H:i:s");
        // $data['Status'] = 1;

        $res1 = $CustomerModel->where(['CustId' => $data['CustId']])->save($data);
        $res2 = $VisitModel->add($data);
        if ($res1 || $res2) {
            //如果assign里面status为0要改为1
            $assign = M('assign')->field('Status')->where(['CustId'=>$_POST['CustId']])->find();
            if($assign['status']==0){
                M('assign')->where(['CustId'=>$_POST['CustId']])->save(['Status'=>1]);
            }
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了客户回访信息' . I('CustName'), 'Seller/addVisit-1');
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
    public function  delCust()
    {
        $rid = M('customer')->where(['CustId' => $_GET['id']])->delete();
        if ($rid) {
            $data['alert'] = 'ok';
            $data['redirect'] = U('Seller/index');
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了客户信息' . $_GET['id'], 'Seller/delCust-2');
        } else {
            $data['alert'] = 'error';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 删除回访记录
     */
    public function  delBankList()
    {
        $rid = M('customer')->where(['CustId' => $_GET['id']])->delete();
        if ($rid) {
            $data['alert'] = 'ok';
            $data['redirect'] = U('Seller/banklist');
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '在回访管理删除了客户信息' . D('Customer')->getCustomerField($_GET['id'], 'custname'), 'Seller/backList-2');
        } else {
            $data['alert'] = 'error';
        }

        $this->ajaxReturn($data);
    }

    /**
     * 改期
     */
    public function  chgPlan()
    {

        layout('Layout/win');
        $this->display();
    }

    /**
     * 追踪客户
     */
    public function trackBackList()
    {
        $id = I('id');
        $d = M('customer')->where(['CustId' => $id])->find();
        //回访次数
        $visitnum = M('visit')->where(['CustId' => $id])->count();
        $d['visitnum'] = $visitnum;
        $this->assign('d', $d);
        $this->display();
    }

    public function accpet()
    {
        $assignId = I('AssignId');
        $map['Assignid'] = $assignId;
        $map['CustId'] = I('CustId');

        $res = M('assign')->where($map)->save(['Status' => 1]);


        if ($res) {
            $salse = M('assign')->field('CustId')->where(['AssignId' => $assignId])->find();
            $expireTime = 86400 - date("H") * 3600 - date("i") * 60 - date("s");
            $acceptNum = (int)S('Count-' . $salse['custid']);
            S('Count-' . $salse['custid'], $acceptNum + 1, ['expire' => $expireTime]);
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
        //echo '<pre>';
        //print_r($d);exit;
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
        // echo $model->_sql();exit;
        if ($res) {
            $assign = M('assign')->field('Status')->where(['CustId'=>$custid])->find();
            if($assign['status']==0){
                M('assign')->where(['CustId'=>$custid])->save(['Status'=>1]);
            }
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '在回访管理编辑了客户信息' . I('CustName'), 'Seller/editBack-3');
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
            operateLog($this->user['userid'], $this->user['realname'], '在回访管理添加了回访记录' . I('CustName'), 'Seller/addBack-1');
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
     * 回访日志
     */
    public function backLog()
    {
        $custid = I('id');
        $list = M('visit')->where(['CustId' => $custid])->select();
        //var_dump($list);exit;
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
        $this->display();
    }

    
}