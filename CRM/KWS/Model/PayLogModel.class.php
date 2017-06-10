<?php
namespace KWS\Model;

use Think\Model;

class PayLogModel extends Model
{
    protected $patchValidate = true;
    protected $customer = [];
    protected $user = [];
   /* protected $_auto = [
        ['AddUser', 'getAddUser', 3, 'callback'],
        ['Kid', 'getKid', 3, 'callback'],
        ['DepartId', 'getDepartId', 3, 'callback'],
        ['PdepartId', 'getPdepartId', 3, 'callback'],
        ['SellerId', 'getSellerId', 3, 'callback'],
        ['PromoterId', 'getPromoterId', 3, 'callback'],
        ['StoreId', 'getStoreId', 3, 'callback'],
        ['Source', 'getSource', 3, 'callback'],
    ];*/

    protected function _initialize()
    {
        // 客户资料
        $CustId = I('CustId');
        $this->customer = M('Customer')->find($CustId);

        // 操作者资料
        $this->user = session('user');
    }

    /**
     * 获取订单添加者
     */
    protected function getAddUser()
    {
        return $this->user['userid'];
    }

    /**
     * 获取该客资所在大区
     */
    protected function getKid()
    {
        return $this->customer['kid'];
    }

    /**
     * 获取该客资的邀约部门
     */
    protected function getPdepartId()
    {
        return $this->customer['pdepartid'];
    }

    /**
     * 获取该客资的邀约人员
     */
    protected function getPromoterId()
    {
        return $this->customer['opeartor'];

    }

    /**
     * 获取该客资的销售部门
     */
    protected function getDepartId()
    {
        $departId = I('DepartId');
        if($departId){
            return $departId;
        }else{
            return $this->customer['departid'];
        }
    }

    /**
     * 获取该客资的销售人员
     */
    protected function getSellerId()
    {
        $sellerId = I('Seller');
        if($sellerId){
            return $sellerId;
        }else{
            return $this->customer['salseid'];
        }
    }

    /**
     * 获取客资所预约的门店
     */
    protected function getStoreId()
    {
        //return $this->customer['storeid'];
        $storeId = I('Seller');
        if($storeId){
            return $storeId;
        }else{
            return $this->customer['storeid'];
        }
    }

    /**
     * 获取客资信息来源平台、官网、大众点评
     */
    protected function getSource()
    {
        $sourcefrom = I('SourceFrom');

        if($sourcefrom){
            return $sourcefrom;
        } else{
            return $this->customer['sourcefrom'];
        }
    }
}