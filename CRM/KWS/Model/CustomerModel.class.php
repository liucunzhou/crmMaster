<?php
namespace KWS\Model;

use Think\Model;

class CustomerModel extends Model
{
    protected $patchValidate = true;
    protected $user = [];

    protected $_validate = [
        ['StoreId', 'require', '门店不能为空'],
        // ['Mobile', 'checkRepeatCustomer', '重复客户...', 0, 'callback', 1],
    ];

    protected $_auto = [
        ['IsRepeat', 'setRepeatCustomer', 1, 'callback'],
        ['LastEditUser', 'getLastEditUser', 3, 'callback'],
        ['LastEditTime', 'getLastEditTime', 3, 'callback'],
        ['InsertTime', 'setInsertTime', 1, 'callback'],
        ['Opeartor', 'setOpeartor', 1, 'callback'],
        ['PdepartId', 'setPdepartId', 1, 'callback'],
        ['Kid', 'setKid', 1, 'callback'],
    ];

    protected function _initialize()
    {
        $this->user = session('user');
    }

    /**
     * 设置邀约手ID
     */
    protected function setOpeartor()
    {
        return $this->user['userid'];
    }

    /**
     * 设置邀约手部门ID
     */
    protected function setPdepartId()
    {
        return $this->user['departid'];
    }

    /**
     * 设置KID
     */
    protected function setKid()
    {
        return $this->user['kid'];
    }

    /**
     * 设置写入时间
     */
    protected function setInsertTime()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * 查询回访次数
     * @param $CustNo
     * @return mixed
     */
    public function getVisitTimes($CustNo)
    {
        $where['CustNo'] = $CustNo;
        $count = M('visit')->where($where)->count();
        return $count;
    }

    /**
     * 判断是否重复客户
     * @return bool
     */
    protected function checkRepeatCustomer()
    {
        $post = I('post.');
        $map['StoreId'] = $post['StoreId'];
        $arr= ['Mobile','QQ','WeiboName','WeChat'];
        foreach($arr as $k=>$v){
            if(!empty($post[$v])){
                $map[$v] = $post[$v];
                $result = $this->where($map)->find();
                if($result){
                    return false;
                }else{
                    unset($map[$v]);
                }
            }
        }

        return true;
    }

    /**
     * 重复标识：1是重复咨询；0是非重复的
     * @return int
     */
    public function setRepeatCustomer()
    {
        $data = I('post.');
        $arr= ['Mobile','QQ','WeiboName','WeChat'];
        $where = [];
        foreach($arr as $k=>$v){
            if(!empty($data[$v])){
                $where[$v] = $data[$v];
            }
        }
        $where['_logic'] = 'OR';
        $result = $this->where($where)->find();

        if(!empty($result)){
            return true;
        }else{
            $where = [];
        }

        return false;
    }


    public function getCustomerField($custId, $field = '')
    {
        $customer = $this->where(['CustId' => $custId])->find();
        if (empty($field)) {
            return $customer;
        } else {
            return $customer[$field];
        }
    }

    /**
     * 获取Kid
     */
    protected function getKid()
    {
        $user = session('user');
        return $user['kid'];
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

    protected function checkMobile()
    {
        $mobile = I('Mobile');
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }
    //比较两个组谁的客咨多
    public function checkDepartCustNum($departIds,$storeId)
    {
        $insertStart = date("Y-m-d").' 00:00:00';
        $insertEnd = date("Y-m-d").' 23:59:59';
        $map['InsertTime'] = ['between',[$insertStart,$insertEnd]];
        $map['StoreId'] = $storeId;
        if($departIds[0]){
            $map['DepartId'] =$departIds[0];
            $res1 = $this->field('count(*) n')->where($map)->select();
        }
        if($departIds[1]){
            $map['DepartId'] =$departIds[1];
            $res2 = $this->field('count(*) n')->where($map)->select();
        }
        //echo $res1[0]['n'].',';
        //echo $res2[0]['n'];
        $res = $res1[0]['n']-$res2[0]['n'];
        if($res){
            return true;
        }else{
            return false;
        }
    }
}