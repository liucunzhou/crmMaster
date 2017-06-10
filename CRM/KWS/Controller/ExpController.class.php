<?php
namespace KWS\Controller;
use Think\Upload;
/**
 * Class SellerController
 * 客服销售人员相关功能
 * @package Admin\Controller
 */
class ExpController extends BaseController
{
    protected $customerData = [];

    public function index(){
        $num =S('expFile-seller'.$this->user['userid']);
        echo $num;
        $this->display();
    }
    public function ajaxExp(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map = [];
        $endId = I('endId');
        if(!$endId){
            return false;
        }
        $listNum = I('listNum');
        $endId = $endId;
        $listNum = $listNum?$listNum:1000;
        $customerModel = M('customer');
        $map['Kid'] = 4;
        $count = $customerModel->where($map)->count();
        $field = 'CustId,CustNo,Company,CustName,StoreId,Mobile,Wechat,WeiboName,QQ,QQCode,Keywords,Status,IsOrder,InsertTime,LastVisitTime,SourceFrom,Opeartor';
        $map['CustId'] = ['gt',$endId];
        $customers = $customerModel->field($field)->where($map)->limit($listNum)->select();
        $data = [];
        $data1 = $customers;
        $num = S('expNum-test');
        $num = $num + count($customers) + I('num');
        S('expNum-test',$num);
        $lastData = array_pop($data1);

        $filename = S('expFile-test');
        if(!$filename){
            $filename ='./expfile/customer-' . $this->user['accountname'] . '-' . date('YmdHis') . '.csv';
            S('expFile-test',$filename);
        }


        if(!empty($customers)){
            $output = fopen($filename,'a') or die('can not open'.$filename);

            $FieldName = ['城市品牌', '客户姓名', '咨询门店', '手机号', '微信号', '微博昵称', 'QQ号', 'QQ验证', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '接单销售'];
            foreach ($FieldName as $key => $val) {
                $FieldName[$key] = iconv('utf-8', 'gbk', $val);
            }

            $flush = 0;
            $limit = 1000;
            fputcsv($output, $FieldName);
            foreach ($customers as $key => $val) {
                $data['Company'] = iconv('utf-8', 'gbk', $val['company']);
                $data['CustName'] = iconv('utf-8', 'gbk', $val['CustName']);
                $data['StoreId'] = iconv('utf-8', 'gbk', $this->brands[$this->stores[$val['storeid']]['brandid']]['brandname'] . $this->stores[$val['storeid']]['storename']);
                $data['Mobile'] = iconv('utf-8', 'gbk', $val['mobile']);
                $data['Wechat'] = iconv('utf-8', 'gbk', $val['wechat']);
                $data['WeiboName'] = iconv('utf-8', 'gbk', $val['weiboname']);
                $data['QQ'] = iconv('utf-8', 'gbk', $val['qq']);
                $data['QQCode'] = iconv('utf-8', 'gbk', $val['qqcode']);
                $data['Keywords'] = iconv('utf-8', 'gbk', $val['keywords']);
                $data['AssignStatus'] = iconv('utf-8', 'gbk', D('Assign')->getFieldInfo($val['custno'], 'status'));
                $data['Status'] = iconv('utf-8', 'gbk', $this->status[$val['status']]);
                $data['VisitTimes'] = iconv('utf-8', 'gbk', D('Customer')->getVisitTimes($val['custno']));
                $data['IsOrder'] = iconv('utf-8', 'gbk', $val['isorder'] ? '是' : '否');
                $time = explode(' ', $val['inserttime']);
                $data['InsertTime'] = $time[0];
                $time = explode(' ', $val['lastvisittime']);
                $data['LastVisitTime'] = $time[0];
                $data['SourceFrom'] = iconv('utf-8', 'gbk', $this->sources[$val['sourcefrom']]['sourcename']);
                $data['Opeartor'] = iconv('utf-8', 'gbk', D('User')->getUser($val['opeartor'], 'realname'));
                $data['Seller'] = iconv('utf-8', 'gbk', D('User')->getUser($val['salseid'], 'realname'));
                fputcsv($output, $data);

                $flush++;
                if ($limit == $flush) {
                    ob_flush();
                    flush();
                    $flush = 0;
                }
            }

            fclose($output) or die("can not close");
            unset($customers);
            unset($data1);
            $this->ajaxReturn([
                'num' => $num,
                'endId' => $lastData['custid'],
                'listNum' => 1000,
                'count' => $count,

            ]);
        }else{
            //$this->outDown($customers);
            $fileExp = $filename;
            unset($filename);
            unset($num);
            S('expFile-test',null);
            S('expNum-test',0);
            $this->ajaxReturn([
                'num' => 0,
                'endId' => $lastData['custid'],
                'listNum' => 1000,
                'count' => $count,
                'expUrl'=> $fileExp
            ]);

        }
    }

    public function outDown($customers)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filename = '客资-' . $this->user['realname'] . '-' . date('YmdHis') . '.csv';

        // 导出cvs
         header('Content-Type: application/csv');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        if(empty($customers)){
          $file = file_get_contents($filename);
            echo $file;
        }
        $fiveMBs = 512*1024*1024;
        $output = fopen("php://temp/maxmemory:$fiveMBs", 'a') or die('can not open');

        $FieldName = ['城市品牌', '客户姓名', '咨询门店', '手机号', '微信号', '微博昵称', 'QQ号', 'QQ验证', '关键字', '接受状态', '回访状态', '回访次数', '订单', '录入时间', '最后回访时间', '来源', '邀约手', '接单销售'];
        foreach ($FieldName as $key => $val) {
            $FieldName[$key] = iconv('utf-8', 'gbk', $val);
        }

        $flush = 0;
        $limit = 1000;
        fputcsv($output, $FieldName);
        foreach ($customers as $key => $val) {
            $data['Company'] = iconv('utf-8', 'gbk', $val['company']);
            $data['CustName'] = iconv('utf-8', 'gbk', $val['CustName']);
            $data['StoreId'] = iconv('utf-8', 'gbk', $this->brands[$this->stores[$val['storeid']]['brandid']]['brandname'] . $this->stores[$val['storeid']]['storename']);
            $data['Mobile'] = iconv('utf-8', 'gbk', $val['mobile']);
            $data['Wechat'] = iconv('utf-8', 'gbk', $val['wechat']);
            $data['WeiboName'] = iconv('utf-8', 'gbk', $val['weiboname']);
            $data['QQ'] = iconv('utf-8', 'gbk', $val['qq']);
            $data['QQCode'] = iconv('utf-8', 'gbk', $val['qqcode']);
            $data['Keywords'] = iconv('utf-8', 'gbk', $val['keywords']);
            $data['AssignStatus'] = iconv('utf-8', 'gbk', D('Assign')->getFieldInfo($val['custno'], 'status'));
            $data['Status'] = iconv('utf-8', 'gbk', $this->status[$val['status']]);
            $data['VisitTimes'] = iconv('utf-8', 'gbk', D('Customer')->getVisitTimes($val['custno']));
            $data['IsOrder'] = iconv('utf-8', 'gbk', $val['isorder'] ? '是' : '否');
            $time = explode(' ', $val['inserttime']);
            $data['InsertTime'] = $time[0];
            $time = explode(' ', $val['lastvisittime']);
            $data['LastVisitTime'] = $time[0];
            $data['SourceFrom'] = iconv('utf-8', 'gbk', $this->sources[$val['sourcefrom']]['sourcename']);
            $data['Opeartor'] = iconv('utf-8', 'gbk', D('User')->getUser($val['opeartor'], 'realname'));
            $data['Seller'] = iconv('utf-8', 'gbk', D('User')->getUser($val['salseid'], 'realname'));
            fputcsv($output, $data);

            $flush++;
            if ($limit == $flush) {
                ob_flush();
                flush();
                $flush = 0;
            }
        }

        fclose($output) or die("can not close");

    }
    public function out(){
        $ksellers1 = D('Department')->getKSellers($this->user['kid']);
        print_r($ksellers1);
        exit;
        header('Content-Type:application/csv');
        header('Content-Disposition: attachment;filename="' . 'csv.csv' . '"');
        $fp = fopen('csv.csv','a');
        //$fieldName = 'csv.csv';
        $data_arr = ['a','b','c'];
        foreach($data_arr as $key=>$val){
            $data_arr[$key] = iconv('utf-8', 'gbk', $val);

        }
        fwrite($fp,$data_arr);
        fclose($fp);
    }

    public function platform()
    {
        set_time_limit(0);
        ini_set("max_execution_time", "600");
       // ini_set('memory_limit', '512M');
        // K信息集合
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);

        // 获取来源所在的平台
        $source = M('Source')->getField('sourceId,PlatForm');

        // 来源分类
        $SourceCate = ['传统平台', '微博平台', '微信平台', '官网平台', '天猫平台', '网转介绍', '其他平台'];
        $this->assign('SourceCate', $SourceCate);

        // 构造检索条件
        $get = I('get.');
        !empty($get['store']) && $map['StoreId'] = ['in', $get['store']];
        $InsertStart = !empty($get['InsertStart']) ? date("Y-m-d", strtotime($get['InsertStart']) - 86400) . ' 21:00:00' : date('Y-m-d', strtotime('-1day')) . ' 21:00:00';
        $InsertEnd = !empty($get['InsertEnd']) ? $get['InsertEnd'] . ' 21:00:00' : date('Y-m-d') . ' 21:00:00';
        $map['InsertTime'] = ['between', [$InsertStart, $InsertEnd]];
        if (!empty($get['source'])) {
            $platform = M('source')->where(['Platform' => ['in', $get['source']]])->getField('sourceId, sourceName');
            $map['SourceFrom'] = ['in', array_keys($platform)];
        }
        $this->user['kid'] && $map['Kid'] = $this->user['kid'];
        $map['Del'] = 0;
        if (empty($get['InsertStart']) || empty($get['InsertEnd'])) {
            $this->display();
            return;
        }
        // 获取总咨询
        $field = 'CustId,CustNo,CustName,StoreId,Mobile,WeChat,WeiboName,QQ,QQCode,Keywords,Status,Opeartor,SourceFrom,salseId,LastVisitTime,InsertTime,IsOrder,OrderPrice,PayPrice,GotoStore';
        $list = M('customer')->field($field)->where($map)->order('InsertTime desc')->select();
        //print_r($list);
       // exit;
        //echo M('customer')->_sql();
        $count = [];
        // 有效咨询 状态集
        $effective = [10, 11, 15, 16];
        $CustomerType = [2, 3, 5];
        $Order = M('order');
        $PayLog = M('PayLog');
        $Promotion = M('promotion');

        foreach ($list as $k => $v) {
            $platform = empty($source[$v['sourcefrom']]) ? '未录入' : $source[$v['sourcefrom']];
            ++$count[$platform][$v['storeid']]['总咨询'];
            $count[$platform][$v['storeid']]['来源'][] = $v['sourcefrom'];
            $mapPromotion['StoreId'] = $v['storeid'];
            $mapPromotion['SourceFrom'] = $v['sourcefrom'];
            $mapPromotion['UtilityTime'] = $v['inserttime'];
            //$adver = [];
            //$adver = $Promotion->field('Charge,CustomerNum')->where($mapPromotion)->find();

            // 有效咨询
            if (in_array($v['status'], $effective)) {
                $count[$platform][$v['storeid']]['有效咨询'] = $count[$platform][$v['storeid']]['有效咨询'] + 1;
            } else {
                $count[$platform][$v['storeid']]['有效咨询'] = $count[$platform][$v['storeid']]['有效咨询'] + 0;
            }
            $count[$platform][$v['storeid']]['有效咨询率'] = percent($count[$platform][$v['storeid']]['有效咨询'], $count[$platform][$v['storeid']]['总咨询']);

            $order = $Order->field('OrderType,Money,PayMoney,CustomerType')->where(['CustId' => $v['custid']])->order('OrderTime desc')->find();

            if ($v['gotostore'] == 2) {
                $count[$platform][$v['storeid']]['入店数'] = $count[$platform][$v['storeid']]['入店数'] + 1;
            } else {
                $count[$platform][$v['storeid']]['入店数'] = $count[$platform][$v['storeid']]['入店数'] + 0;
            }

            $count[$platform][$v['storeid']]['入店率'] = percent($count[$platform][$v['storeid']]['入店数'], $count[$platform][$v['storeid']]['有效咨询']);
            // 店内订单数
            if (in_array($order['customertype'], $CustomerType)) {
                $count[$platform][$v['storeid']]['店内订单数'] = $count[$platform][$v['storeid']]['店内订单数'] + 1;
            } else {
                $count[$platform][$v['storeid']]['店内订单数'] = $count[$platform][$v['storeid']]['店内订单数'] + 0;
            }

            // 网单订单数
            if ($order['customertype'] == 1) {
                $count[$platform][$v['storeid']]['网单订单数'] = $count[$platform][$v['storeid']]['网单订单数'] + 1;
            } else {
                $count[$platform][$v['storeid']]['网单订单数'] = $count[$platform][$v['storeid']]['网单订单数'] + 0;
            }

            $count[$platform][$v['storeid']]['总订单数'] = $count[$platform][$v['storeid']]['店内订单数'] + $count[$platform][$v['storeid']]['网单订单数'];
            $count[$platform][$v['storeid']]['咨询到订单比率'] = percent($count[$platform][$v['storeid']]['总订单数'], $count[$platform][$v['storeid']]['总咨询']);
            $count[$platform][$v['storeid']]['入店订单率'] = percent($count[$platform][$v['storeid']]['店内订单数'], $count[$platform][$v['storeid']]['入店数']);
            $count[$platform][$v['storeid']]['平均入店成本'] = 0;
            $count[$platform][$v['storeid']]['平均订单成本'] = 0;

            // 门店网络实收
            $PayMoney = $PayLog->where(['CustId' => $v['custid'], 'OrderTime' => ['between', [$InsertStart, $InsertEnd]]])->sum('PayMoney');
            if (!empty($PayMoney)) {
                $count[$platform][$v['storeid']]['门店网络实收'] = $count[$platform][$v['storeid']]['门店网络实收'] + $PayMoney;
            } else {
                $count[$platform][$v['storeid']]['门店网络实收'] = $count[$platform][$v['storeid']]['门店网络实收'] + 0;
            }

            $count[$platform][$v['storeid']]['投入广告比'] = 0;

            // 总套系
            $CustomerTotal = $PayLog->field('Money')->where(['CustId' => $v['custid'], 'OrderTime' => ['between', [$InsertStart, $InsertEnd]]])->find();
            if (!empty($CustomerTotal)) {
                $count[$platform][$v['storeid']]['店内套系'] = $count[$platform][$v['storeid']]['店内套系'] + $CustomerTotal['money'];
            } else {
                $count[$platform][$v['storeid']]['店内套系'] = $count[$platform][$v['storeid']]['店内套系'] + 0;
            }

            // 网单套系
            if ($order['customertype'] == 1) {
                $count[$platform][$v['storeid']]['网单套系'] = $count[$platform][$v['storeid']]['网单套系'] + $CustomerTotal['money'];
            } else {
                $count[$platform][$v['storeid']]['网单套系'] = $count[$platform][$v['storeid']]['网单套系'] + 0;
            }

            // 店内套系
            if (in_array($order['customertype'], $CustomerType)) {
                $count[$platform][$v['storeid']]['店内'] = $count[$platform][$v['storeid']]['店内'] + $CustomerTotal['money'];
            } else {
                $count[$platform][$v['storeid']]['店内'] = $count[$platform][$v['storeid']]['店内'] + 0;
            }
            $count[$platform][$v['storeid']]['网单均价'] = 0;
            $count[$platform][$v['storeid']]['店内均价'] = 0;

        }

        // 店内订单
        $Promotion = M('promotion');
        $where['UtilityTime'] = ['between', [$InsertStart, $InsertEnd]];
        foreach ($count as $key => $val) {
            foreach ($val as $k => $v) {
                $source = array_unique($v['来源']);
                $where['StoreId'] = $k;
                $where['SourceFrom'] = ['in', $source];
                $charge = $Promotion->where($where)->sum('Charge');
                $count[$key][$k]['广告费'] = (int)$charge;
                $count[$key][$k]['咨询指标'] = (int)$Promotion->where($where)->sum('CustomerNum');
                $count[$key][$k]['咨询完成率'] = percent($count[$key][$k]['总咨询'], $count[$key][$k]['咨询指标']);
                $count[$key][$k]['平均入店成本'] = round($count[$key][$k]['广告费'] / $count[$key][$k]['入店数']);
                $count[$key][$k]['平均订单成本'] = round($count[$key][$k]['广告费'] / $count[$key][$k]['总订单数'], 2);
                $count[$key][$k]['网单均价'] = round($count[$key][$k]['网单套系'] / $count[$key][$k]['网单订单数'], 2);
                $count[$key][$k]['店内均价'] = round($count[$key][$k]['店内'] / $count[$key][$k]['店内订单数'], 2);
                $count[$key][$k]['投入广告比'] = round($count[$key][$k]['门店网络实收'] / $count[$key][$k]['广告费'], 2);

            }
        }
        $this->assign('count', $count);
        $this->display();
    }

    public function upExcel()
    {
        $map = [];
        $get = I('get.');
        !empty($get['XbbOrderNo'])&&$map['XbbOrderNo'] = I('XbbOrderNo');
       !empty($get['PaperOrderNo'])&&$map['PaperOrderNo'] = I('PaperOrderNo');
        $get['InputStatus']>-1&&$map['InputStatus'] = I('InputStatus');
        !empty($get['CrmOrderNo'])&&$map['CrmOrderNo'] = I('CrmOrderNo');
        $get['DepartId']>-1&&$map['DepartId'] = I('DepartId');
        $get['Kid']>-1&&$map['Kid'] = $get['Kid'];
        $options = [
            'map' => $map,
        ];
        //print_r($map);
        $list =$this->page('file', $options);
        foreach($list as $k=>$v){
            if($v['diff']){
                $diffArr = array_filter(explode(',',$v['diff']));
                foreach($diffArr as $kal=>$val){
                    $diff = '';
                    switch($val){
                        case 1:
                            $diff = $diff.'来源不对,';
                            break;
                        case 2:
                            $diff = $diff.'套系金额不对,';
                            break;
                        case 3:
                            $diff = $diff.'实收不对,';
                            break;
                        case 4:
                            $diff = $diff.'客服不对';
                            break;
                        case 5:
                            $diff = $diff.'平台不对';
                            break;
                    }
                }
            }
            $list[$k]['diff'] = $diff;
        }
        $this->assign('list',$list);
        $this->sells =  D('Department')->getSellDepartment($this->user['kid']);
        $this->assign('sells', $this->sells);
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        $this->assign('ks', $ks);
        $this->display();
    }

    public function doUpExcelTo()
    {
        $dir = './xxzx/';
        $config = [
            'maxSize' => 2 * 1024 * 1024,
            'rootPath' => './',
            'savePath' => $dir,
            'saveName' => ['uniqid', ''],
            'exts' => ['xlsx', 'xls'],
            'autoSub' => false,
        ];

        $uploader = new Upload($config);
        $info = $uploader->upload();
        if (!$info) {
            $this->ajaxReturn([
                'code' => '500',
                'msg' => $uploader->getError()
            ]);
        }

        $excel = $info['upexcel']['savepath'] . $info['upexcel']['savename'];
        $ext = $info['upexcel']['ext'];
        $excel = realpath($excel);
        $arr = importExecl($excel, $ext);
        $rows = $arr['data'][0]['Content'];
        $count=count($rows);
        if($count>5000)
        {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '一次最多能导入5千条数据，您目前导入条数为：'.$count,
            ]);
        }

        $Model = M('file');
        $userModel = D('User');
        $customerModel = D('Customer');
        $payLogModel = M('payLog');
        $orderModel = M('order');
        $type = 0;
        $CrmOrderNoOfKid = [];
        if (!empty($rows)) {
            foreach ($rows as $key => $val) {
                if(empty($val[2])&&empty($val[3])){
                    continue;
                }

                $res1 = [];
                $res2 = [];
                if ($key == 1) continue;
                foreach($this->sources as $k=>$v){
                    if($v['sourcename']==trim($val[11])){
                        $data['SourceFrom'] = $k;
                    }
                }
                $orderTimeStart = date("Y-m-01",strtotime($val[0]));
                $orderTimeEnd = date("Y-m-d 23:59:59",strtotime("$orderTimeStart+1 month -1 day"));
                $sellerArr = $userModel->where(['RealName'=>trim($val[12])])->select();
                $n = count($sellerArr);
                $remark ='';
                if($n>1){
                    foreach($sellerArr as $ks=>$vs){
                        $remark =$remark . $this->departments[$vs['departid']]['departname'].$vs['realname'].',';
                    }
                }
                $sellerArr[0]['userid']&&$seller = $sellerArr[0]['userid'];
                $seller&&$kid=$userModel->getUser($seller,'kid');
                $seller&&$departId=$userModel->getUser($seller,'departid');



                if('邀约进店'==trim($val[9])){
                    $orderType = 3;
                }elseif('网单'==trim($val[9])){
                    $orderType = 1;
                }
                //退单
                if(trim($val[7])=='-1'){
                    $orderType = 4;
                }
                // 预导入》信息中心导入》项管中心导入》已导入
                $data = [
                    'OrderDate' => $val[0],
                    'PayTime' => $val[1],
                    'XbbOrderNo' => $val[2],
                    'PaperOrderNo' => $val[3],
                    'CrmOrderNo' => '',
                    'Bride' => $val[4],
                    'CustName' => $val[5],
                    'SetPrice' => $val[6],
                    'Receipt' => $val[7],
                    'EffectiveOrderNum' => $val[8],
                    'OrderType' => $orderType,
                    'Platform' => $val[10],
                    'SourceFrom' =>$data['SourceFrom'],
                    'Seller' => $seller,
                    'Kid' =>$kid ,
                    'DepartId' => $departId,
                    'CrmSeller' => 0,
                    'InputStatus' => 0,
                    'Diff' => $type,
                    'Remark' =>iconv('utf-8', 'gbk', $remark)
                ];


                //该大区已录入的订单

                $project = $Model->where(['XbbOrderNo' => "{$data['XbbOrderNo']}"])->find();
                $projects = $Model->where(['PaperOrderNo' => "{$data['PaperOrderNo']}"])->find();
                if (empty($project)&&empty($projects)) {
                    $Model->add($data);
                }
                $data['XbbOrderNo']&&$res1 = $payLogModel->where(['OrderNo' => "{$data['XbbOrderNo']}"])->find();
                $data['XbbOrderNo']&&$res3 = $orderModel->where(['OrderNo' => "{$data['XbbOrderNo']}"])->find();
                $data['PaperOrderNo']&&$res2 = $payLogModel->where(['OrderNo' => "{$data['PaperOrderNo']}"])->find();
                $data['PaperOrderNo']&&$res4 = $orderModel->where(['OrderNo' => "{$data['PaperOrderNo']}"])->find();
               /* if(!empty($res3)){
                    $accountOrderNo[] = $val[2];
                }elseif(!empty($res4)){
                    $accountOrderNo[] = $val[3];
                }*/

                $filename = '';
                 //$output = '';
                $filename = S('paylog-diff'. $this->user['accountname']);
                if(!$filename){
                    $filename = './expfile/paylog-diff' . $this->user['accountname'] . '-' . date('YmdHis') . '.csv';
                   S('paylog-diff'. $this->user['accountname'],$filename);
                   $output = fopen($filename, 'a') or die('can not open' . $filename);
                    $FieldName = [
                        '订单日期', '付款时间', '熊宝贝订单号', '纸质订单号', 'CRM订单号', '套系价格','实收金额','实收明细','台账差值' , '订单方式', '订单来源平台', '订单明细来源','CRM平台',
                        '客服','大区','部门','CRM客服','订单录入状态', '比较结果'
                    ];
                    foreach ($FieldName as $key => $val) {
                        $FieldName[$key] = iconv('utf-8', 'gbk', $val);
                    }
                    fputcsv($output, $FieldName);
                }

                $diff = '';
                if(empty($res3)&&empty($res4)){

                    $data1['InputStatus'] = 0;
                    $data['InputStatus'] = iconv('utf-8', 'gbk','订单未录入');

                }else{
                    //$data['StoreId'] =
                    if(!empty($res3['orderno'])){
                        $data1['CrmOrderNo']=$data['CrmOrderNo'] = $res3['orderno'];
                        $data1['CrmSeller'] =$data['CrmSeller'] = $res3['sellerid'];
                        $data1['CrmStoreId'] =$data['CrmStoreId'] = $res3['storeid'];
                        $customer = $customerModel->field('CustName')->where(['CustId'=>$res3['custid']])->find();
                        $data1['CrmCustName'] = $customer['custname'];
                    }else{
                        $data1['CrmOrderNo']=$data['CrmOrderNo'] = $res4['orderno'];
                        $data1['CrmSeller'] =$data['CrmSeller'] = $res4['sellerid'];
                        $data1['CrmStoreId']=$data['CrmStoreId']  = $res4['storeid'];
                        $customer = $customerModel->field('CustName')->where(['CustId'=>$res4['custid']])->find();
                        $data1['CrmCustName'] = $customer['custname'];
                    }

                    $data['CrmSeller'] = $userModel->getUser($data['CrmSeller'],'realname');
                    $data['CrmSeller'] = iconv('utf-8', 'gbk',$data['CrmSeller']);
                    $data1['InputStatus'] = 1;
                    $data['InputStatus'] =iconv('utf-8', 'gbk', '订单已录入');
                    if($data['XbbOrderNo']){
                        $receiptArr = $payLogModel->field('PayMoney,OrderType')->where(['OrderNo' => "{$data['XbbOrderNo']}",'OrderTime'=>['between',[$orderTimeStart,$orderTimeEnd]]])->select();
                        $crm['paymoney'] = $payLogModel->where(['OrderNo' =>"{$data['XbbOrderNo']}",'OrderType'=>['neq',5],'OrderTime'=>['between',[$orderTimeStart,$orderTimeEnd]]])->sum('PayMoney');


                    }elseif($data['PaperOrderNo']){
                        $crm['paymoney'] = $payLogModel->where(['OrderNo' =>"{$data['PaperOrderNo']}",'OrderType'=>['neq',5],'OrderTime'=>['between',[$orderTimeStart,$orderTimeEnd]]])->sum('PayMoney');
                        //echo $payLogModel->_sql();
                        $receiptArr = $payLogModel->field('PayMoney,OrderType')->where(['OrderNo' => "{$data['PaperOrderNo']}",'OrderTime'=>['between',[$orderTimeStart,$orderTimeEnd]]])->select();

                    }
                    if(!empty($receiptArr)){
                        foreach($receiptArr as $kr=>$vr){
                            if($vr['ordertype']==5){
                                if(abs($vr['paymoney'])){
                                    $crm['退款'] =  $crm['退款']+abs($vr['paymoney']);
                                }
                            }
                        }
                        $crm['实收'] = $crm['paymoney']-$crm['退款'];
                        $receipt = array_column($receiptArr,'paymoney');
                        $receiptDetail = implode('/',$receipt);
                        $data['ReceiptDetail'] = $receiptDetail;
                    }
                   /* if($data['PaperOrderNo']=='161207058'){
                        $str = $payLogModel->_sql();
                        $str .="\n".json_encode($receiptArr);
                        file_put_contents('123.txt',$str);
                    }*/

                    if(($data['SourceFrom']) && (($data['SourceFrom']!=$res3['sourcefrom'])&&($data['SourceFrom']!=$res4['sourcefrom']))){
                        $diff = $diff.',1';
                    }
                    if(($data['SetPrice'])&&($data['SetPrice']!=$res3['money']&&$data['SetPrice']!=$res4['money'])){
                        $diff = $diff.',2';
                    }
                    if(($data['Receipt'])&&($data['Receipt']!=$crm['实收'])){

                        $diff = $diff.',3';
                        $diffMoney = $crm['paymoney']-$data['Receipt'];
                        $data['DiffMoney'] = $diffMoney;

                    }
                    if(($data['Seller'])&&($data['Seller']!=$res3['sellerid']&&$data['Seller']!=$res4['sellerid'])){
                        $diff = $diff.',4';
                    }
                    !empty($res3['sourcefrom'])&&$crm['platform'] = $this->sources[$res3['sourcefrom']]['platform'];
                    !empty($res4['sourcefrom'])&&$crm['platform'] = $this->sources[$res4['sourcefrom']]['platform'];
                    if($data['Platform']&&$data['Platform']!=$crm['platform']){
                        $diff = $diff.',5';
                    }
                    if($data['CustName']&&$data['CustName']!=$data1['CrmCustName']){
                        $diff = $diff.',6';
                    }
                    $data['CrmPlatform'] = iconv('utf-8', 'gbk',$crm['platform']);
                    $data1['Diff'] = $diff;
                }


                $data['Seller'] = $userModel->getUser($data['Seller'],'realname');

                $data['Kid'] = $userModel->getUser($data['Seller'],'kid');
                $data1['Kid'] = $kid;
                $data['DepartId'] = $userModel->getUser($data['Seller'],'departid');
                $data1['DepartId'] = $departId;
                $data['Seller'] = iconv('utf-8', 'gbk',$data['Seller']);


                $data['SourceFrom'] = iconv('utf-8', 'gbk',$this->sources[$data['SourceFrom']]['sourcename']);
                if($orderType==1){
                    $orderType = '网单';
                }elseif($orderType==3){
                    $orderType = '邀约进店';
                }elseif($orderType==-1){
                    $orderType = '退款';
                }
                if($data['InputStatus']){
                    unset($data1['Diff']);
                    unset($data['Diff']);
                }
                !empty($data['XbbOrderNo'])&&$Model->where(['XbbOrderNo'=>"{$data['XbbOrderNo']}"])->save($data1);
                if(empty($data['XbbOrderNo'])&&!empty($data['PaperOrderNo'])){
                    $Model->where(['PaperOrderNo'=>"{$data['PaperOrderNo']}"])->save($data1);
                }
                unset($data1);
                $data['OrderType'] = iconv('utf-8', 'gbk',$orderType);
                $data['Diff'] = $diff;


                $diffArr = explode(',',$data['Diff']);
                $diffArr = array_filter($diffArr);
                $diff = '';
                foreach($diffArr as $kd=>$vd){
                    switch($vd){
                        case 1:
                            $diff = $diff.'来源不对,';
                            break;
                        case 2:
                            $diff = $diff.'套系金额不对,';
                            break;
                        case 3:
                            $diff = $diff.'实收不对,';
                            break;
                        case 4:
                            $diff = $diff.'客服不对,';
                            break;
                        case 5:
                            $diff = $diff.'平台不对,';
                            break;
                        case 6:
                            $diff = $diff.'客户名字不对,';
                            break;

                    }
                }

                $Kids = $this->getKidName();
                $data['Kid'] = iconv('utf-8', 'gbk',$Kids[$kid]);
                $depart = D('Department')->field('DepartName')->find($departId);
                $data['DepartId'] = iconv('utf-8', 'gbk',$depart['departname']);
                $data['Diff'] = iconv('utf-8', 'gbk', $diff);
                $data['Platform'] = iconv('utf-8', 'gbk', $data['Platform']);
                $inputField =  [
                    'OrderDate','PayTime','XbbOrderNo','PaperOrderNo','CrmOrderNo','SetPrice','Receipt','ReceiptDetail','DiffMoney','OrderType','Platform',
                    'SourceFrom','CrmPlatform','Seller','Kid','DepartId','CrmSeller','InputStatus','Diff'
                ];
                $inputData1 = [];
                foreach($inputField as $ki=>$vi){
                    if($data['InputStatus']==0){
                        //unset($data['Diff']);
                        //unset($data['CrmSeller']);
                    }
                    $inputData1[$vi] = $data[$vi];
                }
                    fputcsv($output, $inputData1);



                unset($inputData);
                unset($data);
                unset($crm);
                //unset($data1);
                unset($project);
                unset($projects);
                unset($res1);
                unset($res3);
                unset($res4);
                unset($res2);
            }



           ob_flush();
            flush();
          fclose($output) or die("can not close");
            S('paylog-diff'. $this->user['accountname'],null);
            $this->ajaxReturn([
                'code' => '200',
                'expUrl'=> $filename,
                'msg' => '数据录入成功'
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '数据文件为空或数据格式不正确'
            ]);
        }
    }

    private function getKidName()
    {
        $ks = M('Department')->field('DepartId,DepartName')->where(['ParentId' => 1])->select();
        foreach($ks as $k=>$v){
            $Kids[$v['departid']] = $v['departname'];
        }
        return $Kids;
    }
    protected  function wedding_search()
    {
        $map = [];
        $get = I('get.');

        !empty($get['Province']) && $map['Province'] = $get['Province'];
        !empty($get['City']) && $map['City'] = $get['City'];
        !empty($get['Location']) && $map['Location'] = $get['Location'];

        if(empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] =  strtotime('2017-03-14 00:00:00');//date('Y-m-d', 0);
        } else {
            $get['StartInsertTime'] =  strtotime($get['StartInsertTime'].' 00:00:00');
        }
        if(empty($get['EndInsertTime'])) {
            $get['EndInsertTime'] = strtotime('2017-03-14 23:59:59');
        } else {
            $get['EndInsertTime'] =  strtotime($get['EndInsertTime'].' 23:59:59');
        }
       // print_r($get);
        $map['InsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];
       // print_r($map);exit;
        return $map;
    }
    public function wedding()
    {
        $map = $this->wedding_search();
        $this->page('weddingSeason', [
            'map' => $map,
            'order' => 'InsertTime desc',
            'display' => 20
        ]);
        $this->display();
    }

    public function expWedding()
    {
        // 设置要导出的字段
        $expCellName = [
            ['id', '编号'],
            ['custname', '客户名'],
            ['mobile', '手机号'],
            ['province', '省'],
            ['city', '市'],
            ['location', '地名'],
            ['inserttime', '录入时间'],
        ];
        $map = $this->wedding_search();
        $expTableData = M('weddingSeason')->where($map)->order('InsertTime desc')->select();
        foreach($expTableData as $k=>$v){
            $expTableData[$k]['inserttime'] = date("Y-m-d H:i:s",$v['inserttime']);
        }
        $expName = '品效通_' . $this->user['userid'] . '_';
        exportExcel($expName, $expTitle = '', $expCellName, $expTableData);
        unset($expTableData);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统品效通数据', 4);
    }

}