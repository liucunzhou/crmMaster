<?php
/**
 * Created by PhpStorm.
 * User: LFC
 * Date: 2017/1/4
 * Time: 14:09
 */

namespace KWS\Controller;


class CheckController extends BaseController
{
    /**
     * 订单核对
     */
    public function checkOrderList()
    {
        $map = [];
        isset($_GET['Kid']) && $map['Kid'] = $_GET['Kid'];
        isset($_GET['DepartId']) && $map['DepartId'] = $_GET['DepartId'];
        isset($_GET['PdepartId']) && $map['PdepartId'] = $_GET['PdepartId'];
        isset($_GET['SellerId']) && $map['SellerId'] = $_GET['SellerId'];
        isset($_GET['PromoterId']) && $map['PromoterId'] = $_GET['PromoterId'];
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
            if(empty($customer)) $customer = $Customer->field($CustFields)->find($v['custid']);
            $list[$k]['customer'] = $customer;

            // 获取支付日志
            $paylog = $PayLog->field($PayLogFields)->where(['OrderNo' => $v['orderno']])->select();
            $list[$k]['paylog'] = $paylog;
        }
        $this->assign('list', $list);

        // 回去推广列表
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);

        $this->display();
    }

    /**
     * 校对订单
     */
    public function checkOrder()
    {
        $OrderId=I('OrderId');

        // 订单详情
        $Order = M('Order')->find($OrderId);
        $this->assign('Order', $Order);

        // 订单支付详情
        $PayLog = M('PayLog')->where(['OrderId'=>$OrderId])->order('OrderTime desc')->select();
        $this->assign('PayLog', $PayLog);

        layout('Layout/win');
        $this->display();
    }
}