<?php
namespace KWS\Controller;

class CountController extends BaseController
{
    /**
     * 业务统计预览
     */
    public function index()
    {
        $controller = strtolower(CONTROLLER_NAME);
        $first = array_shift($this->menus[$controller]);
        sort($first);
        $this->redirect($first[0]['url']);
    }

    /**
     * 平台统计
     */
    public function platform()
    {
        ini_set("max_execution_time", 0);

        $get = I("get.");
        // 获取大区信息
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);
        if (!empty($get['ks'])) {
            $kids = $get['ks'];
        } else {
            $kids = array_column($ks, 'departid');
        }
        !empty($kids) && $map['Kid'] = ['in', $kids];
        // 获取来源所在的平台
        $data = M('Source')->getField('sourceId,sourceName,PlatForm');
        $SourceCate = [];
        $AllSource = [];

        foreach ($data as $key => $val) {
            if (!empty($get['source'])) {
                if (in_array($val['platform'], $get['source'])) {
                    $AllSource[$val['platform']][] = [
                        'sourceid' => $val['sourceid'],
                        'sourcename' => $val['sourcename']
                    ];
                }
            } else {
                $AllSource[$val['platform']][] = [
                    'sourceid' => $val['sourceid'],
                    'sourcename' => $val['sourcename']
                ];
            }
            $SourceCate[] = $val['platform'];
        }
        $SourceCate = array_unique($SourceCate);
        $this->assign('SourceCate', $SourceCate);

        // 获取所有门店
        $AllStore = [];
        if (!empty($get['store'])) {
            foreach ($this->stores as $key => $val) {

            }
        } else {
            if (in_array(12, $kids) || in_array(67, $kids)) {

                $AllStore = $this->stores;

            } else {
                foreach ($this->stores as $key => $val) {
                    if (in_array($val['departid'], $kids)) {
                        $AllStore[] = $val;
                    }
                }
            }
        }

        // 设置检索时间
        $InsertStart = date("Y-m-d 00:00:00", strtotime($get['InsertStart']));
        $InsertEnd = $get['InsertEnd'] . ' 23:59:59';
        if (empty($get['InsertStart']) || empty($get['InsertEnd'])) {
            $this->display();
            return;
        }


        // 有效咨询 状态集
        // $CustomerType = [2, 3, 5];
        $Customer = M("Customer");
        $Order = M("Order");
        $PayLog = M("PayLog");
        $Promotion = M('promotion');

        $count = [];
        foreach ($AllSource as $key1 => $source) {
            $SourceIds = array_column($source, 'sourceid');
            // print_r($SourceIds);
            // exit;
            if (empty($SourceIds)) continue;

            foreach ($AllStore as $key2 => $store) {
                $map = [];
                $map['StoreId'] = $store['storeid'];
                $map['SourceFrom'] = ['in', $SourceIds];
                $map['Source'] = ['in', $SourceIds];
                // 广告费
                $map['UtilityTime'] = ['between', [$InsertStart, $InsertEnd]];
                $charge = $Promotion->where($map)->sum('Charge');
                $count[$key1][$store['storeid']]['广告费'] = round($charge, 2);
                $count[$key1][$store['storeid']]['咨询指标'] = (int)$Promotion->where($map)->sum('CustomerNum');

                unset($map['UtilityTime']);
                $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
                if (count($kids) == 1) {
                    $map['Kid'] = $kids[0];
                } else {
                    $map['Kid'] = ['in', $kids];
                }
                $map['Del'] = 0;
                !empty($kids) && $map['Kid'] = ['in', $kids];
                // 总咨询
                $count[$key1][$store['storeid']]['总咨询'] = $Customer->where($map)->count();

                if ($count[$key1][$store['storeid']]['总咨询'] == '0' && $count[$key1][$store['storeid']]['广告费'] == '0') {
                    unset($count[$key1][$store['storeid']]);
                    continue;
                }

                // 有效咨询
                $map['Status'] = ['in', [10, 11, 15, 16]];
                $count[$key1][$store['storeid']]['有效咨询'] = $Customer->where($map)->count();
                // 入店数
                unset($map['Status']);
                //$map['GotoStore'] = 1;
                //$count[$key1][$store['storeid']]['入店数'] = $Customer->where($map)->count();


                // unset($map['GotoStore']);
                unset($map['InsertTime']);
                $map['OrderTime'] = ['between', [$InsertStart, $InsertEnd]];

                // 总订单数
                $count[$key1][$store['storeid']]['总订单数'] = $Order->where($map)->count();
                // 门店网络实收
                $count[$key1][$store['storeid']]['门店网络实收'] = $PayLog->where($map)->sum("PayMoney");

                // 店内订单数
                $map['CustomerType'] = ['in', [2, 3, 5]];
                $map['OrderTime'] = ['between', [$InsertStart, $InsertEnd]];
                $count[$key1][$store['storeid']]['店内订单数'] = $Order->where($map)->count();
                // 店内套系
                $count[$key1][$store['storeid']]['店内套系'] = $PayLog->where($map)->sum("PayMoney");

                // 网单订单数
                $map['CustomerType'] = 1;
                $count[$key1][$store['storeid']]['网单订单数'] = $Order->where($map)->count();
                // 网单套系
                $count[$key1][$store['storeid']]['网单套系'] = $PayLog->where($map)->sum("Money");
            }
        }


        $this->assign('count', $count);
        $this->display();
    }


    /**
     * 导出日报表
     */
    public function expDay()
    {
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new \PHPExcel();
        $cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
        $FieldName = ['门店', '总咨询', '未验证', '直接无效', '有效', '邀约到店', '跑单', '进店', '订单', '有效率', '有效进店', '进店咨询', '跑单率', '进店订单', '咨询订单', '套系金额', '实收金额', '套系均价'];

        $Sheet = $PHPExcel->setActiveSheetIndex(0);
        $Sheet->getDefaultRowDimension()->setRowHeight(20);
        $Sheet->setTitle('日报表');
        foreach ($FieldName as $key => $val) {
            $Sheet->setCellValue($cellName[$key] . '1', $val);
            $Fill = $Sheet->getStyle($cellName[$key] . '1');
            $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $Fill->getFill()->getStartColor()->setRGB('999999');
            $Fill->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        }

        $field = 'CustNo,CustName,StoreId,Mobile,WeChat,WeiboName,QQ,QQCode,Keywords,Status,Opeartor,SourceFrom,salseId,LastVisitTime,InsertTime,IsOrder,OrderPrice,PayPrice';
        $get = I('get.');
        isset($_GET['store']) && $map['StoreId'] = ['in', $get['store']];
        isset($_GET['source']) && $map['SourceFrom'] = ['in', $get['source']];
        $map['InsertTime'] = ['between', [$get['InsertStart'], $get['InsertEnd']]];
        $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();
        $count = [];
        foreach ($list as $k => $v) {
            ++$count[$v['sourcefrom']][$v['storeid']]['总咨询'];
            $count[$v['sourcefrom']][$v['storeid']]['未验证'] = $v['status'] == '5' ? $count[$v['sourcefrom']][$v['storeid']]['未验证'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['未验证'];
            $count[$v['sourcefrom']][$v['storeid']]['直接无效'] = $v['status'] == '6' ? $count[$v['sourcefrom']][$v['storeid']]['直接无效'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['直接无效'];
            $count[$v['sourcefrom']][$v['storeid']]['有效'] = $v['status'] == '10' ? $count[$v['sourcefrom']][$v['storeid']]['有效'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['有效'];
            $count[$v['sourcefrom']][$v['storeid']]['邀约到店'] = $v['status'] == '10' ? $count[$v['sourcefrom']][$v['storeid']]['直接无效'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['直接无效'];
            $count[$v['sourcefrom']][$v['storeid']]['跑单'] = $v['isorder'] == '0' ? $count[$v['sourcefrom']][$v['storeid']]['跑单'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['跑单'];
            $count[$v['sourcefrom']][$v['storeid']]['进店'] = $v['gotostore'] == '1' ? $count[$v['sourcefrom']][$v['storeid']]['进店'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['进店'];
            $count[$v['sourcefrom']][$v['storeid']]['订单'] = $v['isorder'] == '1' ? $count[$v['sourcefrom']][$v['storeid']]['订单'] + 1 : (int)$count[$v['sourcefrom']][$v['storeid']]['订单'];
            $count[$v['sourcefrom']][$v['storeid']]['有效率'] = percent($count[$v['sourcefrom']][$v['storeid']]['直接无效'], $count[$v['sourcefrom']][$v['storeid']]['直接无效']);
            $count[$v['sourcefrom']][$v['storeid']]['有效进店'] = percent($count[$v['sourcefrom']][$v['storeid']]['进店'], $count[$v['sourcefrom']][$v['storeid']]['有效']);
            $count[$v['sourcefrom']][$v['storeid']]['进店咨询'] = percent($count[$v['sourcefrom']][$v['storeid']]['进店'], $count[$v['sourcefrom']][$v['storeid']]['总咨询']);
            $count[$v['sourcefrom']][$v['storeid']]['跑单率'] = percent($count[$v['sourcefrom']][$v['storeid']]['跑单'], $count[$v['sourcefrom']][$v['storeid']]['总咨询']);
            $count[$v['sourcefrom']][$v['storeid']]['进店订单'] = percent($count[$v['sourcefrom']][$v['storeid']]['进店'], $count[$v['sourcefrom']][$v['storeid']]['订单']);
            $count[$v['sourcefrom']][$v['storeid']]['咨询订单'] = percent($count[$v['sourcefrom']][$v['storeid']]['订单'], $count[$v['sourcefrom']][$v['storeid']]['总咨询']);
            $count[$v['sourcefrom']][$v['storeid']]['套系金额'] = (int)$v['orderprice'] + (int)$count[$v['sourcefrom']][$v['storeid']]['套系金额'];
            $count[$v['sourcefrom']][$v['storeid']]['实收金额'] = (int)$v['payprice'] + (int)$count[$v['sourcefrom']][$v['storeid']]['实收金额'];
            $count[$v['sourcefrom']][$v['storeid']]['套系均价'] = (int)($count[$v['sourcefrom']][$v['storeid']]['实收金额'] / $count[$v['sourcefrom']][$v['storeid']]['订单']);
        }
        // print_r($count);
        // exit;

        $i = 0;
        foreach ($count as $k => $v) {
            $start = $i + 2;
            foreach ($v as $key => $val) {
                $col = $i + 2;
                $Sheet->setCellValue('A' . ($i + 2), $this->sources[$k]['sourcename']);
                $Fill = $Sheet->getStyle('A' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('B' . ($i + 2), $val['未验证']);
                $Fill = $Sheet->getStyle('B' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('C' . ($i + 2), $val['直接无效']);
                $Fill = $Sheet->getStyle('C' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('D' . ($i + 2), $val['有效']);
                $Fill = $Sheet->getStyle('D' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('E' . ($i + 2), $val['邀约到店']);
                $Fill = $Sheet->getStyle('E' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('F' . ($i + 2), $val['跑单']);
                $Fill = $Sheet->getStyle('F' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('G' . ($i + 2), $val['进店']);
                $Fill = $Sheet->getStyle('G' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('H' . ($i + 2), $val['订单']);
                $Fill = $Sheet->getStyle('H' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('I' . ($i + 2), $val['有效率']);
                $Fill = $Sheet->getStyle('I' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('J' . ($i + 2), $val['有效进店']);
                $Fill = $Sheet->getStyle('J' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('K' . ($i + 2), $val['进店咨询']);
                $Fill = $Sheet->getStyle('K' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('L' . ($i + 2), $val['跑单率']);
                $Fill = $Sheet->getStyle('L' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('M' . ($i + 2), $val['进店订单']);
                $Fill = $Sheet->getStyle('M' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('N' . ($i + 2), $val['咨询订单']);
                $Fill = $Sheet->getStyle('N' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('O' . ($i + 2), $val['套系金额']);
                $Fill = $Sheet->getStyle('O' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('P' . ($i + 2), $val['实收金额']);
                $Fill = $Sheet->getStyle('P' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $Sheet->setCellValue('Q' . ($i + 2), $val['套系均价']);
                $Fill = $Sheet->getStyle('Q' . $col);
                $Fill->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $Fill->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                ++$i;
            }

            $end = $start + count($v) - 1;
            $Sheet->mergeCells("A{$start}:A{$end}");
        }
        // $PHPExcel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
        $Sheet->getDefaultColumnDimension()->setAutoSize(true);

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="导出台账.xls"');
        header("Content-Disposition:attachment;filename=导出台账.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 台账统计
     */
    public function account()
    {

        $options = [
            'map' => $map
        ];

        $list = $this->page('Customer', $options);
        $orderModel = M('order');

        $field = 'OrderNo,Money,PayMoney,OrderType,OrderPay,Remarks,CustomerType';
        foreach ($list as $key => $val) {

            $order = $orderModel->field($field)->find($val['custid']);
            $list[$key]['orderno'] = $order['orderno'];
            $list[$key]['money'] = $order['money'];
            $list[$key]['paymoney'] = $order['paymoney'];
            $list[$key]['orderpay'] = $order['orderpay'];
            $list[$key]['ordertype'] = $order['ordertype'];
            $list[$key]['paytype'] = $order['paytype'];
            $department = D('Department')->getDepartment($val['departid']);
            $dpath = explode('-', $department['dpath']);
            $list[$key]['manage'] = $this->allDepart[$dpath[2]];
            $list[$key]['sellerManage'] = $this->allDepart[$dpath[3]];
            $list[$key]['seller'] = $this->allDepart[$val['departid']];
        }
        $this->assign('list', $list);

        if (!empty($map)) {
            F('mapAcount' . $this->user['userid'], $map, 60);
        }

        $this->display();
    }

    /**
     * 导出台账
     */
    public function expAccount()
    {
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new \PHPExcel();
        $cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
        $FieldName = ['门店', '订单号', '套系', '实收', '姓名', '联系电话', '团队', '组别', '主管', '电商客服', '微博粉丝通', '订单方式', '付款方式', '备注'];

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

        $this->user['kid'] && $map['kid'] = $this->user['kid'];
        $options = [];
        $map = S('mapAcount' . $this->user['userid']);
        $customers = M('customer')->where($map)->select();
        $User = D('User');
        $orderModel = M('order');

        $field = 'OrderNo,Money,PayMoney,OrderType,OrderPay,Remarks,CustomerType';
        foreach ($customers as $key => $val) {
            $order = $orderModel->field($field)->find($val['custid']);

            $storeName = $this->brand[$this->store[$val['storeid']]['brandid']]['brandname'] . $this->store[$val['storeid']]['storename'];
            $source = $this->source[$val['sourcefrom']];
            $user = $User->getUser($val['salseid']);
            $department = M('Department')->field('DepartId,dpath')->find($user['departid']);
            $dpath = explode('-', $department['dpath']);
            $Sheet->setCellValue('A' . ($key + 2), $storeName);
            $Sheet->getColumnDimension('A')->setAutoSize(true);
            $Sheet->setCellValue('B' . ($key + 2), $order['orderno']);
            $Sheet->getColumnDimension('B')->setAutoSize(true);
            $Sheet->setCellValue('C' . ($key + 2), $order['money']);
            $Sheet->getColumnDimension('C')->setAutoSize(true);
            $Sheet->setCellValue('D' . ($key + 2), $order['paymoney']);
            $Sheet->getColumnDimension('D')->setAutoSize(true);
            $Sheet->setCellValue('E' . ($key + 2), $val['custname']);
            $Sheet->getColumnDimension('E')->setAutoSize(true);
            $Sheet->setCellValue('F' . ($key + 2), $val['mobile']);
            $Sheet->getColumnDimension('F')->setAutoSize(true);
            $Sheet->setCellValue('G' . ($key + 2), $this->allDepart[$dpath[2]]);
            $Sheet->getColumnDimension('G')->setAutoSize(true);
            $Sheet->setCellValue('H' . ($key + 2), $this->allDepart[$dpath[3]]);
            $Sheet->getColumnDimension('H')->setAutoSize(true);
            $Sheet->setCellValue('I' . ($key + 2), $this->allDepart[$department['departid']]);
            $Sheet->getColumnDimension('I')->setAutoSize(true);
            $Sheet->setCellValue('J' . ($key + 2), $user['realname']);
            $Sheet->getColumnDimension('J')->setAutoSize(true);
            $Sheet->setCellValue('K' . ($key + 2), $source);
            $Sheet->getColumnDimension('K')->setAutoSize(true);
            $Sheet->setCellValue('L' . ($key + 2), $this->orderType[$order['ordertype']]);
            $Sheet->getColumnDimension('L')->setAutoSize(true);
            $Sheet->setCellValue('M' . ($key + 2), $this->orderPay[$order['orderpay']]);
            $Sheet->getColumnDimension('M')->setAutoSize(true);
            $Sheet->setCellValue('N' . ($key + 2), D('Visit')->getVisitRemark($val['custid']));
            $Sheet->getColumnDimension('N')->setAutoSize(true);

        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="导出台账.xls"');
        header("Content-Disposition:attachment;filename=导出台账.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 平台数据
     */
    public function fineReport()
    {
        // K信息集合
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);

        $roles = [2,6,7,8,9,10,11,12];
        if(in_array($this->user['roleid'], $roles)) {
            // 大区总裁 或者 数据
            $sells = D('Department')->getSellDepartment($this->user['kid']);
        } else {
            $sells = $this->departments;
        }
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

        if (!empty($get['tj']) && $get['DepartId'] <= 0) {
            $this->display();
            return false;
        }
        $department = D('Department')->getDepartment($get['DepartId']);
        if (!empty($department)) {
            $dpath = $department['dpath'];
            $pathArr = explode('-', $dpath);
            if ($pathArr[2]) {
                $kid = $pathArr[2];
            } else {
                $kid = $department['dpartid'];
            }
        } else {
        }
        $tree = D('Department')->getTree($sells, $department);
        $pids = array_keys($tree);
        $get['DepartId'] > -1 && array_push($pids, $get['DepartId']);
        $get['DepartId'] > -1 && $map['DepartId'] = ['in', $pids];
        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        $InsertStart = !empty($get['InsertStart']) ? date("Y-m-d", strtotime($get['InsertStart'])) . ' 00:00:00' : date('Y-m-d') . ' 00:00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';
        $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $map['SourceFrom'] = ['in', array_keys($platform)];
        }
        $_REQUEST['DepartId'] < 0 && $map['Kid'] = $this->user['kid'];
        if ($kid == 67) {
            $map['IsWashing'] = ['neq', '1'];
        }
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
            } elseif ($v['status'] == 27) {
                ++$count[$v['sourcefrom']]['有效死单'];
            } elseif ($v['status'] == 28) {

                ++$count[$v['sourcefrom']]['写真无效'];

            } elseif ($v['status'] == 0) {
                ++$count[$v['sourcefrom']]['未回访'];
            }
            if (in_array($v['status'], [11, 10, 15, 16, 27])) {
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

        $roles = [2,6,7,8,9,10,11,12];
        if(in_array($this->user['roleid'], $roles)) {
            // 大区总裁 或者 数据
            $sells = D('Department')->getSellDepartment($this->user['kid']);
        } else {
            $sells = $this->departments;
        }
        $this->assign('sells', $sells);

        // 获取来源所在的平台
        $source = M('Source')->getField('sourceId,Platform');

        // 来源分类
        $SourceCate = ['传统平台', '微博平台', '微信平台', '官网平台', '网转介绍'];
        $this->assign('SourceCate', $SourceCate);

        // 构造检索条件
        $get = I('get.');
        if (!empty($get['tj']) && $get['DepartId'] <= 0) {
            $this->display();
            return false;
        }
        $map['Del'] = 0;
        $pids = [];
        $get['DepartId'] > -1 && $department = D('Department')->getDepartment($get['DepartId']);
        if (!empty($department)) {
            $dpath = $department['dpath'];
            $pathArr = explode('-', $dpath);
            if ($pathArr[2]) {
                $kid = $pathArr[2];
            } else {
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

        $InsertStart = !empty($get['InsertStart']) ? $get['InsertStart'] . ':00:00' : date('Y-m-d H') . ':00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ':59:59' : date('Y-m-d H') . ':59:59';
        $goto_store_map['PresetTime'] = $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        $OrderTimeStart = !empty($get['InsertStart']) ? $get['InsertStart'] . ':00:00' : date('Y-m-d 00:00:00');
        $OrderTimeEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ':59:59' : date('Y-m-d 23:59:59');
        $depart_order_map['OrderTime'] = ['between', [$OrderTimeStart, $OrderTimeEnd]];
        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $map['SourceFrom'] = ['in', array_keys($platform)];
        }

        $_REQUEST['DepartId'] < 0 && $map['Kid'] = $this->user['kid'];
        if ($kid == 67) {
            $map['IsWashing'] = ['neq', '1'];
        }
        $field = 'CustId,CustNo,CustName,SourceFrom,salseId,InsertTime,Status,DepartId,StoreId,Mobile,WeChat,GotoStore';
        $get['tj'] && $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();

        //echo M('customer')->_sql();
        $count = [];
        $count1 = [];
        $order = M('order');
        $GotoStore = M('GotoStore');
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

            } elseif ($v['status'] == 27) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['有效死单'];

            } elseif ($v['status'] == 28) {

                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['写真无效'];

            } elseif ($v['status'] == 0) {
                ++$count[$v['departid']][$v['storeid']][$v['sourcefrom']]['未回访'];
            }

            if (!in_array($count[$v['departid']][$v['storeid']][$v['sourcefrom']]['进店数'], $count[$v['departid']][$v['storeid']][$v['sourcefrom']])) {
                $v['storeid']&&$goto_store_map['StoreId'] = $v['storeid'];
                $v['sourcefrom']&&$goto_store_map['SourceFrom'] = $v['sourcefrom'];
                $v['departid']&&$goto_store_map['DepartId'] = $v['departid'];
                $count[$v['departid']][$v['storeid']][$v['sourcefrom']]['进店数'] = $GotoStore->where($goto_store_map)->count();

            }
            //订单数
            /* if (!in_array($count[$v['departid']][$v['storeid']][$v['sourcefrom']]['订单数'], $count[$v['departid']][$v['storeid']][$v['sourcefrom']])) {
                 $v['storeid']&&$depart_order_map['StoreId'] = $v['storeid'];
                 $v['sourcefrom']&&$depart_order_map['SourceFrom'] = $v['sourcefrom'];
                 $v['sourcefrom']&&$depart_order_map['DepartId'] = $v['departid'];
                 $depart_order_map['OrderType'] = ['neq',5];
                 $count[$v['departid']][$v['storeid']][$v['sourcefrom']]['订单数'] = $order->where($depart_order_map)->count();
                 if($v['storeid']==52){
                     $sql = $order->_sql();
                     file_put_contents('123.txt',$v['storeid'].$sql.'\n');
                 }
             }*/

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

        $roles = [2,6,7,8,9,10,11,12];
        if(in_array($this->user['roleid'], $roles)) {
            // 大区总裁 或者 数据
            $sells = D('Department')->getSellDepartment($this->user['kid']);
        } else {
            $sells = $this->departments;
        }
        $this->assign('sells', $sells);

        // 构造检索条件
        $get = I('get.');
        $map = [];
        $salse_order_map = [];
        $salse_payMoney_map = [];
        $map['Del'] = 0;
        if (!empty($get['tj']) && $get['DepartId'] <= 0) {
            $this->display();
            return false;
        }

        $department = D('Department')->getDepartment($get['DepartId']);
        if (!empty($department)) {
            $dpath = $department['dpath'];
            $pathArr = explode('-', $dpath);
            if ($pathArr[2]) {
                $kid = $pathArr[2];
            } else {
                $kid = $department['dpartid'];
            }
        }
        $tree = D('Department')->getTree($sells, $department);
        $pids = array_keys($tree);
        $get['DepartId'] > -1 && array_push($pids, $get['DepartId']);
        //file_put_contents("321321.txt", implode('|', $pids));
        if ($get['DepartId'] > -1) {
            $salse_payMoney_map['DepartId'] = $map['DepartId'] = ['in', $pids];
            $salse_order_map['DepartId'] = $map['DepartId'] = ['in', $pids];
        }
        !empty($get['StoreId']) && $salse_payMoney_map['StoreId'] = $salse_order_map['StoreId'] = $map['StoreId'] = ['in', $get['StoreId']];
        if ($kid == 67) {
            $map['IsWashing'] = ['neq', '1'];
        }
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $salse_order_map['SourceFrom'] = $salse_payMoney_map['Source'] = $map['SourceFrom'] = ['in', array_keys($platform)];
        }

        $_REQUEST['DepartId'] < 0 && $salse_payMoney_map['Kid'] = $salse_order_map['Kid'] = $map['Kid'] = $this->user['kid'];

        $InsertStart = !empty($get['InsertStart']) ? date("Y-m-d 00:00:00", strtotime($get['InsertStart'])) : date('Y-m-d 00:00:00');
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ' 23:59:59' : date('Y-m-d 23:59:59');
        $OrderTimeStart = !empty($get['InsertStart']) ? date("Y-m-d 00:00:00", strtotime($get['InsertStart'])) : date('Y-m-d 00:00:00');
        $OrderTimeEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ' 23:59:59' : date('Y-m-d 23:59:59');
        $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        $salse_payMoney_map['OrderTime'] = $salse_order_map['OrderTime'] = ['between', [$OrderTimeStart, $OrderTimeEnd]];

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
            } elseif ($v['status'] == 27) {
                ++$count[$v['salseid']]['有效死单'];
            } elseif ($v['status'] == 28) {
                ++$count[$v['salseid']]['写真无效'];
            } elseif ($v['status'] == 0) {
                ++$count[$v['salseid']]['未回访'];
            }
            if (in_array($v['status'], [11, 10, 15, 16, 27])) {
                ++$count[$v['salseid']]['有效'];
            }
            $count[$v['salseid']]['有效转化率'] = sprintf('%01.2f', ($count[$v['salseid']]['有效'] / $count[$v['salseid']]['num']) * 100) . '%';
            $count[$v['salseid']]['准客户转化率'] = sprintf('%01.2f', ($count[$v['salseid']]['准客户'] / $count[$v['salseid']]['num']) * 100) . '%';
            if (!in_array($count[$v['salseid']]['实收'], $count[$v['salseid']])) {
                $salse_payMoney_map['SellerId'] = $v['salseid'];
                $salse_payMoney_map['OrderType'] = ['neq', 5];
                $count[$v['salseid']]['实收'] = $payLog->where($salse_payMoney_map)->sum('PayMoney');

                $salse_payMoney_map['OrderType'] = 5;
                $tkArr = $payLog->field('PayMoney')->where($salse_payMoney_map)->select();
                foreach ($tkArr as $kat => $vat) {
                    if (abs($vat['paymoney'])) {
                        $count[$v['salseid']]['退款'] = $count[$v['salseid']]['退款'] + abs($vat['paymoney']);
                    }
                }

                $count[$v['salseid']]['实收'] = $count[$v['salseid']]['实收'] - $count[$v['salseid']]['退款'];
                unset($count[$v['salseid']]['退款']);
                unset($salse_payMoney_map['OrderType']);

            }
            if (!in_array($count[$v['salseid']]['订单'], $count[$v['salseid']])) {
                $salse_order_map['SellerId'] = $v['salseid'];
                !empty($pids) && $salse_order_map['DepartId'] = ['in', $pids];
                $salse_order_map['OrderType'] = ['not in', [3,5]];
                $count[$v['salseid']]['订单'] = $order->where($salse_order_map)->count();
                unset($salse_order_map['OrderType']);
                $salse_order_map['OrderType'] = 6;
                $count[$v['salseid']]['非订单尾款'] = $order->where($salse_order_map)->count();
                $count[$v['salseid']]['订单'] = $count[$v['salseid']]['订单'] - $count[$v['salseid']]['非订单尾款'];
            }
            $count[$v['salseid']]['有效订单率'] = sprintf('%01.2f', ($count[$v['salseid']]['订单'] / $count[$v['salseid']]['有效']) * 100) . '%';
        }


        $this->assign('count', $count);

        $this->display();
    }

    /*
     * 门店数据
     */
    public function storeReport()
    {
        // K信息集合
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);

        $roles = [2,6,7,8,9,10,11,12];
        if(in_array($this->user['roleid'], $roles)) {
            // 大区总裁 或者 数据
            $sells = D('Department')->getSellDepartment($this->user['kid']);
        } else {
            $sells = $this->departments;
        }
        $this->assign('sells', $sells);

        // 获取来源所在的平台
        $source = M('Source')->getField('sourceId,Platform');

        // 来源分类
        $SourceCate = ['传统平台', '微博平台', '微信平台', '官网平台', '网转介绍'];
        $this->assign('SourceCate', $SourceCate);
        $map = [];
        $stores_order_map = [];
        $stores_promotion_map = [];
        $all_promotion_map = [];
        // 构造检索条件
        $get = I('get.');
        if (!empty($get['tj']) && $get['DepartId'] <= 0) {//部门必选
            return false;
        }
        $map['Del'] = 0;
        $pids = [];
        $get['DepartId'] > -1 && $department = D('Department')->getDepartment($get['DepartId']);
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

        $InsertStart = !empty($get['InsertStart']) ? $get['InsertStart'] . ':00:00' : date('Y-m-d H') . ':00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ':59:59' : date('Y-m-d H') . ':59:59';
        $goto_store_map['PresetTime'] = $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        $OrderTimeStart = !empty($get['InsertStart']) ? $get['InsertStart'] . ':00:00' : date('Y-m-d H').':00:00';
        $OrderTimeEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ':59:59' : date('Y-m-d H').':59:59';
        $stores_order_map['OrderTime'] = ['between', [$OrderTimeStart, $OrderTimeEnd]];
        $stores_promotion_map['UtilityTime'] = ['between', [$InsertStart, $InsertEnd]];
        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $map['SourceFrom'] = ['in', array_keys($platform)];
        }
        //获取kid
        $department = D('Department')->getDepartment($get['DepartId']);
        $dpath = explode('-', $department['dpath']);
        $kid = isset($dpath[2]) ? $dpath[2] : 0;
        //$kid && $map['Kid'] = $kid;
        // $map['IsWashing'] = ['neq',1];
        $field = 'CustId,CustNo,CustName,SourceFrom,salseId,InsertTime,Status,DepartId,StoreId,Mobile,WeChat,GotoStore';
        $get['tj'] && $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();

        $count = [];
        $count1 = [];
        $order = M('order');
        $payLog = M('payLog');
        $promotion = M('promotion');
        $GotoStore = M('GotoStore');
        foreach ($list as $k => $v) {

            if (!in_array($count[$v['storeid']][$v['sourcefrom']], $count[$v['storeid']])) {
                ++$count1[$v['storeid']]['srows'];
                ++$count1['drows'];
            }
            if ($v['mobile']) {
                ++$count[$v['storeid']][$v['sourcefrom']]['手机号'];

            } elseif ($v['wechat']) {
                ++$count[$v['storeid']][$v['sourcefrom']]['微信号'];
            }
            ++$count[$v['storeid']][$v['sourcefrom']]['num'];
            if ($v['gotostore'] == 2) {
                ++$count[$v['storeid']][$v['sourcefrom']]['进店数'];
            }
            if ($v['status'] == 11) {

                ++$count[$v['storeid']][$v['sourcefrom']]['准客户'];

            } elseif ($v['status'] == 10) {

                ++$count[$v['storeid']][$v['sourcefrom']]['有效客户'];

            } elseif ($v['status'] == 15) {

                ++$count[$v['storeid']][$v['sourcefrom']]['长期有效'];

            } elseif ($v['status'] == 16) {

                ++$count[$v['storeid']][$v['sourcefrom']]['写真有效'];

            } elseif ($v['status'] == 12) {
                ++$count[$v['storeid']][$v['sourcefrom']]['无效'];

            } elseif ($v['status'] == 25) {
                ++$count[$v['storeid']][$v['sourcefrom']]['未验证'];

            } elseif ($v['status'] == 26) {

                ++$count[$v['storeid']][$v['sourcefrom']]['直接无效'];

            } elseif ($v['status'] == 27) {

                ++$count[$v['storeid']][$v['sourcefrom']]['有效死单'];

            } elseif ($v['status'] == 28) {

                ++$count[$v['storeid']][$v['sourcefrom']]['写真无效'];

            } elseif ($v['status'] == 0) {
                ++$count[$v['storeid']][$v['sourcefrom']]['未回访'];
            }

            //订单数
            if (!in_array($count[$v['storeid']][$v['sourcefrom']]['订单数'], $count[$v['storeid']][$v['sourcefrom']])) {
                $v['storeid'] && $stores_order_map['StoreId'] = $v['storeid'];
                $v['sourcefrom'] && $stores_order_map['SourceFrom'] = $v['sourcefrom'];
                !empty($pids) && $stores_order_map['DepartId'] = ['in', $pids];
                $stores_order_map['OrderType'] = ['not in', [3,5]];
                $count[$v['storeid']][$v['sourcefrom']]['订单数'] = $order->where($stores_order_map)->count();
                unset($stores_order_map['OrderType']);
                $stores_order_map['OrderType'] = 6;
                $count[$v['storeid']][$v['sourcefrom']]['非订单尾款数'] = $order->where($stores_order_map)->count();
                $count[$v['storeid']][$v['sourcefrom']]['订单数'] = $count[$v['storeid']][$v['sourcefrom']]['订单数'] - $count[$v['storeid']][$v['sourcefrom']]['非订单尾款数'];
            }

            //广告费
            if (!in_array($count[$v['storeid']][$v['sourcefrom']]['广告费'], $count[$v['storeid']])) {
                $stores_promotion_map['StoreId'] = $v['storeid'];
                $stores_promotion_map['SourceFrom'] = $v['sourcefrom'];

                $plist = $promotion->where($stores_promotion_map)->sum('charge');

                //$plist1 = array_column($plist,'charge');
                $count[$v['storeid']][$v['sourcefrom']]['广告费'] = round($plist, 2);
            }

            //进店数
            if (!in_array($count[$v['storeid']][$v['sourcefrom']]['进店数'], $count[$v['storeid']])) {
                $goto_store_map['StoreId'] = $v['storeid'];
                $goto_store_map['SourceFrom'] = $v['sourcefrom'];
                $goto_store_num = $GotoStore->where($goto_store_map)->count();
                $count[$v['storeid']][$v['sourcefrom']]['进店数'] = $goto_store_num;
            }

        }
        unset($plist);
        unset($list);
        unset($goto_store_num);
        $Source_map = [];
        if (!empty($get['source'])) {
            $Source_map['Platform'] = ['in', $get['source']];
        }
        $data = [];
        $data = M('Source')->where($Source_map)->getField('sourceId,sourceName,PlatForm');

        $all_promotion_map['UtilityTime'] = $stores_promotion_map['UtilityTime'];
        $kid && $all_promotion_map['Kid'] = $kid;
        foreach ($get['StoreId'] as $val) {
            foreach ($data as $k => $v) {
                $rs = [];
                if (!in_array($count[$val][$v['sourceid']], $count[$val])) {
                    $all_promotion_map['StoreId'] = $val;
                    $all_promotion_map['SourceFrom'] = $v['sourceid'];
                    $rs = $promotion->where($all_promotion_map)->sum('charge');


                    if ($rs) {
                        $count[$val][$v['sourceid']]['广告费'] = round($rs, 2);
                        ++$count1[$val]['srows'];
                    }

                } elseif ($val && !in_array($count[$val], $count)) {
                    $rs1 = [];
                    $all_promotion_map['StoreId'] = $val;
                    $all_promotion_map['SourceFrom'] = $v['sourceid'];
                    $rs1 = $promotion->where($all_promotion_map)->sum('charge');
                    if ($rs1) {
                        $count[$val][$v['sourceid']]['广告费'] = round($rs1, 3);
                        ++$count1[$val]['srows'];
                    }
                }
            }

        }


        $this->assign('count', $count);
        $this->assign('count1', $count1);
        $this->display();
    }

    /**
     * public function getStores()
     * {
     * $get = I('get.');
     * $department = D('Department')->getDepartment($get['department']);
     * $sells = D('Department')->getSellDepartment($this->user['kid']);
     * $tree = D('Department')->getTree($sells, $department);
     * $tree = array_keys($tree);
     *
     * $get['department'] && array_push($tree, $get['department']);
     * $tree = array_unique($tree);
     *
     * // 通过部门获取
     * $dStores = '';
     * if (isset($get['department'])) {
     * foreach ($tree as $k => $v) {
     * $stores = M('Department')->where(['DepartId' => $v])->getField('Stores');
     * $dStores = $dStores . $stores . ',';
     * }
     * $storeIds = explode(',', $dStores);
     * $storeIds = array_filter($storeIds);
     * $storeIds = array_unique($storeIds);
     *
     * $map['StoreId'] = ['in', $storeIds];
     * }
     *
     * $store = M('store')->where($map)->order('BrandId')->getField('StoreId,StoreName,BrandId,DepartId');
     * $this->assign('store', $store);
     *
     * layout(false);
     * $this->display();
     * }
     **/


    public function getStores()
    {
        $get = I('get.');
        $stores = D('Store')->getAllStore();

        // 获取门店关系
        $Deparment = D('Department');
        $departments = $Deparment->getAllDepartment();
        $department = $Deparment->getDepartment($get['department']);
        $treeDepartment = $Deparment->getTree($departments, $department);

        if (empty($treeDepartment)) {
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                if (in_array($get['department'], $SalesDepartment)) {
                    $DepartStore[$key] = $val;
                }
            }
        } else {
            $departIds = array_keys($treeDepartment);
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                foreach ($departIds as $v) {
                    if (in_array($v, $SalesDepartment)) {
                        $DepartStore[$key] = $val;
                    }
                }
            }
        }
        foreach ($DepartStore as $k1 => $v1) {
            if ($v1['business'] == 'photo') {
                $dStores['婚纱摄影'][] = $v1;
            } elseif ($v1['business'] == 'hall') {
                $dStores['婚礼堂'][] = $v1;
            } elseif ($v1['business'] == 'baby') {
                $dStores['宝宝摄影'][] = $v1;
            } elseif ($v1['business'] == 'wedding') {
                $dStores['婚纱礼服'][] = $v1;
            } elseif ($v1['business'] == 'dress') {
                $dStores['男士西装'][] = $v1;
            } elseif ($v1['business'] == 'birth') {
                $dStores['月子中心'][] = $v1;
            }
        }

        $this->assign('store', $dStores);

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

        if (empty($treeDepartment)) {
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                if (in_array($get['department'], $SalesDepartment)) {
                    $DepartStore[$key] = $val;
                }
            }
        } else {
            $departIds = array_keys($treeDepartment);
            $DepartStore = [];
            foreach ($stores as $key => $val) {
                $SalesDepartment = explode(',', $val['sellids']);
                foreach ($departIds as $v) {
                    if (in_array($v, $SalesDepartment)) {
                        $DepartStore[$key] = $val;
                    }
                }
            }
        }


        //$this->assign('store', $dStores);

        $this->assign('store', $DepartStore);

        layout(false);
        $this->display('getStores');
    }

}