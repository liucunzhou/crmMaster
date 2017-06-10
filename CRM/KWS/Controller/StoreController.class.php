<?php
namespace KWS\Controller;

/**
 * Class StoreController
 * 门店管理
 * @package KWS\Controller
 */
class StoreController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();

        // 设置业务
        $this->business = [
            'photo' => '婚纱摄影',
            'hall' => '婚礼堂',
            'baby' => '宝宝摄影',
            'wedding' => '婚纱礼服',
            'dress' => '男士西装',
            'birth' => '月子中心'
        ];
        $this->assign('business', $this->business);

        // 集团
        $compays = [
            1 => '婚宴事业部',
            2 => '嘉丰集团',
            3 => '嘉艺集团',
            4 => '嘉铄集团',
            5 => '礼服集团',
            6 => '特区1集团',
            7 => '特区2集团',
            8 => '翰林宝宝',
        ];
        $this->assign('companys', $compays);

        // 获取大区信息
        $ks = D('Department')->getChildren(1);
        $this->assign('ks', $ks);

        $multiStores = D('Store')->devideGroup();
        $multiStoreIds = $multiStores['multy'];
        $this->assign('multiStoreIds',$multiStoreIds);
    }

    /**
     * 门店列表
     */
    public function index()
    {
        $where = [];
        if($this->user['roleid'] != 12) $where['DepartId'] = $this->user['kid'];

        // 搜索部分
        $map = [];
        $map = $this->_search();
        $map = array_merge($where, $map);

        $this->page('store', [
            'map' => $map,
            'order' => 'BrandId',
            'display' => 25
        ]);
        $this->display();
    }

    private function _search()
    {
        $map = [];
        $get = I("get.");

        $get['StoreId'] > 0 && $map['StoreId'] = $get['StoreId'];
        $get['GroupId'] > 0 && $map['GroupId'] = $get['GroupId'];
        !empty($get['Business']) && $map['Business'] = $get['Business'];
        $get['BrandId'] > 0 && $map['BrandId'] = $get['BrandId'];
        $get['DepartId'] > 0 && $map['DepartId'] = $get['DepartId'];
        $get['OrderNo'] > 0 && $map['OrderNo'] = $get['OrderNo'];
        !empty($get['StoreName']) && $map['StoreName'] = ['like', $get['StoreName'] . '%'];

        return $map;
    }

    /**
     * 添加门店信息
     */
    public function addStore()
    {
        $this->display();
    }

    /**
     * 编辑门店信息
     */
    public function editStore()
    {
        $id = I('id');
        $d = M('store')->where(['StoreId' => $id])->find();
        $this->assign('d', $d);
        $sells = explode(',', $d['sellids']);
        $this->assign('sells', $sells);
        $users = explode(',', $d['users']);
        $this->assign('users', $users);
        $d['departid']&&$kDeparts = D('Department')->getSellDepartment($d['departid'],'seller');
        $this->assign('kdepart',$kDeparts);
        $this->display();
    }

    /**
     * 执行编辑、添加
     */
    public function doEditStore()
    {
        $model = D('Store');
        $sells = I('sellids');
        if(empty($sells)){
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '必须选择部门'
            ]);
        }
        $validate = $model->create();
        if (!$validate) {
            $error = $model->getError();
            $keys = array_keys($error);
            $messages = array_values($error);

            $this->ajaxReturn([
                'code' => '400',
                'id' => $keys[0],
                'msg' => $messages[0]
            ]);
        }

        $pk = $model->getPk();
        $id = I($pk);
        if ($id) {
            $result = $model->save();
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '修改了门店信息' . I('StoreName'), 3);
        } else {
            $result = $model->add();
            $id = $model->getLastInsID();
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了门店信息' . I('StoreName'), 1);
        }


        //print_r($sells);exit;
        $Department =  M('Department');
        $storeDeparts = $Department->getField('DepartId,Stores');


        //更新tk_store表里的users字段
        $userIds = I('seller');
        $storeId = I('StoreId');
        $users = implode(',',$userIds);
        $sells1 = implode(',',$sells);
        M('store')->where(['StoreId' => $storeId])->save(['Users'=>$users,'SellIds' => $sells1]);
        D('Store')->getStoreUsers($storeId);
       // D('Store')->getAllStore(true);

        if($result){
            $arr['code'] = '200';
            $arr['msg'] = '保存成功';
            $arr['redirect'] = $this->referer;
        } else {
            $arr['code'] = '500';
            $arr['msg'] = '保存失败';
        }

        $this->ajaxReturn($arr);
    }
    /**
     * 删除门店信息
     */
    public function delStore()
    {
        $id = I('id');

        $result = M('store')->where(['StoreId' => $id])->delete();
        $store = D('Store')->getAllStore();
        if ($result) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了门店参数数据' . $store[$id]['storename'], 2);
            $res['code'] = '200';
            $res['msg'] = '删除成功';
            $res['reload'] = 'yes';

        } else {
            $res['code'] = '400';
            $res['msg'] = '删除失败';

        }
        $this->ajaxReturn($res);
    }

    /**
     * 编辑门店用户
     */
    public function editUsers()
    {
        $storeId = I('id');
        $users = M('store')->where(['StoreId' => $storeId])->getField('Users');
        $userArr = explode(',', $users);
        $this->assign('userArr', $userArr);
        layout('Layout/win');
        $this->display();
    }

    public function delUser()
    {
        $userId = I('uid');
        $storeId = I('storeId');
        $users = M('store')->where(['StoreId' => $storeId])->getField('Users');
        $userArr = explode(',', $users);
        $key = array_search($userId, $userArr);
        unset($userArr[$key]);
        $users = implode(',', $userArr);
        $res = M('store')->where(['StoreId'=>$storeId])->save(['Users' => $users]);
        if ($res) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了门店'. $this->stores[$storeId]['storename'].'用户' .$userId, 2);
            $arr = [
                'code' => '200',
                'msg' => '删除用户成功',
                'layer' => 'yes'
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '删除用户失败',
            ];
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 导出门店
     *
     */
    public function expStore()
    {
        set_time_limit(0);

        $where = [];
        if($this->user['roleid'] != 12) $where['DepartId'] = $this->user['kid'];

        // 搜索部分
        $map = [];
        $map = $this->_search();
        $map = array_merge($where, $map);
        $field = 'StoreId,GroupId,BrandId,DepartId,SellIds,Users,StoreName,Business,OrderNo,CreateTime';
        $list = M('Store')->field($field)->where($map)->order('StoreId desc')->select();

        $xlsCell = [
            ['StoreId','门店Id'],['GroupName','集团名称'],  ['Business','门店业务'],['StoreName','门店名称'], ['DepartName','大区'],
            ['SellIds','销售部门'], ['OrderNo' , '排序']
        ];
        $data = [];
        foreach($list as $key=>$val){
            $data[$key]['StoreId'] = $val['storeid'];
            $data[$key]['GroupName'] = $this->companys[$val['groupid']];
            $data[$key]['Business'] = $this->business[$val['business']];
            $data[$key]['StoreName'] = $this->brands[$val['brandid']]['brandname'].$val['storename'];
            $data[$key]['DepartName'] = D('Department')->getDepartment($val['departid'],'departname');
            $sellids = explode(',',$val['sellids']);
            $departments = '';
            foreach($sellids as $k=>$v){
                $departments = $departments.$this->departments[$v]['departname'].'/';
            }
            $data[$key]['SellIds'] = $departments;
            $data[$key]['OrderNo'] = $val['orderno'];

        }


        exportExcel('门店'.'-'.$this->user['userid'],'门店统计', $xlsCell, $data);

    }

  
}