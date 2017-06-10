<?php
namespace KWS\Model;

use Think\Model;

class OrderModel extends Model
{
    protected $patchValidate = true;
    protected $customer = [];
    protected $user = [];

    protected $_auto = [
        ['Add_User', 'getAddUser', 3, 'callback'],
        ['Kid', 'getKid', 3, 'callback'],
        ['DepartId', 'getDepartId', 3, 'callback'],
        ['PdepartId', 'getPdepartId', 3, 'callback'],
        ['SellId', 'getSellerId', 1, 'callback'],
        ['SellerId', 'getSellerId', 1, 'callback'],
        ['PromoterId', 'getPromoterId', 3, 'callback'],
        //['StoreId', 'getStoreId', 3, 'callback'],
        ['SourceFrom', 'getSource', 3, 'callback'],
        ['Platform', 'getPlatform', 3, 'callback'],
        ['InsertTime', 'getDate', 1, 'callback']
    ];


    protected function _initialize()
    {
        // 客户资料
        $CustId = I('CustId');
        $this->customer = M('Customer')->find($CustId);

        // 操作者资料
        $this->user = session('user');
    }

    protected function getDate()
    {
        return date('Y-m-d H:i:s');
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
        $kid = I('Kid');
        if($kid){
            return $kid;
        }else{
            return $this->customer['kid'];
        }

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
        //return $this->customer['opeartor'];
        $promoterId = I('PromoterId');
        if($promoterId){
            return $promoterId;
        }else{
            return $this->customer['opeartor'];
        }
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

    protected  function getPlatform()
    {
        $platform = I('Platform');
        $sourcefrom = I('SourceFrom');
        if(!$platform){
            $sources = D('Source')->getAllSource();
            $platform = $sources[$sourcefrom]['platform'];
        }

        return $platform;
    }

}