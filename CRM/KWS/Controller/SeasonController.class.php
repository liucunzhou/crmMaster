<?php
/**
 * Created by PhpStorm.
 * User: LFC
 * Date: 2017/3/9
 * Time: 19:15
 */

namespace KWS\Controller;


use Think\Controller;

class SeasonController extends Controller
{
    // 所有品牌
    private $brands = [];

    // 所有门店
    private $stores = [];

    // 所有部门
    private $departments = [];

    /**
     * 初始化
     */
    public function _initialize()
    {
        // 获取所有品牌
        $this->brands = D("Brand")->getAllBrand();

        // 获取所有门店
        $this->stores = D("Store")->getAllStore();

        // 获取所有部门
        $this->departments = $this->_getAllDepartment();

    }

    /**
     * 婚礼季分配模式
     */
    public function wedding()
    {
        header('Content-Type:text/html; charset=utf-8');
        // 每2分钟推送一次，每次推送50条
        $User = D("User");
        $WeddingSeason = M("WeddingSeason");
        $season = $WeddingSeason->where(['Status' => 0])->limit(20)->select();

        if(empty($season)) return;

        foreach ($season as $key => $value) {

            // 根据城市获取门店
            $store = $this->_getStore($value['location']);
            // 未比对到城市门店，标记信息状态为3
            if (empty($store)) {
                $WeddingSeason->where(['id' => $value['id']])->save(['Status' => 3]);
                echo $store['storeid']."未找到匹配的门店{$value['location']}推送失败......<br>";
                continue;
            } else {
                echo "获取城市成功......<br>";
            }

            $total = S("store5-count");
            if ($store['storeid'] == 5 && !empty($total) && $total % 10 == 0) {
                $SellerId = 1105;
                S("store5-count", $total + 1);
            } else {
                // 获取自动分配的销售
                $SellerId = $this->_autoAssign($store);
                if ($store['storeid'] == 5) {
                    S("store5-count", $total + 1);
                }
            }

            if(empty($SellerId)) {
                echo $store['storeid']."未推送成功......<br>";
                $WeddingSeason->where(['id' => $value['id']])->save(['Status' => -1]);
                continue;
            } else {
                echo "获取客服成功......<br>";
            }

            $buildCustomerResult = $this->_buildCustomer($value, $SellerId, $store['storeid']);

            // 更改婚礼纪来源信息状态
            if($buildCustomerResult) {
                $WeddingSeason->where(['id' => $value['id']])->save(['Status' => 1]);
                echo $store['storename'] . "推送成功......<br>";
            } else {
                echo $store['storename'] . "推送失败......<br>";
            }
        }
    }

    /**
     * 婚礼季分配模式
     */
    public function wedding2()
    {
        header('Content-Type:text/html; charset=utf-8');
        // 每2分钟推送一次，每次推送50条
        $User = D("User");
        $WeddingSeason = M("WeddingSeason");
        $season = $WeddingSeason->where(['Status' => -1])->limit(50)->select();

        if(empty($season)) return;

        foreach ($season as $key => $value) {

            // 根据城市获取门店
            $store = $this->_getStore($value['location']);
            // 未比对到城市门店，标记信息状态为3
            if (empty($store)) {
                $WeddingSeason->where(['id' => $value['id']])->save(['Status' => 3]);
                echo $store['storeid']."未找到匹配的门店{$value['location']}推送失败......<br>";
                continue;
            } else {
                echo "获取城市成功......<br>";
            }

            $total = S("store5-count");
            if ($store['storeid'] == 5 && !empty($total) && $total % 10 == 0) {
                $SellerId = 1105;
                S("store5-count", $total + 1);
            } else {
                // 获取自动分配的销售
                $SellerId = $this->_autoAssign($store);
                if ($store['storeid'] == 5) {
                    S("store5-count", $total + 1);
                }
            }

            if(empty($SellerId)) {
                echo $store['storeid']."未推送成功......<br>";
                $WeddingSeason->where(['id' => $value['id']])->save(['Status' => -1]);
                continue;
            } else {
                echo "获取客服成功......<br>";
            }

            $buildCustomerResult = $this->_buildCustomer($value, $SellerId, $store['storeid']);

            // 更改婚礼纪来源信息状态
            if($buildCustomerResult) {
                $WeddingSeason->where(['id' => $value['id']])->save(['Status' => 1]);
                echo $store['storename'] . "推送成功......<br>";
            } else {
                echo $store['storename'] . "推送失败......<br>";
            }
        }
    }



    /**
     * 推送未开门店以外的城市客资
     */
    public function pushUnstore()
    {
        $user = session("user");
        $get = I("get.");

        if(empty($get['StoreId'])) {
            $this->assign([
                'code'  => '400',
                'msg'   => 'StoreId is empty!'
            ]);
        } else {
            $store = $this->stores[$get['StoreId']];
        }


        $ids = explode(',', $get['ids']);
        if (empty($ids)) {
            $this->assign([
                'code'  => '400',
                'msg'   => 'CustId is empty'
            ]);
        }

        $WeddingSeason = M("WeddingSeason");
        $weddingSeason = $WeddingSeason->where(['id'=>['in', $ids]])->select();

        $error = 0;
        $success = 0;
        foreach ($weddingSeason as $key=>$val) {
            // 获取自动分配的销售
            $SellerId = $this->_autoAssign($store);
            if (empty($SellerId)) {
                $error++;
                operateLog($user['userid'], $user['realname'], '推送婚礼季客资ID为{$val[id]}失败', 1);
                continue;
            }

            $buildCustomerResult = $this->_buildCustomer($val, $SellerId, $store['storeid']);
            // 更改婚礼纪来源信息状态
            $buildCustomerResult && $WeddingSeason->where(['id' => $val['id']])->save(['Status' => 1]);
            $success++;

            operateLog($user['userid'], $user['realname'], '成功推送了婚礼季客资ID为{$val[id]}给客服ID为'.$SellerId,1);
        }

        $this->ajaxReturn([
            'code'  => '200',
            'msg'   => "成功推送{$success}条数据,{$error}条失败"
        ]);
    }

    private function _buildCustomer($data, $SellerId, $StoreId)
    {
        $Customer = M("Customer");
        $Customer->startTrans();
        $CustData['CustName'] = $data['custname'];
        $CustData['Mobile'] = $data['mobile'];
        $CustData['Sex'] = $data['sex'];
        $CustData['CustNo'] = date("Ymdhis", time()) . rand(1000, 9999);
        $CustData['Remark'] = $data['location'];
        // 设置大区、部门信息
        $seller = D('User')->getUser($SellerId);
        $CustData['Kid'] = $seller['kid'];
        $CustData['salseId'] = $SellerId;
        $CustData['DepartId'] = $seller['departid'];
        $CustData['Opeartor'] = 1009;
        $CustData['PdepartId'] = 0;

        // 设置客资的门店、来源、状态写入时间信息
        $CustData['StoreId'] = $StoreId;
        $CustData['SourceFrom'] = 72;
        $CustData['Status'] = 0;
        $CustData['IsWashing'] = 0;
        $CustData['Del'] = 0;
        $CustData['IsRepeat'] = 0;
        $CustData['InsertTime'] = date('Y-m-d H:i:s');
        $rs1 = $Customer->add($CustData);

        // 添加到分配表
        $lastId = $Customer->getLastInsID();
        $AssignData['CustId'] = $lastId;
        $AssignData['CustNo'] = $CustData['CustNo'];
        $AssignData['InitUser'] = $SellerId;
        $AssignData['NowUser'] = $SellerId;
        $AssignData['Status'] = 0;
        $AssignData['InsertTime'] = date('Y-m-d H:i:s');
        $AssignData['UserCount'] = 1;
        $AssignData['DepartId'] = $seller['departid'];;
        $AssignData['Invitor'] = 0;
        $AssignData['InvitDepart'] = 0;
        $AssignData['IsReward'] = 0;
        $AssignData['IsWashing'] = 0;
        $AssignData['AppointType'] = 0;
        $rs2 = M('assign')->add($AssignData);

        if ($rs1 && $rs2) {
            $Customer->commit();
            return true;
        } else {
            $Customer->rollback();
            return false;
        }
    }

    /**
     * 自动分配客资
     * @param $store
     * @return int
     */
    private function _autoAssign($store)
    {
        $StoreDepartments = explode(',', $store['sellids']);
        $count = count($StoreDepartments);
        if ($count > 1) {
            $amounts = [];
            $Customer = M('Customer');
            $yestoday = date('Y-m-d H:i:s', strtotime('-1 day 21:00'));
            $today = date('Y-m-d H:i:s', strtotime('Today 21:00'));
            foreach ($StoreDepartments as $key => $val) {
                $map['StoreId'] = $store['storeid'];
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
                if ($department == 0) return 0;
                $sales = $this->_departmentSellerQueue($department, $store['storeid']);

            } else {
                // 否则按照门店队列来
                $department = $this->_storeDepartmentQueue($store['storeid'], 72);
                $sales = $this->_departmentSellerQueue($department, $store['storeid']);
            }

        } else {
            // 单个部门
            $department = $store['sellids'];
            $sales = $this->_storeSellerQueue($store['storeid']);
        }

        if ($sales == '0') {
            // $this->ajaxReturn(['code' => '400', 'msg' => $this->departments[$department]['departname'] . ' 无客服在线']);
        }

        return $sales;
    }


    private function _getStore($city)
    {

        $store = [];
        foreach ($this->stores as $key => $val) {

            if ($val['brandid'] == '1' && strpos($val['storename'], $city) !== false) {
                $store = $val;
                break;
            } else {

                // 检测是否在K2的地区列表
                $stores = $this->_getCityStore();
                foreach($stores as $k=>$v) {
                    if($k == $city) {
                        $store = $this->stores[$v];
                        break;
                    }
                }
            }
        }

        return $store;
    }

    /**
     * 获取城市对应门店ID
     * @return array
     */
    private function _getCityStore()
    {
        $citys = [
            '揭阳' => 10,
            '哈尔滨' => 29,
            '大庆' => 26,
            '雅安' => 267,
            '巴中' => 267,
            '桂林' => 267,
            '蚌埠' => 188,
            '宣城' => 260,
            '宿迁' => 188,
            '三亚' => 187,
            '宁德' => 188,
            '景德镇' => 188,
            '萍乡' => 41,
            '襄阳' => 41,
            '黄石' => 270,
            '十堰' => 114,
            '恩施' => 41,
            '马鞍山' => 188,

        ];

        return $citys;
    }

    /**
     * 非PK门店，接单客服队列
     */
    private function _storeSellerQueue($StoreId)
    {
        $sales = 0;
        $online = S('online');
        $QueueName = "StoreSellerQueue-" . $StoreId;
        $queue = S($QueueName);
        if (empty($queue)) {
            $queue = explode(",", $this->stores[$StoreId]['users']);
            S($QueueName, $queue);
        }

        $User = D('User');
        foreach ($queue as $key => $val) {
            if (in_array($val, $online)) {
                $MaxOrder = $User->getUser($val, 'maxorder');
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
     * PK门店，销售部门队列
     * @param $StoreId 门店ID
     * @param $source 来源ID
     * @return int
     */
    private function _storeDepartmentQueue($StoreId, $source)
    {
        $QueueName = "queue-store-department-" . $StoreId . '-' . $source;
        $queue = S($QueueName);
        if (empty($queue)) {
            $store = $this->stores[$StoreId];
            $queue = explode(',', $store['sellids']);
            S($QueueName, $queue);
        }

        $department = array_shift($queue);
        array_push($queue, $department);
        S($QueueName, $queue);

        return $department;
    }

    private function _departmentSellerQueue($department, $StoreId)
    {
        $User = D('User');
        $sales = 0;
        $online = S('online');
        // 获取该门店 此部门下的所有销售
        $store = $this->stores[$StoreId];
        $users = explode(',', $store['users']);

        $QueueName = "queue-department-" . $department . '-store-' . $StoreId;
        $queue = S($QueueName);
        if (empty($queue)) {
            foreach ($users as $key => $val) {
                // 获取用户的部门
                $DepartId = $User->getUser($val, 'departid');
                if ($DepartId == $department) {
                    $queue[] = $val;
                }
            }

            $queue = array_unique($queue);
            S($QueueName, $queue);
        }

        foreach ($queue as $key => $val) {
            if (in_array($val, $online) && in_array($val, $users)) {
                // if (in_array($val, $users)) {
                $MaxOrder = $User->getUser($val, 'maxorder');
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
     * 获取所有部门
     * @param bool|false $update 更新缓存
     * @return mixed
     */
    private function _getAllDepartment($update = false)
    {
        $departments = S('Departments');
        if (empty($departments) || $update) {
            $fields = 'DepartId,DepartName,ParentId,Role,Stores,dpath';
            $departments = M('Department')->order('OrderNo,dpath')->getField($fields);
            S('Departments', $departments);
        }

        return $departments;
    }
}