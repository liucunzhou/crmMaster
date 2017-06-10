<?php
namespace KWS\Controller;

class CjController extends BaseController
{
    private $where = [];

    public function _initialize()
    {
        parent::_initialize();

        if (in_array($this->user['roleid'], [6, 7])) {
            // 大区总裁
            $this->where['Kid'] = $this->user['kid'];
        } else if (in_array($this->user['roleid'], [5, 4, 3])) {
            // 客服经理，客服主管，客服组长
            $tree = D('Department')->getTree($this->departments, $this->department);
            $pids = array_keys($tree);
            if (!empty($pids)) {
                $this->where['DepartId'] = ['in', $pids];
            } else {
                $this->where['DepartId'] = $this->user['departid'];
            }
            /**
             * $this->where['salseId'] = $this->user['userid'];
             * $this->where['_logic'] = 'or';
             */
        } else if ($this->user['roleid'] == '1') {

            // 客服组员
            $this->where['salseId'] = $this->user['userid'];
        }

        $this->where['Del'] = 0;
    }

    /**
     * 获取检索条件
     */
    private function _search()
    {
        $map = [];
        $get = I('get.');
        $get['CustId'] > 0 && $map['CustId'] = (int)$get['CustId'];
        !empty($get['StoreId']) && $map['StoreId'] = ['in', $get['StoreId']];
        !empty($get['Status']) && $map['Status'] = ['in', $get['Status']];
        !empty($get['Opeartor']) && $map['Opeartor'] = ['in', $get['Opeartor']];
        !empty($get['salseId']) && $map['salseId'] = ['in', $get['salseId']];
        !empty($get['SourceFrom']) && $map['SourceFrom'] = ['in', $get['SourceFrom']];
        $get['IsOrder'] > -1 && $map['IsOrder'] = $get['IsOrder'];
        if ($get['CustType'] != '-1' && !empty($get['Customer'])) {
            $map[$get['CustType']] = $get['Customer'];
        }

        if (empty($get['StartInsertTime'])) {
            $get['StartInsertTime'] = date('Y-m-d', 0);//date('Y-m-d',strtotime('-1day')).' 21:00:00';
        } else {
            $get['StartInsertTime'] = date('Y-m-d H:i', strtotime('-1 day 21:00', strtotime($get['StartInsertTime'])));
        }

        if (empty($get['EndInsertTime'])) {
            $get['EndInsertTime'] = date('Y-m-d H:i', strtotime('today 21:00'));
        } else {
            $get['EndInsertTime'] = date('Y-m-d H:i', strtotime('21:00', strtotime($get['EndInsertTime'])));
        }
        // $map['Del'] = 0;
        $map['InsertTime'] = ['between', [$get['StartInsertTime'], $get['EndInsertTime']]];
        $get['CustName'] = trim($get['CustName']);
        if (!empty($get['CustName'])) {
            $where['CustName'] = ['like', $get['CustName']];
            $where['Mobile'] = ['like', '%' . $get['CustName'] . '%'];
            $where['WeiboName'] = ['like', $get['CustName'] . '%'];
            $where['WeChat'] = ['like', $get['CustName'] . '%'];
            $where['QQ'] = ['like', $get['CustName'] . '%'];
            $where['Wang'] = ['like', $get['CustName'] . '%'];
            $where['Keywords'] = ['like', $get['CustName'] . '%'];
            $where['_logic'] = 'OR';
        }
        !empty($where) && $map['_complex'] = $where;
        return $map;
    }
    /**
     * 业务统计预览
     */
    public function index()
    {
        $controller = strtolower(CONTROLLER_NAME);
        $first = array_shift($this->menus[$controller]);
        sort($first);
        $this->redirect($first[0]['url']);
    }

    /**
     * 修改离职客服列表页面
     */
    public function dimission()
    {
        $map = [];
        $_GET['sf'] && $map = $this->_search();
        $map = array_merge($map, $this->where);

        //权限限制每个人用户只能看自己所在k的用户数据
        $options = [
            'map' => $map,
            'field' => 'CustId,CustNo,CustName,Mobile,QQ,WeChat,WeiboName,InsertTime,Opeartor,SourceFrom,Status,salseId,StoreId,IsRepeat,Color,Remark,LastVisitTime,IsOrder'
        ];
        $this->page('customer', $options);
        // 回去推广列表
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);
        $this->display();
    }

    //c重新分配

    public function doEditDimission()
    {

        $seller = I('seller');
        $custids = I('ids');
        $custs = explode(',', $custids);

        $customerModel = M('customer');
        $assignModel = M('assign');
        $d['salseId'] = $seller;
        $fileds = $customerModel->getDbFields();
        foreach ($custs as $k => $v) {
            $map['CustId'] = $v;

            $data['Kid'] = $this->user['kid'];
            $data['salseId'] = $seller;
            $data['DepartId'] = D('User')->getUser($seller, 'departid');

            $res = $customerModel->where($map)->save($data);
            unset($data);
            //分配表
            $assign = [
                'NowUser' => $seller,
                'DepartId' =>  D('User')->getUser($seller, 'departid'),
            ];
            $res1 = $assignModel->where($map)->save($assign);
            unset($assign);
            operateLog($this->user['userid'], $this->user['realname'], '重新分配客咨给客服'.$seller, 3);
        }

        if ($res && $res1) {
            $arr = [
                'code' => '200',
                'msg' => '重新分配成功',
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '重新分配失败',
            ];
        }
        $this->ajaxReturn($arr);


    }


}