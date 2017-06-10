<?php
namespace KWS\Controller;

/**
 * Class SellerController
 * 客服销售人员相关功能
 * @package Admin\Controller
 */
use Think\Controller;
class WashingController extends BaseController
{
    /**
     * 所有客户
     */
    public function index()
    {
        //权限限制每个人用户只能看自己所在k的用户数据

        // 快捷搜索
        !empty($_GET['StoreId']) && $map['StoreId'] = I('StoreId');
        !empty($_GET['CustNo']) && $map['CustNo'] = trim(I('CustNo'));
        !empty($_GET['Mobile']) && $map['Mobile'] = trim(I('Mobile'));
        !empty($_GET['WeChat']) && $map['WeChat'] = trim(I('WeChat'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['QQ']) && $map['QQ'] = trim(I('QQ'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['salseId']) && $map['salseId'] = trim(I('salseId'));
        !empty($_GET['Opeartor']) && $map['Opeartor'] = trim(I('Opeartor'));
        !empty($_GET['DepartId']) && $map['DepartId'] = I('DepartId');
        !empty($_GET['Status']) && $map['Status'] = I('Status');
        !empty($_GET['SourceFrom']) && $map['SourceFrom'] = I('SourceFrom');
        $InsertStart = trim(I('InsertStart'));
        $InsertEnd = trim(I('InsertEnd'));
        if ($InsertStart && $InsertEnd) {
            $map['InsertTime'] = ['between', array($InsertStart . ' 00:00:00', $InsertEnd . ' 23:59:59')];
        }
        !empty($_GET['CustName']) && $map['CustName'] = ['like', '%' . trim(I('CustName')) . '%'];

        $options = [
            'map' => $map,
            'field' => 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Remark,LastVisitTime,IsOrder,IsAssign'
        ];
        $this->page('wash', $options);
        $this->display();
    }

    public function k7assign()
    {
        // 快捷搜索
        !empty($_GET['StoreId']) && $map['StoreId'] =['in',$_GET['StoreId']];
        !empty($_GET['CustNo']) && $map['CustNo'] = trim(I('CustNo'));
        !empty($_GET['Mobile']) && $map['Mobile'] = trim(I('Mobile'));
        !empty($_GET['WeChat']) && $map['WeChat'] = trim(I('WeChat'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['QQ']) && $map['QQ'] = trim(I('QQ'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['salseId']) && $map['salseId'] = trim(I('salseId'));
        !empty($_GET['Opeartor']) && $map['Opeartor'] = trim(I('Opeartor'));
        !empty($_GET['DepartId']) && $map['DepartId'] = I('DepartId');
        !empty($_GET['Status']) && $map['Status'] = I('Status');
        !empty($_GET['SourceFrom']) && $map['SourceFrom'] = ['in',$_GET['SourceFrom']];
        $InsertStart = trim(I('StartInsertTime'));
        $InsertEnd = trim(I('EndInsertTime'));
        if ($InsertStart && $InsertEnd) {
            $map['InsertTime'] = ['between', array($InsertStart . ' 00:00:00', $InsertEnd . ' 23:59:59')];
        }
        !empty($_GET['CustName']) && $map['CustName'] = ['like', '%' . trim(I('CustName')) . '%'];
        $map['IsAssign'] = 0;
        $options = [
            'map' => $map,
            'field' => 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Remark,LastVisitTime,IsOrder,IsAssign'
        ];
        $this->page('wash', $options);

        $this->display();
    }

    public function expWashing()
    {
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new \PHPExcel();
        $cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
        $FieldName = ['序号', '城市品牌', '客户姓名', '咨询门店', '手机号', '微信号', '微博昵称', 'QQ号', 'QQ验证', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '接单销售', '备注'];

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

        // 快捷搜索
        !empty($_GET['StoreId']) && $map['StoreId'] = I('StoreId');
        !empty($_GET['CustNo']) && $map['CustNo'] = trim(I('CustNo'));
        !empty($_GET['Mobile']) && $map['Mobile'] = trim(I('Mobile'));
        !empty($_GET['WeChat']) && $map['WeChat'] = trim(I('WeChat'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['QQ']) && $map['QQ'] = trim(I('QQ'));
        !empty($_GET['WeiboName']) && $map['WeiboName'] = trim(I('WeiboName'));
        !empty($_GET['salseId']) && $map['salseId'] = trim(I('salseId'));
        !empty($_GET['Opeartor']) && $map['Opeartor'] = trim(I('Opeartor'));
        !empty($_GET['DepartId']) && $map['DepartId'] = I('DepartId');
        !empty($_GET['Status']) && $map['Status'] = I('Status');
        !empty($_GET['SourceFrom']) && $map['SourceFrom'] = I('SourceFrom');
        $InsertStart = trim(I('InsertStart'));
        $InsertEnd = trim(I('InsertEnd'));
        if ($InsertStart && $InsertEnd) {
            $map['InsertTime'] = ['between', array($InsertStart . ' 00:00:00', $InsertEnd . ' 23:59:59')];
        }
        !empty($_GET['CustName']) && $map['CustName'] = ['like', '%' . trim(I('CustName')) . '%'];
        //权限限制每个人用户只能看自己所在k的用户数据

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
            $map['DepartId'] = ['in', $departmentIds];
        }

        // $map1=S('mapseller'.$this->user['userid']);
        $customers = M('wash')->where($map)->select();
        // print_r($customers);.
        $User = D('User');
        $orderModel = M('order');
        $field = 'OrderNo,Money,PayMoney,OrderType,OrderPay,Remarks,CustomerType';
        foreach ($customers as $key => $val) {
            $order = $orderModel->field($field)->find($val['custid']);
            $storeName = $this->brand[$this->store[$val['storeid']]['brandid']]['brandname'] . $this->store[$val['storeid']]['storename'];
            $source = $this->source[$val['sourcefrom']];
            //录入时间不要时分秒
            $insertTime = explode(' ', $val['inserttime']);

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
     * 编辑客户信息
     *
     */
    public function editCust()
    {
        layout('Layout/win');
        $id = I('id');
        if ($id) {
            $res = M('wash')->where(['CustId' => $id])->find();
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

        $washModel = D('Wash');
        $valid = $washModel->create();

        $kid = D('User')->getDepartId($this->user['departid']);
        $washModel->Kid = $kid;
        $id = I('id');
        if ($id) {
            $washModel->LastEditUser = $this->user['userid'];
            $washModel->LastEditTime = date("Y-m-d H:i:s");
            $res = $washModel->where(['CustId' => $id])->save();
            $storeId = D('Wash')->getCustomerField($id, 'StoreId');
            if ($storeId == 65) {
                M('customerBirth')->create();
                M('customerBirth')->where(['CustId' => $id])->save();
            }
            $d['Status'] = 1;
            M('assign')->where(['CustId' => $id])->save($d);
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '修改了客户信息' . I('RealName'), 'Seller/editCust-3');
        } else {
            $data = I('post.');
            $washModel->DepartId = D('User')->getUser($_POST['salseId'], 'departid');
            $washModel->Opeartor = $this->user['userid'];
            $washModel->PdepartId = $this->user['departid'];
            $washModel->InsertTime = date("Y-m-d H:i:s");
            $washModel->Kid = $this->user['kid'];
            $res = $washModel->add();
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
            D('assign')->add($assign);
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了客户信息' . I('RealName'), 1);
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
     *添加回访
     */
    public function addVisit()
    {
        $id = I('id');
        $customer = M('wash')->where(['CustId' => $id])->find();
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
        // $data['Status'] = 1;
        $data['Kid'] = 67;
        $res1 = $CustomerModel->where(['CustId' => $data['CustId']])->save($data);
        if ($data['yuezi'] == 1) {
            M('customerBirth')->where(['CustId' => $data['CustId']])->save($data);
        }

        $res2 = $VisitModel->add($data);

        if ($res1 || $res2) {
            //如果assign里面status为0要改为1
            $assign = M('assign')->field('Status')->where(['CustId' => $_POST['CustId']])->find();
            if ($assign['status'] == 0) {
                M('assign')->where(['CustId' => $_POST['CustId']])->save(['Status' => 1]);
            }
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了客户回访信息' . I('CustName'), 1);
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
     * 编辑月子中心客户信息
     */
    public function editBCust()
    {
        $id = I('id');
        if ($id) {
            $res = M('wash')->where(['CustId' => $id])->find();
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
        $customer = M('wash')->where(['CustId' => $id])->find();
        $this->assign('customer', $customer);
        $visit = M('visit')->where(['CustId' => $customer['custid']])->select();
        $this->assign('visit', $visit);
        layout('Layout/win');
        $this->display();
    }

    public function deAssign()
    {
        $custId = I('custId');
        $assign = M('Assign')->where(['CustId' => $custId])->find();
        $this->assign('d', $assign);

        $department = D('Department')->getDepartment($this->user['departid']);
        $dpath = explode('-', $department['dpath']);
        $kid = $dpath[2];
        $map['Kid'] = $kid;
        $map['RoleId'] = 1;
        $sellers = M('user')->field('UserId,RealName')->where($map)->select();

        $this->assign('sellers', $sellers);
        layout('Layout/win');
        $this->display();
    }

    public function doEditDeAssign()
    {
        $seller = I('seller');
        $custids = I('ids');
        $custs = explode(',', $custids);

        $washModel = M('wash');
        $customerModel = M('customer');
        $d['salseId'] = $seller;
        $d['IsAssign'] = 1;
        $fileds = $customerModel->getDbFields();
        foreach ($custs as $k => $v) {
            $map['CustId'] = $v;
            $wdata = $washModel->where($map)->find();
            $wdata['custno'] = $wdata['custno'].'w';
            unset($wdata['isassign']);
            unset($wdata['custid']);

            foreach ($fileds as $kal => $val) {
                $vv = $val;
                if ($wdata[strtolower($val)]) {
                    $data[$vv] = $wdata[strtolower($val)];
                    unset($wdata[$val]);
                }
            }
            $data['Opeartor'] = $this->user['userid'];
            $data['PdepartId'] = D('User')->getUser($this->user['userid'], 'departid');;
            $data['IsWashing'] = 1;
            $data['Kid'] = 67;
            $data['IsRepeat'] = 1;
            $data['InsertTime'] = date('Y-m-d H:i:s');
            $data['salseId'] = $seller;
            $data['DepartId'] = D('User')->getUser($seller, 'departid');
            $res1 = $customerModel->add($data);
            unset($data);
            $res = $washModel->where($map)->save($d);
            //分配表
            $assign = [
                'CustId' => $v,
                'CustNo' => $wdata['custno'],
                'InitUser' => $seller,
                'NowUser' => $seller,
                'Status' => 1,
                'InsertTime' => date('Y-m-d H:i:s'),
                'UserCount' => 1,
                'DepartId' =>  D('User')->getUser($seller, 'departid'),
                'Invitor' => $this->user['userid'],
                'InvitDepart' => $this->user['departid'],
                'IsReward' => 0,
                'IsWashing' => 1,
                'AppointType' => 2,
            ];

           M('assign')->add($assign);
            unset($assign);
            operateLog($this->user['userid'], $this->user['realname'], '分配洗单客咨给客服'.$seller, 3);
        }

        if ($res && $res1) {
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

   
  

    /**
     * 平台数据
     */
    public function fineReport()
    {
        // K信息集合
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);
        $sells = D('Department')->getSellDepartment(67);

        //print_r($sells);
       // exit;
        $this->assign('sells', $sells);
        // 获取来源所在的平台
        $source = M('Source')->getField('sourceId,Platform');
        // 来源分类
        $SourceCate = ['传统平台', '微博平台', '微信平台', '官网平台', '网转介绍'];
        $this->assign('SourceCate', $SourceCate);

        // 构造检索条件
        $get = I('get.');
        $map['Del'] = 0;
        $pids = [];
        $department = D('Department')->getDepartment($get['DepartId']);
        if(!empty($department)){
            $dpath = $department['dpath'];
            $pathArr = explode('-',$dpath);
            if($pathArr[2]) {
                $kid= $pathArr[2];
            } else{
                $kid = $department['dpartid'];
            }
        }
        $tree = D('Department')->getTree($sells, $department);
        $pids = array_keys($tree);

        $get['DepartId'] > -1 && array_push($pids, $get['DepartId']);
        $get['DepartId'] > -1 && $map['DepartId'] = ['in', $pids];
        $get['StoreId'] && $map['StoreId'] = $get['StoreId'];
        $InsertStart = !empty($get['InsertStart']) ? date("Y-m-d", strtotime($get['InsertStart']) - 86400) . ' 21:00:00' : date('Y-m-d', strtotime('-1day')) . ' 21:00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ' 21:00:00' : date('Y-m-d') . ' 21:00:00';
        $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $map['SourceFrom'] = ['in', array_keys($platform)];
        }
        $_REQUEST['DepartId'] < 0 && $map['Kid'] = $this->user['kid'];
        if($kid==67){
            $map['IsWashing'] = 1;
        }

        //$map['IsWashing'] = ['neq',1];
        // 获取总咨询
        $field = 'CustId,CustNo,CustName,SourceFrom,salseId,InsertTime,Status';
        $get['tj'] && $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();
        //echo M('customer')->_sql();
        $count = [];
        // 有效咨询 状态集
        // $effective = [10,11,15,16];
        // $CustomerType = [2,3,5];

        foreach ($source as $kal => $val) {
            if ($kal == 59 || !empty($platform) && !in_array($kal, array_keys($platform))) {
                continue;
            } else {
                $count[$kal]['来源'] = $this->sources[$kal]['sourcename'];
                $count[$kal]['平台'] = $this->sources[$kal]['platform'];
            }
        }
        // print_r($count);
        ksort($count);
        foreach ($list as $k => $v) {
            $count[$v['sourcefrom']]['num']++;
            if ($v['status'] == 11) {
                ++$count[$v['sourcefrom']]['准客户'];
            } elseif ($v['status'] == 10) {
                ++$count[$v['sourcefrom']]['有效客户'];
            } elseif ($v['status'] == 15) {
                ++$count[$v['sourcefrom']]['长期有效'];
            } elseif ($v['status'] == 16) {
                ++$count[$v['sourcefrom']]['写真有效'];
            } elseif ($v['status'] == 12) {
                ++$count[$v['sourcefrom']]['无效'];
            } elseif ($v['status'] == 25) {
                ++$count[$v['sourcefrom']]['未验证'];
            } elseif ($v['status'] == 26) {
                ++$count[$v['sourcefrom']]['直接无效'];
            } elseif ($v['status'] == 0) {
                ++$count[$v['sourcefrom']]['未回访'];
            }
            if (in_array($v['status'], [11, 10, 15, 16])) {
                ++$count[$v['sourcefrom']]['有效'];
            }
            $count[$v['sourcefrom']]['有效转化率'] = sprintf('%01.2f', ($count[$v['sourcefrom']]['有效'] / $count[$v['sourcefrom']]['num']) * 100) . '%';

        }

        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 实时报表
     */
    public function realReport()
    {
        // K信息集合
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);
        $sells = D('Department')->getSellDepartment(67);
        $this->assign('sells', $sells);

        // 获取来源所在的平台
        $source = M('Source')->getField('sourceId,Platform');

        // 来源分类
        $SourceCate = ['传统平台', '微博平台', '微信平台', '官网平台', '网转介绍'];
        $this->assign('SourceCate', $SourceCate);

        // 构造检索条件
        $get = I('get.');
        $map['Del'] = 0;
        $pids = [];
        $get['DepartId'] > -1 && $department = D('Department')->getDepartment($get['DepartId']);
        if(!empty($department)){
            $dpath = $department['dpath'];
            $pathArr = explode('-',$dpath);
            if($pathArr[2]) {
                $kid= $pathArr[2];
            } else{
                $kid = $department['dpartid'];
            }
        }
        $tree = D('Department')->getTree($sells, $department);
        $pids = array_keys($tree);
        $get['DepartId'] > -1 && array_push($pids, $get['DepartId']);
        $get['DepartId'] > -1 && $map['DepartId'] = ['in', $pids];
        foreach ($sells as $k => $v) {
            $stores = explode(',', $v['stores']);
            $general[] = [
                'departid' => $v['departid'],
                'departname' => $v['departname'],
                'stores' => $stores
            ];
        }

        $InsertStart = !empty($get['InsertStart']) ? $get['InsertStart'].':00:00' : date('Y-m-d H').':00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] .':59:59': date('Y-m-d H').':59:59';
        $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        $get['StoreId'] && $map['StoreId'] = $get['StoreId'];
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $map['SourceFrom'] = ['in', array_keys($platform)];
        }

        $_REQUEST['DepartId'] < 0 && $map['Kid'] = $this->user['kid'];
        if($kid==67){
            $map['IsWashing'] = 1;
        }
        $field = 'CustId,CustNo,CustName,SourceFrom,salseId,InsertTime,Status,DepartId,StoreId,Mobile,WeChat,GotoStore';
        $get['tj'] && $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();

        //echo M('customer')->_sql();
        $count = [];
        $count1 = [];
        foreach ($list as $k => $v) {

            if (!in_array($count[$v['departid']][$v['storeid']][$v['sourcefrom']], $count[$v['departid']][$v['storeid']])) {
                ++$count1[$v['departid']][$v['storeid']]['srows'];
                ++$count1[$v['departid']]['drows'];
            }
            if ($v['mobile']) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['手机号'];

            } elseif ($v['wechat']) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['微信号'];
            }
            ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['num'];
            if ($v['gotostore'] == 2) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['进店数'];
            }
            if ($v['status'] == 11) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['准客户'];

            } elseif ($v['status'] == 10) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['有效客户'];

            } elseif ($v['status'] == 15) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['长期有效'];

            } elseif ($v['status'] == 16) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['写真有效'];

            } elseif ($v['status'] == 12) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['无效'];

            } elseif ($v['status'] == 25) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['未验证'];

            } elseif ($v['status'] == 26) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['直接无效'];

            } elseif ($v['status'] == 0) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['未回访'];
            }

        }
        $this->assign('count', $count);
        $this->assign('count1', $count1);
        $this->display();
    }

    /**
     * 销售报表
     *
     */
    public function salseReport()
    {
        // 来源分类
        $SourceCate = ['传统平台', '微博平台', '微信平台', '官网平台', '网转介绍'];
        $this->assign('SourceCate', $SourceCate);

        $sells = D('Department')->getSellDepartment(67);
        $this->assign('sells', $sells);
        // 构造检索条件
        $get = I('get.');
        $map = [];
        $salse_order_map = [];
        $salse_payMoney_map = [];
        $map['Del'] = 0;
        if (!$get['tj']) {
        }
        $department = D('Department')->getDepartment($get['DepartId']);
        if(!empty($department)){
            $dpath = $department['dpath'];
            $pathArr = explode('-',$dpath);
            if($pathArr[2]) {
                $kid= $pathArr[2];
            } else{
                $kid = $department['dpartid'];
            }
        }
        $tree = D('Department')->getTree($sells, $department);
        $pids = array_keys($tree);
        $get['DepartId'] > -1 && array_push($pids, $get['DepartId']);
        $get['DepartId'] > -1 &&$salse_payMoney_map['DepartId']=$salse_order_map['DepartId'] =$map['DepartId'] = ['in', $pids];
        $get['StoreId'] && $salse_payMoney_map['StoreId']=$salse_order_map['StoreId'] =$map['StoreId'] = $get['StoreId'];
        //if($kid==67){
            $map['IsWashing'] = 1;
        //}
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $salse_order_map['SourceFrom'] =$map['SourceFrom'] = ['in', array_keys($platform)];
        }

        $_REQUEST['DepartId'] < 0 && $salse_payMoney_map['Kid'] = $salse_order_map['Kid'] = $map['Kid'] = $this->user['kid'];

        $InsertStart = !empty($get['InsertStart']) ? date("Y-m-d", strtotime($get['InsertStart']) - 86400) . ' 21:00:00' : date('Y-m-d', strtotime('-1day')) . ' 21:00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ' 21:00:00' : date('Y-m-d') . ' 21:00:00';
        $salse_payMoney_map['OrderTime']=$salse_order_map['OrderTime'] =$map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];


        // 获取总咨询
        $field = 'CustId,CustNo,CustName,SourceFrom,salseId,InsertTime,Status,IsOrder';
        $get['tj'] && $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();
        //echo M('customer')->_sql();
        $count = [];
        $order = M('order');
        $payLog = M('payLog');
        foreach ($list as $k => $v) {
            $count[$v['salseid']]['num']++;
            if ($v['status'] == 11) {
                ++$count[$v['salseid']]['准客户'];
            } elseif ($v['status'] == 10) {
                ++$count[$v['salseid']]['有效客户'];
            } elseif ($v['status'] == 15) {
                ++$count[$v['salseid']]['长期有效'];
            } elseif ($v['status'] == 16) {
                ++$count[$v['salseid']]['写真有效'];
            } elseif ($v['status'] == 12) {
                ++$count[$v['salseid']]['无效'];
            } elseif ($v['status'] == 25) {
                ++$count[$v['salseid']]['未验证'];
            } elseif ($v['status'] == 26) {
                ++$count[$v['salseid']]['直接无效'];
            } elseif ($v['status'] == 0) {
                ++$count[$v['salseid']]['未回访'];
            }
            if (in_array($v['status'], [11, 10, 15, 16])) {
                ++$count[$v['salseid']]['有效'];
            }
            $count[$v['salseid']]['有效转化率'] = sprintf('%01.2f', ($count[$v['salseid']]['有效'] / $count[$v['salseid']]['num']) * 100) . '%';
            $count[$v['salseid']]['准客户转化率'] = sprintf('%01.2f', ($count[$v['salseid']]['准客户'] / $count[$v['salseid']]['num']) * 100) . '%';


            if(!in_array($count[$v['salseid']]['订单'],$count[$v['salseid']])){
                $salse_order_map['SellerId'] = $v['salseid'];
                $count[$v['salseid']]['订单'] = $order->where($salse_order_map)->count();
            }
            if(!in_array($count[$v['salseid']]['实收'],$count[$v['salseid']])){
                $salse_payMoney_map['SellerId'] = $v['salseid'];
                $count[$v['salseid']]['实收'] = $payLog->where($salse_payMoney_map)->sum('PayMoney');
            }

            $count[$v['salseid']]['有效订单率'] = sprintf('%01.2f', ($count[$v['salseid']]['订单'] / $count[$v['salseid']]['有效']) * 100) . '%';
        }

        $this->assign('count', $count);
        $this->display();
    }

    public function getStores()
    {
        $get = I('get.');
        $stores = D('Store')->getAllStore();

       /* // 获取门店关系
        $Deparment = D('Department');
        $departments = $Deparment->getAllDepartment();
        $department = $Deparment->getDepartment($get['department']);
        $treeDepartment = $Deparment->getTree($departments, $department);

        if(empty($treeDepartment)) {
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment  = explode(',', $val['sellids']);
                if (in_array($get['department'], $SalesDepartment)) {
                    $DepartStore[$key] = $val;
                }
            }
        } else {
            $departIds = array_keys($treeDepartment);
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                foreach($departIds as $v) {
                    if (in_array($v, $SalesDepartment)) {
                        $DepartStore[$key] = $val;
                    }
                }
            }
        }*/
        $this->assign('store', $stores);

        layout(false);
        $this->display();
    }

    /*
     * 测试函数
     */
    public function getStores2()
    {
        $get = I('get.');
        $stores = D('Store')->getAllStore();

        // 获取门店关系
        $Deparment = D('Department');
        $departments = $Deparment->getAllDepartment();
        $department = $Deparment->getDepartment($get['department']);
        $treeDepartment = $Deparment->getTree($departments, $department);

        if(empty($treeDepartment)) {
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment  = explode(',', $val['sellids']);
                if (in_array($get['department'], $SalesDepartment)) {
                    $DepartStore[$key] = $val;
                }
            }
        } else {
            $departIds = array_keys($treeDepartment);
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                foreach($departIds as $v) {
                    if (in_array($v, $SalesDepartment)) {
                        $DepartStore[$key] = $val;
                    }
                }
            }
        }
        $this->assign('store', $DepartStore);

        layout(false);
        $this->display('getStores');
    }
}