<?php
namespace KWS\Model;

use Think\Model;

class AssignModel extends Model
{
    protected $_validate = [
        ['title', 'require', '不能为空'],
        ['title', 'email', '不能为空'],
        ['title', 'unique', '不能为空'],
        ['title', 'isUnique', '不能为空', 3, 'function'],
    ];

    /**
     * 获取分配的信息详情
     * @param string $CustNo 客户编号
     * @param string $field 要返回的字段名称
     * @return mixed
     */
    public function getFieldInfo($CustNo, $field = '')
    {
        $assign = $this->getAssign($CustNo);

        if ($field == 'nowuser') {

            return D('User')->getUser($assign['nowuser'], 'realname');

        } elseif ($field == 'departid') {

            return D('Department')->getDepartment($assign['departid'], 'departname');

        } elseif ($field == 'status') {
            switch ($assign['status']) {
                case '-2':
                    $status = '待分配';
                    break;
                case '-1':
                    $status = '无人接受';
                    break;
                case '0':
                    $status = '未接受';
                    break;
                case '1':
                    $status = '已接受';
                    break;
                case '2':
                    $status = '暂未接受';
                    break;
                default :
                    $status = '已接受';
            }
            return $status;
        } elseif ($field == 'appointtype') {
            switch ($assign['appointtype']) {
                case '0':
                    $type = '自动分配';
                    break;
                case '1':
                    $type = '组长指定';
                    break;
                case '2':
                    $type = '指定销售';
                    break;
                case '3':
                    $type = '机器人';
                    break;
                case '4':
                    $type = '奖励';
                    break;
                case '-1':
                    $type = '之前录入';
                    break;
            }
            return $type;
        }
    }

    /**
     * 获取分配销售客服详情
     * @param $CustNo 客户ID
     * @return mixed
     */
    public function getAssign($CustNo)
    {
        $cache = 'Assign-' . $CustNo;
        $assign = S($cache);
        if (empty($customer)) {
            $assign = $this->where(['CustNo' => $CustNo])->find();
            S($cache, $assign, ['expire' => 3600]);
        }
        return $assign;
    }

    public function getAssigndata($id, $field)
    {
        $assign = $this->getAssign($id);
        if ($field == 'nowuser') {
            $nowuser = D('User')->getUser($assign['nowuser'], 'realname');
            return $nowuser;
        }elseif($field == 'status'){
            switch ($assign['status']) {
                case '0':
                    $status = '未接受';
                    break;
                case '1':
                    $status = '已接受';
                    break;
                case '2':
                    $status = '暂未接受';
                    break;
                default :
                    $status = '已接受';
            }

            return $status;
        }
    }
}