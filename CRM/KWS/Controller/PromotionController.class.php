<?php
namespace KWS\Controller;

/**
 * Class PromoterController
 * 推广人员功能管理（邀约手）
 * @package KWS\Controller
 */
class PromotionController extends BaseController
{
    private $where = [];

    public function _initialize()
    {
        parent::_initialize();

        // echo $this->user['roleid'];
        if (in_array($this->user['roleid'],[6,7,11,9,10])) {
            // 大区总裁
            $this->where['Kid'] = $this->user['kid'];

        } else if (in_array($this->user['roleid'], [ 9, 10])) {

            $tree = D('Department')->getTree($this->departments, $this->department);
            $pids = array_keys($tree);
            $userModel = D('User');
            $promoters = [];
            if (!empty($pids)) {
                foreach($pids as $key=>$value){
                    $promoter = $userModel->getUserOfDepartId($value);
                    $promoters = array_merge($promoters,$promoter);
                }
                $promoters = array_unique($promoters);
                if(!empty($promoters)){
                    !in_array($this->user['userid'],$promoters)&&array_push($promoters,$this->user['userid']);
                    $this->where['Operator'] = ['in',$promoters];
                }else{
                    $this->where['Operator'] = $this->user['userid'];
                }
                //!empty($promoters)&&$this->where['Operator'] = ['in',$promoters];
                //empty($promoters)&&$this->where['Operator'] = $this->user['userid'];
            } else {
                $this->where['Operator'] = $this->user['userid'];
            }
        } else if ($this->user['roleid'] == '2') {

            // 客服组员
            $this->where['Operator'] = $this->user['userid'];
        }
    }

    /**
     * 所有客户信息
     */
    public function index()
    {
        $map = [];
        $_GET['sf'] == 'yes' && $map = $this->_search();
        // print_r($this->where);
        $map = array_merge($this->where, $map);
        $options = [
            'map' => $map,
        ];
        $this->page('promotion', $options);

        $sum = M('promotion')->where($map)->sum('Charge');
        $this->assign('sum',round($sum,2));
        $promoters = D('Department')->getPromoters();
        $this->assign('promoters', $promoters);

        $this->display();
    }

    /**
     * 获取检索条件
     */
    private function _search()
    {
        $map = [];

        $get = I('get.');
        !empty($get['StoreId'])&& $map['StoreId'] = ['in',$get['StoreId']];
        !empty($get['Operator']) && $map['Operator'] = ['in',$get['Operator']];
        $get['SourceFrom'] > 0 && $map['SourceFrom'] = $get['SourceFrom'];
        $get['Status'] > -1 && $map['Status'] = $get['Status'];
        if($get['SourceFrom']&&!is_numeric($get['SourceFrom'])){
            foreach($this->gsources as $k=>$v){
                if($get['SourceFrom']==$k){
                    $sourceFrom = [];
                    $sourceFrom[] = array_column($v,'sourceid');
                }
                $map['SourceFrom'] = ['in',array_values($sourceFrom[0])];
            }
        }
        if(empty($get['StartUtilityTime'])) {
            $get['StartUtilityTime'] = date('Y-m-d', 0);
        } else {
            $get['StartUtilityTime'] = date('Y-m-d H:i', strtotime('-1 day 21:00', strtotime($get['StartUtilityTime'])));
        }

        if(empty($get['EndUtilityTime'])) {
            $get['EndUtilityTime'] = date('Y-m-d H:i', strtotime('today 21:00'));
        } else {
            $get['EndUtilityTime'] = date('Y-m-d H:i', strtotime('21:00', strtotime($get['EndUtilityTime'])));
        }
        $map['UtilityTime'] = ['between', [$get['StartUtilityTime'], $get['EndUtilityTime']]];
        return $map;
    }

    /**
     * 邀约手客户信息
     * 邀约手添加的手机号、微信号、qq号、微博号
     * 销售客服是不能修改的
     */
    public function addPromotion()
    {
        layout('Layout/win');
        $this->display();
    }

    public function editPromotion()
    {
        $PromotId = I('id');
        $promotion = M('promotion')->where(['PromotId' => $PromotId])->find();
        $this->assign('d', $promotion);
        layout('Layout/win');
        $this->display();
    }


    /**
     * 邀约手变价客户信息
     */
    public function doAddPromotion()
    {
        $model = D('Promotion');
        $valid = $model->create();

        if (!$valid) {
            $error = $model->getError();
            $keys = array_keys($error);
            $messages = array_values($error);

            $this->ajaxReturn([
                'code' => '400',
                'id' => $keys[0],
                'msg' => $messages[0]
            ]);
        }
        $model->Kid = $this->user['kid'];
        if ($_POST['id']) {
            $res = $model->where(['PromotId' => $_POST['id']])->save();
            if(!empty($res)){
                //操作记录
                operateLog($this->user['userid'], $this->user['realname'], '修改了推广资费信息'.$_POST['id'],3);
            }
            $msg = '修改成功';
        } else {
            $model->Operator = $this->user['userid'];
            $model->InsertTime = time();
            $res = $model->add();
            $msg = '添加成功';
            operateLog($this->user['userid'], $this->user['realname'], '添加了推广资费信息'.$res,1);
        }


        if ($res) {
            $this->ajaxReturn([
                'code' => '200',
                'msg' => $msg,
                'layer' => 'yes',
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '操作失败'
            ]);
        }

    }


    /**
     * 执行添加
     */
    public function doEditCustomer()
    {
        $this->edit('Customer', true);

        //操作记录
        operateLog($this->user['userid'], $this->user['realname'], '编辑了客户个人信息' . 3);
    }

    /**
     * 执行添加
     */
    public function doEditBCustomer()
    {
        // $this->edit('Customer', true);
        $data = I('post.');
        $id = I('CustId');
        $res = M('customer')->where(['CustId' => $id])->save($data);
        $rs = M('customerBirth')->where(['CustId' => $id])->save($data);


        if ($res || $rs) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '编辑了客户个人信息' . I('CustName'),3);
            $this->ajaxReturn([
                'code' => '200',
                'msg' => '修改成功',
                'layer' => 'yes'
            ]);
        } else {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '修改失败',
            ]);
        }
    }

    /**
     * 删除推广
     */
    public function delPromotion()
    {
        $rid = M('promotion')->where(['PromotId' => $_GET['id']])->delete();
        if ($rid) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了推广信息' . $_GET['id'], 2);
            $arr['alert'] = '删除成功';
            $arr['redirect'] = U('Promotion/index');
        } else {
            $arr['alert'] = '删除失败';
        }
        $this->ajaxReturn($arr);
    }


    /**
     * 导出推广资费
     */
    public function expPromotion()
    {
        $User = D('User');
        $Source = D('Source');
        $map = [];
        $_GET['sf'] == 'yes' && $map = $this->_search();
        $map = array_merge($this->where, $map);
        $list = M('promotion')->where($map)->order('UtilityTime desc')->select();
        $xlsCell = [
            ['Operator', '邀约手'],
            ['Platform', '推广平台'],
            ['SourceFrom', '来源'],
            ['Store', '门店'],
            ['Charge', '费用'],
            ['CustomerNum', '咨询指标'],
            ['LightExposure', '曝光量'],
            ['ClickNum', '点击/互动量'],
            ['UtilityTime', '使用时间'],
            ['InsertTime', '录入时间'],
        ];

        $data = [];
        foreach ($list as $key => $val) {
            $data[$key]['Operator'] = $User->getUser($val['operator'], 'realname');
            $data[$key]['Platform'] = $Source->getSource($val['sourcefrom'], 'platform');
            $data[$key]['SourceFrom'] = $this->sources[$val['sourcefrom']]['sourcename'];
            $data[$key]['Store'] = $this->brands[$this->stores[$val['storeid']]['brandid']]['brandname'] . $this->stores[$val['storeid']]['storename'];
            $data[$key]['Charge'] = $val['charge'];
            $data[$key]['CustomerNum'] = $val['customernum'];
            $data[$key]['LightExposure'] = $val['lightexposure'];
            $data[$key]['ClickNum'] = $val['clicknum'];
            $data[$key]['UtilityTime'] = $val['utilitytime'];
            $data[$key]['InsertTime'] = date('Y-m-d H:i', $val['inserttime']);
        }

        exportExcel('推广资费' . '-' . $this->user['userid'], '推广资费', $xlsCell, $data);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统推广资费参数数据', 4);
    }
}