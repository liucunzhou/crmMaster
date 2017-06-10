<?php
/**
 * Created by PhpStorm.
 * User: LFC
 * Date: 2016/11/11
 * Time: 21:29
 */
namespace KWS\Model;

use Think\Model;

class CustomerDressModel extends Model
{
    protected $customer = [];
    protected $_auto = [
        ['Kid', 'getKid', 3, 'callback'],
        ['PdepartId', 'getPromoterDepartId', 3, 'callback'],
        ['DepartId', 'getSellerDepartId', 3, 'callback'],
        ['PromoterId', 'getPromoterId', 3, 'callback'],
        ['SellerId', 'getSellerId', 3, 'callback'],
        ['PdepartName', 'getPdepartName', 3, 'callback'],
        ['DepartName', 'getDepartName', 3, 'callback'],
        ['LastEditUser', 'getLastEditUser', 3, 'callback'],
        ['LastEditTime', 'getLastEditTime', 3, 'callback']
    ];

    public function _initialize()
    {
        $CustId = I('CustId');
        $this->customer = M('Customer')->find($CustId);
    }

    /**
     * 获取Kid
     */
    protected function getKid()
    {
        $user = session('user');
        return D('User')->getDepartId($user['departid']);
    }
    /**
     * 获取邀约部门id
     */
    protected function getPromoterDepartId()
    {
        return !empty($this->customer['pdepartid']) ? $this->customer['pdepartid'] : 0;
    }

    /**
     * 获取销售部门ID
     */
    protected function getSellerDepartId()
    {
        return !empty($this->customer['departid']) ? $this->customer['departid'] : 0;
    }

    /**
     * 获取邀约手ID
     */
    protected function getPromoterId()
    {
        return !empty($this->customer['opeartor']) ? $this->customer['opeartor'] : 0;
    }

    /**
     * 获取销售ID
     */
    protected function getSellerId()
    {
        return !empty($this->customer['salseid']) ? $this->customer['salseid'] : 0;
    }

    /**
     * 获取邀约部门全名(k1-销售部门-微博A组)
     */
    protected function getPdepartName()
    {

    }

    /**
     * 获取销售部门全名(k1-销售部门-微博A组)
     */
    protected function getDepartName()
    {

    }

    /**
     * 获取最后修改的用户
     */
    protected function getLastEditUser()
    {
        $user = session('user');
        return $user['userid'];
    }

    /**
     * 设置修改时间
     */
    protected function getLastEditTime()
    {
        return date('Y-m-d H:i');
    }
}