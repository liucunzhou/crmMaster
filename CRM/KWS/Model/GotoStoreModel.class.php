<?php
namespace KWS\Model;

use Think\Model;

class GotoStoreModel extends Model
{
    protected $patchValidate = true;
    protected $customer = [];

    protected $_validate = [
        ['CustId', 'require', '客户id不能为空'],
        // ['Mobile', 'checkRepeatCustomer', '重复客户...', 0, 'callback', 1],
    ];

    protected $_auto = [
        ['StoreId', 'getStoreId', 2, 'callback'],
        ['ModifyTime', 'setModifyTime', 2, 'callback'],
        ['Kid', 'setKid', 2, 'callback'],
        ['CustId', 'setCustId', 1, 'callback'],
        ['CustNo', 'setCustNo', 2, 'callback'],
        ['salseId', 'setsalseId', 2, 'callback'],
        ['DepartId', 'setDepartId', 2, 'callback'],
        ['Opeartor', 'setOpeartor', 2, 'callback'],
        ['SourceFrom', 'setSourceFrom', 2, 'callback'],
        ['Mobile', 'setMobile', 2, 'callback'],
        ['WeChat', 'setWeChat', 2, 'callback'],
        ['WeiboName', 'setWeiboName', 2, 'callback'],
        ['QQ', 'setQQ', 2, 'callback'],
        ['CustName', 'setCustName', 2, 'callback'],
        ['CustInsertTime', 'setCustInserTime', 2, 'callback'],
    ];

    protected function _initialize()
    {
        $custId = I('CustId');
        if($custId){
            $customer = M('Customer')->where(['CustId'=>$custId])->find();
        }

       !empty($customer) &&$this->customer = $customer;
    }

    /**
     * 设置邀约手ID
     */
    protected function getStoreId()
    {
        return $this->customer['storeid'];
    }


    /**
     * 设置KID
     */
    protected function setKid()
    {
        return $this->customer['kid'];
    }
    protected function setsalseId()
    {
        return $this->customer['salseid'];
    }
    protected function setDepartId()
    {
        return $this->customer['departid'];
    }
    protected function setCustId()
    {
        return $this->customer['custid'];
    }
    protected function setCustNo()
    {
        return $this->customer['custno'];
    }
    protected function setOpeartor()
    {
        return $this->customer['opeartor'];
    }
    protected function setWeChat()
    {
        return $this->customer['wechat'];
    }
    protected function setSourceFrom()
    {
        return $this->customer['sourcefrom'];
    }
    protected function setMobile()
    {

        return $this->customer['mobile'];
    }

    protected function setWeiboName()
    {
        return $this->customer['weiboname'];
    }
    protected function setQQ()
    {
        return $this->customer['qq'];
    }
    protected function setCustName()
    {
        return $this->customer['custname'];
    }
    protected function setCustInserTime()
    {
        return $this->customer['inserttime'];
    }
    /**
     * 设置写入时间
     */
    protected function setModifyTime()
    {
        return date("Y-m-d H:i:s");
    }

   /* protected function checkMobile()
    {
        $mobile = I('Mobile');
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }*/

}