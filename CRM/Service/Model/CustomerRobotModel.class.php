<?php
namespace Service\Model;

/**
 * 客资机器人
 * Class CustomerRebotModel
 * @package KWS\Model
 */
class CustomerRobotModel
{
    /**
     * 平均分配
     * @param $storeId  门店ID
     * @param $source   来源ID
     * @return array
     */
    public function averageDistribution($storeId, $source)
    {
        // 获取门店信息
        $store = D("Store")->getStore($storeId);
        // 负责该门店的所有销售部门
        $departments = explode(',', $store['sellids']);

        $sales = 0;
        $count = count($departments);
        if ($count > 1) {

            $amounts = [];
            $Customer = M('Customer');
            $yestoday = date('Y-m-d H:i:s', strtotime('-1 day 21:00'));
            $today = date('Y-m-d H:i:s', strtotime('Today 21:00'));
            foreach ($departments as $key => $val) {
                $map['StoreId'] = $storeId;
                $map['DepartId'] = $val;
                $map['Del'] = 0;
                $map['InsertTime'] = ['between', [$yestoday, $today]];
                $amounts[$val] = $Customer->where($map)->count();
            }

            $max = max($amounts);
            $min = min($amounts);
            if ($max - $min > $count) {
                // 总数相差个部门总数的情况，直接将咨询分配给最少的那一组
                $department = 0;
                foreach ($amounts as $key => $val) {
                    if ($min == $val) {
                        $department = $key;
                        break;
                    }
                }

                // 该部门负责该门店的客服队列
                if (!empty($department)) {
                    $sales = $this->_departmentSellerQueue($store, $department);
                } else {
                    $department = 0;
                    $sales = 0;
                }

            } else {
                // 否则按照门店队列来
                $department = $this->_storeDepartmentQueue($store, $source);
                // 该部门负责该门店的客服队列
                if (!empty($department)) {
                    $sales = $this->_departmentSellerQueue($store, $department);
                } else {
                    $department = 0;
                    $sales = 0;
                }
            }

        } else {

            // 单个部门
            $department = $store['sellids'];
            if (!empty($department)) {
                $sales = $this->_storeSellerQueue($store);
            } else {
                $department = 0;
                $sales = 0;
            }
        }

        return [
            'store' => $storeId,
            'deparment' => $department,
            'sales' => $sales
        ];
    }

    /**
     * 按照门店客服队列分配客资
     * @param $store    指定门店的门店信息
     * @return int      返回系统指定的客服ID
     */
    private function _storeSellerQueue($store)
    {
        $storeId = $store['storeid'];
        $sales = 0;
        $QueueName = "StoreSellerQueue-" . $storeId;
        $queue = S($QueueName);
        if (empty($queue)) {
            $queue = explode(",", $store['users']);
            S($QueueName, $queue);
        }

        $User = D('User');
        foreach ($queue as $key => $val) {
            $online = S('UserOnline-' . $val);
            if ($online == '1') {
                $MaxOrder = $User->getUser($val, 'maxorder');
                // 获取客服的接单总数
                $acceptNum = S('Count-' . $val);
                if ($MaxOrder > 0 && $acceptNum >= $MaxOrder) {
                    continue;
                }

                unset($queue[$key]);
                array_push($queue, $val);
                $queue = array_values($queue);
                S($QueueName, $queue);
                $sales = $val;
                break;
            }
        }

        return $sales;
    }

    /**
     * 获取门店的部门队列
     * @param $store    指定门店的门店信息
     * @param $source   指定来源的来源信息
     * @return mixed    返回指定的部门
     */
    private function _storeDepartmentQueue($store, $source)
    {
        $storeId = $store['storeid'];
        // $QueueName = "StoreDepartmentQueue-".$storeId.'-'.$source;
        $QueueName = "queue-store-department-" . $storeId . '-' . $source;
        $queue = S($QueueName);
        if (empty($queue)) {
            $queue = explode(',', $store['sellids']);
            S($QueueName, $queue);
        }

        $department = array_shift($queue);
        array_push($queue, $department);
        S($QueueName, $queue);

        return $department;
    }


    private function _departmentSellerQueue($store, $department)
    {
        $sales = 0;
        $QueueName = "queue-department-seller-" . $department;
        $queue = S($QueueName);
        if (empty($queue)) {
            $users = M("user")->field("UserId")->where(['DepartId' => $department])->select();
            $queue = array_column($users, 'userid');
            S($QueueName, $queue);
        }

        $users = explode(',', $store['users']);

        $User = D('User');
        $onlineName = 'UserOnline-';
        foreach ($queue as $key => $val) {
            if (S($onlineName . $val) == 1 && in_array($val, $users)) {
                $MaxOrder = $User->getUser($val, 'maxorder');
                $acceptNum = (int)S('Count-' . $val);
                if ($MaxOrder > 0 && $acceptNum >= $MaxOrder) {
                    continue;
                }

                unset($queue[$key]);
                array_push($queue, $val);
                $queue = array_values($queue);
                S($QueueName, $queue);
                $sales = $val;
                break;
            }
        }
        return $sales;
    }
}