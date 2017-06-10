<?php
namespace KWS\Controller;


class ApiController extends CommonController
{
    public function doAddCustomerBak()
    {
        $request = file_get_contents('php://input');
        file_put_contents('iptxt',$request."\n", FILE_APPEND);

        $post = json_decode($request, true);
        foreach($post['entry'] as $key=>$value) {
            // 检测手机
            if(isMobile($value)) {
                $data['Mobile'] = $value;
            }

            // 检测门店
            if(stripos($value, '__store__') === 0) {
                $storeArr = explode("__", $value);
                $data['StoreId'] = $storeArr[2];
            }

            // 检测来源
            if(stripos($value, '__platform__') === 0) {
                $storeArr = explode("__", $value);
                $data['SourceFrom'] = $storeArr[2];
            }

            // 检测邀约手
            if(stripos($value, '__promoter__') === 0) {
                $storeArr = explode("__", $value);
                $data['Opeartor'] = $storeArr[2];
            }
        }

        empty($data) && $data = I("post.");

        // 检测无效数据
        if(empty($data['Mobile']) || empty($data['StoreId']) || empty($data['SourceFrom']) || empty($data['Opeartor'])) {
            echo '200';
            exit;
        }

        // 获取城市
        if(empty($post['entry']['info_remote_ip'])) {
            $ip = get_client_ip();
        } else {
            $ip = $post['entry']['info_remote_ip'];
        }
        $url = "http://restapi.amap.com/v3/ip?ip={$ip}&output=json&key=cfb78a03eefa2b6cc9a401cd25599e8c";
        $amap = file_get_contents($url);
        $position = json_decode($amap, true);
        $this->data['city'] = $position['city'];

        // 初始化个人信息
        $user = M("user")->find($data['Opeartor']);
        $jlog = [
            'StoreId' => $data['StoreId'],
            'Mobile' => $data['Mobile'],
            'SourceFrom' => $data['SourceFrom'],
            'Opeartor' => $data['Opeartor'],
            'City' => $data['city'],
            'Status' => 0,
            'InsertTime' => date('Y-m-d H:i:s')
        ];
        $result = M("JinshujuLog")->add($jlog);
        if($request) {
            echo '200';
        } else {
            echo '500';
        }
    }

    /**
     * 邀约手邀约
     */
    public function doAddCustomer()
    {
        $kid = $this->user['kid'];

        // 添加咨询
        $CustomerModel = D('Customer');
        $valid = $CustomerModel->create($this->data);
        if (!$valid) {
            $error = $CustomerModel->getError();
            $jlog = [
                'StoreId' => $this->data['StoreId'],
                'Mobile' => $this->data['Mobile'],
                'SourceFrom' => $this->data['SourceFrom'],
                'Opeartor' => $this->data['Opeartor'],
                'City' => $this->data['city'],
                'Status' => -1,
                'InsertTime' => date('Y-m-d H:i:s')
            ];
            M("JinshujuLog")->add($jlog);
        }

        // 自动分配数据
        $sales = $this->_autoAssign($kid);
        if (empty($sales)) {
            // 没有客服，记录LOG
            $jlog = [
                'StoreId' => $this->data['StoreId'],
                'Mobile' => $this->data['Mobile'],
                'SourceFrom' => $this->data['SourceFrom'],
                'Opeartor' => $this->data['Opeartor'],
                'City' => $this->data['city'],
                'Status' => 0,
                'InsertTime' => date('Y-m-d H:i:s')
            ];
            M("JinshujuLog")->add($jlog);

            echo "200";
            return;
        } else {
            // 客服接单数累加
            // $expireTime = 43200 - date("h") * 3600 - date("i") * 60 - date("s");
            $acceptNum = (int)S('Count-' . $sales);
            S('Count-'.$sales, $acceptNum + 1);
        }

        $CustomerModel->startTrans();
        // 设置销售
        $CustomerModel->salseId = $sales;
        $DepartId = D('User')->getUser($sales, 'departid');
        $CustomerModel->DepartId = $DepartId;

        // 设置客户号
        $custNo = date("Ymdhis", time()) . rand(1000, 9999);
        $CustomerModel->CustNo = $custNo;

        // 设置城市到备注信息
        $CustomerModel->Remark = $this->data['city'];
        $rs1 = $CustomerModel->add();

        // 分配表添加记录
        $lastId = $CustomerModel->getLastInsID();
        $assign = [
            'CustId' => $lastId, 'CustNo' => $custNo,
            'InitUser' => $sales, 'NowUser' => $sales,
            'Status' => 0, 'InsertTime' => date('Y-m-d H:i:s'),
            'UserCount' => 1, 'DepartId' => 0,
            'Invitor' => $this->user['userid'],
            'InvitDepart' => $this->user['departid'],
            'IsReward' => 0, 'IsWashing' => 0,
            'AppointType' => I('AppointType'),
        ];
        $rs2 = M('assign')->add($assign);

        if ($rs1 && $rs2) {
            $CustomerModel->commit();
            // $arr = ['code' => '200', 'msg' => '客户分配成功', 'redirect' => $this->referer];
            echo '200';
        } else {

            // $arr = ['code' => '500', 'msg' => '客户分配失败,请稍后重试...'];
            $CustomerModel->rollback();
            echo '500';
        }
    }

    /**
     * 新的分配模式
     */
    private function _autoAssign()
    {
        // echo "开始计算...\n";
        $get = $this->data;
        file_put_contents('abc.txt', json_encode($get));
        $store = $this->stores[$get['StoreId']];
        // 负责该门店的所有销售部门
        $StoreDepartments = explode(',', $store['sellids']);
        // 多部门的情况
        $sales = 0;
        $count = count($StoreDepartments);
        if($count > 1) {

            $amounts = [];
            $Customer = M('Customer');
            $yestoday = date('Y-m-d H:i:s', strtotime('-1 day 21:00'));
            $today = date('Y-m-d H:i:s', strtotime('Today 21:00'));
            foreach ($StoreDepartments as $key => $val) {
                $map['StoreId'] = $get['StoreId'];
                $map['DepartId'] = $val;
                // $map['SourceFrom'] = $get['SourceFrom'];
                $map['Del'] = 0;
                $map['InsertTime'] = ['between', [$yestoday, $today]];
                $amounts[$val] = $Customer->where($map)->count();
            }

            $max = max($amounts);
            $min = min($amounts);
            if($max - $min > $count)
            {
                // 总数相差个部门总数的情况，直接将咨询分配给最少的那一组
                $department = 0;
                foreach ($amounts as $key=>$val) {
                    if($min == $val) {
                        $department = $key;
                        break;
                    }
                }
                if ($department == 0) return 0;
                $sales = $this->departmentSellerQueue($department);

            } else {
                // 否则按照门店队列来
                $department = $this->storeDepartmentQueue($get['StoreId'], $get['SourceFrom']);
                $sales = $this->departmentSellerQueue($department);

            }

        } else {
            // 单个部门
            $department = $store['sellids'];
            $sales = $this->storeSellerQueue($get['StoreId']);
        }

        return $sales;
    }

    /**
     * 非PK门店，接单客服队列
     */
    private function storeSellerQueue($StoreId)
    {
        $sales = 0;
        $online = S('online');
        $QueueName = "StoreSellerQueue-".$StoreId;
        $queue = S($QueueName);
        if(empty($queue)) {
            $queue = explode(",", $this->stores[$StoreId]['users']);
            S($QueueName, $queue);
        }

        $User = D('User');
        foreach ($queue as $key=>$val) {
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
    private function storeDepartmentQueue($StoreId, $source)
    {
        $QueueName = "queue-store-department-".$StoreId.'-'.$source;
        $queue = S($QueueName);
        if(empty($queue)) {
            $store = $this->stores[$StoreId];
            $queue = explode(',', $store['sellids']);
            S($QueueName, $queue);
        }

        $department = array_shift($queue);
        array_push($queue, $department);
        S($QueueName, $queue);

        return $department;
    }


    private function departmentSellerQueue($department)
    {
        file_put_contents("department.txt", $department);
        $User = D('User');

        $sales = 0;
        $online = S('online');
        // 获取该门店 此部门下的所有销售
        $StoreId = $this->data["StoreId"];
        $store = $this->stores[$StoreId];
        $users = explode(',', $store['users']);

        $QueueName = "queue-department-".$department.'-store-'.$StoreId;
        file_put_contents('queue2.txt', $QueueName);
        $queue = S($QueueName);
        if(empty($queue)) {
            foreach($users as $key=>$val) {
                // 获取用户的部门
                $DepartId = $User->getUser($val, 'departid');
                if($DepartId == $department) {
                    $queue[] = $val;
                }
            }

            $queue = array_unique($queue);
            S($QueueName, $queue);
        }
        file_put_contents('queue.txt', json_encode($queue));

        foreach ($queue as $key=>$val) {
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
        file_put_contents('queue.txt', json_encode($queue));
        return $sales;
    }
}