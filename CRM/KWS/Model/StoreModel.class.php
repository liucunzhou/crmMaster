<?php
namespace KWS\Model;

use Think\Model;

/**
 * 门店模型
 * Class StoreModel
 * @package KWS\Model
 */
class StoreModel extends Model
{
    protected $user = [];

    protected $_auto = [
        ['SellIds', 'setSellIds', 3, 'callback'],
        ['CreateTime', 'time', 3, 'function']
    ];

    public function _initialize()
    {
        $this->user = session('user');
    }

    /**
     * 获取大区的所有门店
     * @param $kid
     * @return array
     */
    public function getKStores($kid)
    {
        $stores = $this->getAllStore();

        if($this->user['roleid'] != '12') {
            if ($this->user['kid'] != '67' && $this->user['kid'] != '12') {
                $kstores = [];
                foreach ($stores as $key => $val) {
                    if ($val['departid'] == $kid) $kstores[$key] = $val;
                }

                return $kstores;
            } else {
                return $stores;
            }
        } else {
            return $stores;
        }
    }

    /**
     * 获取所在部门门店
     * 如果是销售获取自己所负责的门店
     */
    public function getDStores($departId)
    {
        $stores = D('Store')->getAllStore();
        // 获取门店关系
        $Deparment = D('Department');
        $departments = $Deparment->getAllDepartment();
        $department = $Deparment->getDepartment($departId);
        $treeDepartment = $Deparment->getTree($departments, $department);
        if($this->user['roleid']==1){
            $SalesStores = explode(',', $this->user['storeid']);
            foreach ($stores as $key => $val) {
                if (in_array($key, $SalesStores)) {
                    $DepartStore[$key] = $val;
                }
            }
        }else{
            if(empty($treeDepartment)) {
                $DepartStore = [];
                foreach ($stores as $key => $val) {
                    $SalesDepartment  = explode(',', $val['sellids']);
                    if (in_array($departId, $SalesDepartment)) {
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
        }

        return $DepartStore;
    }
    /**
     * @param bool|false $update
     * @return mixed
     */
    public function getAllStore($update = false)
    {
        $stores = S('Stores');
        if (empty($stores) || $update) {
            $stores = $this->order('BrandId,OrderNo')->getField('StoreId,StoreName,GroupId,BrandId,DepartId,SellIds,Users,Business');
            S('Stores', $stores);
        }

        return $stores;
    }

    /**
     * 获取指定门店销售列表
     * @param $id
     * @return array|mixed
     */
    public function getStoreUsers($id)
    {
        //$users = S('storeUsers'.$id);
       // if(empty($users)||$update==true){
            $users = $this->getStore($id, 'users');
            $users = explode(',', $users);
           // S('storeUsers'.$id,$users);
      //  }

        return $users;
    }

    /**
     * 获取指定门店信息
     * @param $id int 门店ID
     * @param $field string 获取门店的某一个字段名称
     * @return mixed
     */
    public function getStore($id, $field = '')
    {
        $stores = $this->getAllStore();

        $store = $stores[$id];

        if (!empty($field)) {
            return $store[$field];
        } else {
            return $store;
        }
    }

    protected function setSellIds()
    {
        $items = I("sellids");
        return implode(',', $items);
    }


    public function devideGroup()
    {
        $multiStore = [2,3,5, 6,10,11, 15, 16,28, 29, 37, 38,41,52,61, 63,82, 83,94,95,104,134,186,187,193,243];
        return $multiStore;
       /* $storePara = S('MultiStore');
        $UserModel = D('User');
        if(empty($storePara)){
            $field = "StoreId,DepartId,SellIds,Users";
            $list = $this->field($field)->select();

            foreach($list as $k=>$v){

                if(!strpos($v['sellids'], ',')){
                    continue;
                }else{
                    $sellids = explode(',',$v['sellids']);
                }

                if(strpos($v['users'],',')){
                    $users = explode(',', $v['users']);
                    $departs = [];
                    foreach($users as $kal=>$val){

                        $departId = $UserModel->getUser($val,'departid');
                        if(in_array($departId,$sellids)){
                            $departs[] = $departId;
                        }
                    }

                    $departs = array_unique($departs);
                    $departs = array_values($departs);
                    if(count($departs)>1){
                        $storePara[] = $v['storeid'];
                    }
                }else{
                    continue;
                }

            }
            S('MultiStore', $storePara);
        }*/
        // return $storePara;

    }
    /**
     *单个门店部门队列
     */
    public function singleStoreQueue($storeId,$sourceFrom){
        $singleStoreQueue = S('singleStoreQueue-'.$storeId.'-'.$sourceFrom);
        if(empty($singleStoreQueue)){
            $field = "StoreId,DepartId,SellIds,Users";
            $data = $this->field($field)->where(['StoreId'=>$storeId])->find();
            if(!strpos($data['sellids'], ',')){
                return false;
            }else{
                $singleStoreQueue = explode(',',$data['sellids']);
                S('singleStoreQueue-'.$storeId.'-'.$sourceFrom,$singleStoreQueue);
            }
        }
        return $singleStoreQueue;
    }

    /**
     * 门店按照部门排序
     * @param $storeId
     * @return array|mixed
     */
    public function queueByDepartment($storeId)
    {
        $queueName = "queue-store-department-".$storeId;
        $queue = S($queueName);
        if(!$queue) {
            $store = $this->getStore($storeId);
            $queue = explode(',', $store['sellids']);
            S($queueName, $queue);
        }

        return $queue;
    }

    /**
     * @param $storeId
     * @param $platform
     * @return array|mixed
     */
    public function queueByDepartmentPlatform($storeId,$platform)
    {
        $queueName = "queue-store-department-".$storeId.'-'.$platform;
        $queue = S($queueName);
        if(empty($queue)) {
            $store = $this->getStore($storeId);
            $queue = explode(',', $store['sellids']);
            S($queueName, $queue);
        }

        return $queue;
    }
}