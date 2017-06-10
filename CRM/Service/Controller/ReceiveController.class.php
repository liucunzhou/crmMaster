<?php
namespace Service\Controller;

use Think\Controller;

class ReceiveController extends Controller
{
    /**
     * 批量分配数据
     */
    public function distribute()
    {
        $customers = json_decode($_POST['data'], true);
        $queueName = 'DistributeFailedQueue';
        $queue = S($queueName);

        $data = [];
        foreach ($customers as $key=>$customer){
            $result = $this->doAddCustomer($customer);

            \Think\Log::record($result['code']);

            if($result['code'] != '200') {
                array_push($queue, $customer['id']);
                if (isset($result['repeat']) && $result['repeat']==1) {
                    $data[$customer['id']] = -1;
                } else {
                    $data[$customer['id']] = 0;
                }
            } else {
                $data[$customer['id']] = 1;
            }

        }

        echo json_encode($data);
    }

    public function doAddCustomer($slave)
    {
        // 判断数据重复性
        $result = $this->_checkRepeat($slave);
        if ($result['code'] == '400') return $result;
        if($result['code'] != '200') {
            // 重复设置
            $salesId = $result['salesId'];
            if ($result['repeat'] == 2) {
                return [
                    'code' => '300',
                    'repeat' => 1,
                    'data' => $slave
                ];
            } elseif (($result['repeat'] == 1)) {
                $slave['IsRepeat'] = 1;
            }
        }

        $CustomerRobot = D('CustomerRobot');
        $distibutionResult = $CustomerRobot->averageDistribution($slave['storeid'], $slave['sourceid']);
        // 分配失败后
        if ($distibutionResult['sales'] == 0) {
            return [
                'code' => '500',
                'data' => $slave
            ];
        } else {
            $salesId = $distibutionResult['sales'];
        }
        
        // 保存客资数据
        $CustomerModel = M('Customer');
        // $CustomerModel->startTrans();

        // 设置客户号
        $custData = $this->_buildCustomerData($slave, $salesId);
        $rs1 = $CustomerModel->add($custData);
        $lastId = $CustomerModel->getLastInsID();

        // 分配表添加记录
        $assignData = $this->_buildAssignData($custData, $lastId);
        $rs2 = M('assign')->add($assignData);

        if ($rs1 && $rs2) {
            $CustomerModel->commit();
            // 设置最大接单数
            $countCache = "Count-".$salesId;
            $count = S($countCache) + 1;
            S($countCache, $count);

            return [
                'code'  => '200',
                'data'  => $slave
            ];
        } else {

            // $CustomerModel->rollback();
            return [
                'code' => '500',
                'data' => $slave
            ];
        }
    }

    /**
     * 检查是否是重复客资
     * @param $custData 客资信息
     * @return array
     */
    private function _checkRepeat($custData)
    {
        // 检测是否存在该客资
        $map = [];
        !empty($custData['mobile']) && $map['Mobile'] = $custData['mobile'];
        !empty($custData['qq']) && $map['QQ'] = $custData['qq'];
        !empty($custData['wechat']) && $map['WeChat'] = $custData['wechat'];
        !empty($custData['weiboname']) && $map['WeiboName'] = $custData['weiboname'];

        if (empty($map)) {
            return [
                'code' => '400',
                'msg'  => 'Data is empty'
            ];
        } else {
            $map['_logic'] = 'OR';
        }

        $where['StoreId'] = $custData['storeid'];
        $where['_complex'] = $map;

        $fields = 'Kid,salseId,InsertTime';
        $customers = M('Customer')->field($fields)->where($where)->order('InsertTime desc')->select();

        $repeat = 0;
        $time1 = strtotime('-3days 00:00:01');
        $time2 = strtotime('today 23:59:59');
        foreach ($customers as $key=>$val)
        {
            $time = strtotime($val['inserttime']);
            if($time > $time1 && $time < $time2){
                $repeat = 2;
            } else {
                $repeat = 1;
            }
        }

        if (empty($customers)) {
            // 非重复咨询
            return [
                'code'  => '200',
                'repeat' => $repeat,
                'msg'   => 'Data is Effective'
            ];
        } else { // 重复咨询

            // 获取当前用户的大区ID
            return [
                'code' => '300',
                'repeat' => $repeat,
                'msg' => 'Data is repeat'
            ];
        }
    }

    /**
     * 构造客资数据
     * @param $custData array 包含门店数据,来源数据,推广人员数据
     * @param $salesId int 销售人员ID
     * @return array
     */
    private function _buildCustomerData($custData, $salesId)
    {
        // 设置客资编号
        $data['CustNo'] = date("Ymdhis", time()) . rand(1000, 9999);

        // 设置门店ID,来源ID
        $data['StoreId'] = $custData['storeid'];
        $data['SourceFrom'] = $custData['sourceid'];
        !empty($custData['mobile']) && $data['Mobile'] = $custData['mobile'];
        !empty($custData['qq']) && $data['QQ'] = $custData['qq'];
        !empty($custData['wechat']) && $data['WeChat'] = $custData['wechat'];
        !empty($custData['weiboname']) && $data['WeiboName'] = $custData['weiboname'];
        !empty($custData['keywords']) && $data['Keywords'] = $custData['keywords'];

        $User = D('User');
        // 客服信息
        $sales = $User->getUser($salesId);
        $data['Kid'] = $sales['kid'];
        $data['salseId'] = $salesId;
        $data['DepartId'] = $sales['departid'];

        // 推广信息
        $promoter = $User->getUser($custData['promoterid']);
        $data['Opeartor'] = $promoter['userid'];
        $data['PdepartId'] = $promoter['departid'];
        // 设置备注信息
        $data['Remark'] = $custData['remark'];

        // 设置是否重复信息
        $data['IsRepeat'] = $custData['IsRepeat'];

        // 设置录入时间,与金数据接口录入时间同步
        $data['InsertTime'] = date('Y-m-d H:i:s');

        return $data;
    }

    private function _buildAssignData($custData, $lastInsertId)
    {

        $data = [];
        $data['CustId'] = $lastInsertId;
        $data['CustNo'] = $custData['CustNo'];
        $data['InitUser'] = $custData['salseId'];
        $data['NowUser'] = $custData['salseId'];
        $data['Status'] = 0;
        $data['InsertTime'] = date('Y-m-d H:i:s');
        $data['UserCount'] = 1;
        $data['DepartId'] = 0;
        $data['Invitor'] = $custData['Opeartor'];
        $data['InvitDepart'] = $custData['PdepartId'];
        $data['IsReward'] = 0;
        $data['IsWashing'] = 0;
        $data['AppointType'] = 3;

        return $data;
    }
}