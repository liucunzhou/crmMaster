<?php
namespace KWS\Controller;

/**
 * Class UserController
 * 用户管理
 * @package KWS\Controller
 */
class UserController extends BaseController
{
    private $where = [];
    private $pids = [];

    public function _initialize()
    {
        parent::_initialize();

        $roles = D('AuthGroup')->getAllRole();
        $this->assign('roles', $roles);

        if ($this->user['roleid']==6) {
            // 大区总裁
            $this->where['Kid'] = $this->user['kid'];

        } else if (in_array($this->user['roleid'], [5, 4, 3, 9, 10, 11,7])) {

            // 客服经理，客服主管，客服组长
            $tree = D('Department')->getTree($this->departments, $this->departments[$this->user['departid']]);
            $pids = array_keys($tree);
            if (!empty($pids)) {
                $pids[] = $this->user['departid'];
                $this->where['DepartId'] = ['in', $pids];
            } else {
                $this->where['DepartId'] = $this->user['departid'];
            }
        }

    }

    /**
     * 所有用户信息
     */
    public function index()
    {
        $map = [];
        $get = I('get.');
        unset($get['m']);
        if ($get['sf'] == 'yes') {
            $map = array_merge($this->_search(),$this->where);
            //$_GET['DepartId'] < 0 && !empty($this->where['DepartId']) && $map['DepartId'] = $this->where['DepartId'];
        } else {
            $map = $this->where;
        }

        $options = [
            'map' => $map,
            'order' => 'isLock asc,UserId asc'
        ];

        $this->page('user', $options);

        $this->display();
    }

    /**
     * 设置搜索条件
     */
    private function _search()
    {
        $map = [];

        $get = I('get.');
        $get['UserId'] > 0 && $map['UserId'] = $get['UserId'];
        if ($get['DepartId'] > 0) {
            $tree = D('Department')->getTree($this->departments, $this->departments[$get['DepartId']]);
            $pids = array_keys($tree);
            // $pids[] = $this->user['departid'];
            if (!empty($pids)) {
                $map['DepartId'] = ['in', $pids];
            } else {
                $map['DepartId'] = $get['DepartId'];
            }
        }else{
            $tree = D('Department')->getTree($this->departments, $this->departments[$this->user['departid']]);
            $pids = array_keys($tree);
            // $pids[] = $this->user['departid'];
            if (!empty($pids)) {
                $map['DepartId'] = ['in', $pids];
            }
        }
        $get['Sex'] > -1 && $map['Sex'] = $get['Sex'];
        !empty($get['Mobile']) && $map['Mobile'] = $get['Mobile'];
        !empty($get['Email']) && $map['Email'] = $get['Email'];
        $get['isLock'] > -1 && $map['isLock'] = $get['isLock'];
        $get['RoleId'] > 0 && $map['RoleId'] = $get['RoleId'];
        $get['Sex'] > -1 && $map['Sex'] = $get['Sex'];
        !empty($get['UserAccount']) && $map['UserAccount'] = ['like', '%' . $get['UserAccount'] . '%'];
        !empty($get['RealName']) && $map['RealName'] = ['like', '%' . $get['RealName'] . '%'];

        return $map;
    }

    /**
     * 导出用户
     */
    public function expUser()
    {
        $map = [];
        $get = I('get.');
        unset($get['m']);
        if ($get['sf'] == 'yes') {
            $map = $this->_search();
            $_GET['DepartId'] < 0 && !empty($this->where['DepartId']) && $map['DepartId'] = $this->where['DepartId'];
        } else {
            $map = $this->where;
        }

        $field = 'UserId,UserNo,UserAccount,RoleId,RealName,Sex,Mobile,Email,IsLock,DepartId';
        $list = M('User')->field($field)->where($map)->order('UserId desc')->select();
        $isLock = [
           '0' => '有效',
           '1' => '锁定',
           '2' => '离职',
        ];
        $xlsCell = [
            ['UserId','编号'],['DepartName','所在部门'],  ['RoleId','角色'],['UserAccount','账号'],['RealName','真实姓名'],['Sex','性别'],
            ['Mobile','电话'],['Email','邮箱'],['IsLock','是否锁定'],
        ];
        $data = [];
        $DepartModel = D('Department');
        $sex = [
            '0' => '女',
            '1' => '男',
            '2' => '未知',
        ];
        foreach($list as $key=>$val){
            $data[$key]['UserId'] = $val['userid'];
            $data[$key]['DepartName'] = $DepartModel->getDepartment($val['departid'],'departname');
            $data[$key]['RoleId'] = $this->roles[$val['roleid']]['title'];
            $data[$key]['UserAccount'] = $val['useraccount'];
            $data[$key]['RealName'] = $val['realname'];
            $data[$key]['Sex'] = $sex[$val['sex']];
            $data[$key]['Mobile'] = $val['mobile'];
            $data[$key]['Email'] = $val['email'];
            $data[$key]['IsLock'] = $isLock[$val['islock']];
        }

        exportExcel('用户'.'-'.$this->user['userid'],'用户统计', $xlsCell, $data);
        operateLog($this->user['userid'], $this->user['realname'], '导出了crm系统用户信息数据', 4);
    }

    public function addUser()
    {
        $this->display();
    }

    /**
     * 编辑用户信息
     */
    public function editUser()
    {

        $id = I('id');
        $d = M('user')->where(['UserId' => $id])->find();
        $this->assign('d', $d);

        $dstores = [];
        foreach($this->stores as $key=>$val) {
            $SellIds = explode(',', $val['sellids']);
            if(!empty($SellIds) && in_array($d['departid'], $SellIds))
                $dstores[] = $val['storeid'];

            $sellers = explode(',', $val['users']);
            if(!empty($sellers) && in_array($d['userid'], $sellers))
                $mystores[] = $val['storeid'];
        }
        $dstores = array_unique($dstores);
        $this->assign('dstores', $dstores);
        $mystores = array_unique($mystores);
        $this->assign('mystores', $mystores);

        $this->display();
    }

    public function doEditUser()
    {
        $model = D('User');
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

        //根据DepartId获取id保存Kid到数据表
        if (I('DepartId')) {
            $DepartId = I('DepartId');
            $department = D('Department')->getDepartment(I('DepartId'));
            $dpath = explode('-', $department['dpath']);
            if ($dpath[2]) {
                $kid = $dpath[2];
            } elseif (in_array($DepartId, [2, 3, 4, 5, 6, 12, 67,137])) {
                $kid = $DepartId;
            }
            $model->Kid = $kid;
        }
        $sources = I('source');
        $stores =I('store');
        $model->SourceFrom = implode(',',$sources);
        $model->StoreId = implode(',',$stores);
        $pk = $model->getPk();
        $id = I($pk);
        if ($id) {
            $result = $model->save();
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '修改了crm系统用户'.$id.'-' . I('RealName'), 3);
        } else {
            $result = $model->add();
            $id = $model->getLastInsID();
            // D('Department')->getUsersByK($kid);
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '添加了crm系统用户'.$id.'-'. I('RealName'), 1);
        }

        if ($result) {
           // $stores = I('store');
            $Store = M('Store');
            $DepartmentModel = M('department');
            $DepartId&&$departStore = $DepartmentModel->where(['DepartId'=>$DepartId])->find();
            $departStores = explode(',',$departStore['Stores']);
            foreach ($stores as $k => $v) {
                $rs = $Store->find($v);
                if (empty($rs['users'])) {
                    $Store->where(['StoreId' => $v])->save(['Users' => $id]);
                } else {

                    $users = explode(',', $rs['users']);
                    if (!in_array($id, $users)) {
                        array_push($users, $id);
                        $str = implode(',', $users);
                        $Store->where(['StoreId' => $v])->save(['Users' => $str]);
                    }
                }
                //部门和门店
                if(!in_array($v,$departStores)){
                    array_push($departStores,$v);
                }

            }

            $strDEpartStores = implode(',',$departStores);
            $strDEpartStores= substr($strDEpartStores,1);
            $DepartId&&$DepartmentModel->where(['DepartId'=>$DepartId])->save(['Stores'=>$strDEpartStores]);
            //如果在不负责的门店里有这个用户ID则去掉这个ID
            $allStores = $Store->field('StoreId,Users')->select();

            foreach ($allStores as $k => $v) {
                //检测门店客服队列
                $storeQueueName = "StoreSellerQueue-".$v['storeid'];
                $storeQueue = S($storeQueueName);
                $index = array_search($id, $storeQueue);
                if (in_array($v['storeid'], $stores)) {
                    if(!$index){
                        $storeQueue[] = $id;
                        S($storeQueueName,$storeQueue);
                    }else{
                        continue;
                    }
                }else{
                    if($index){
                       // $index !== false && array_splice($storeQueue, $index, 1);
                        unset($storeQueue[$index]);
                        S($storeQueueName, array_unique($storeQueue));
                    }
                }
                if ($v['users']) {
                    $v['stores'] = explode(',', $v['users']);
                    if (!in_array($v['storeid'],$stores)&&in_array($id, $v['stores'])) {
                        $key = array_search($id, $v['stores']);
                        unset($v['stores'][$key]);
                        $v['users'] = implode(',', $v['stores']);
                        $Store->where(['StoreId' => $v['storeid']])->save(['Users' => $v['users']]);
                    }
                }
            }


            //保存角色分组
            $userId = I('UserId');
            if (I('RoleId')) {
                if ($userId) {//老用户添加到分组表
                    $res = M('AuthGroupAccess')->where(['uid' => $userId])->find();
                    if (!empty($res)) {
                        $data['group_id'] = I('RoleId');

                        M('AuthGroupAccess')->where(['uid' => $userId])->save($data);
                    } else {
                        $data['group_id'] = I('RoleId');
                        $data['uid'] = $userId;

                        M('AuthGroupAccess')->add($data);
                    }
                } else {
                    $data = [];
                    //新添加的用户保存到分组表
                    $data['group_id'] = I('RoleId');
                    $data['uid'] = $id;//新生成的用户ID

                    M('AuthGroupAccess')->add($data);
                }
            }

            //更新门店用户缓存
            D("Store")->getStoreUsers(true);
            D("Store")->getAllStore(true);

            //更新用户缓存
            $UserModel = D('User');
            $UserModel->getUser($userId, '', true);
            $UserModel->getAllUser(true);

            // 写日志
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
     * 用户信息
     */
    public function Info()
    {
        // print_r($this->departments);

        $stores = $this->user['storeid'];
        $storeArr = explode(',', $stores);
        $this->assign('storeArr', $storeArr);

        $departId = $this->user['departid'];
        $dpath = D('department')->getDepartment($departId, 'dpath');
        $dpath = explode('-', $dpath);
        $this->assign('dpath', $dpath);

        $user = M('user')->where(['UserId' => $this->user['userid']])->find();
        $this->assign('d', $user);

        $this->display();
    }

    /**
     *保存 修改个人信息
     */
    public function doEditUserInfo()
    {
        $model = D('User');
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

        $data = I('post.');
        $res = M('user')->where(['UserId' => $this->user['userid']])->save($data);
        if ($res) {
            operateLog($this->user['userid'], $this->user['realname'], '修改了用户个人信息' . I('RealName'), 3);
            $arr = [
                'code' => '200',
                'msg' => '个人信息保存成功'
            ];
        } else {
            $arr = [
                'code' => '400',
                'msg' => '个人信息保存失败'
            ];
        }

        $this->ajaxReturn($arr);
    }

    /**
     * 重置用户登录密码
     */
    public function repassword()
    {
        if (IS_POST) {
            $data = I('post.');
            if (strlen($data['OldUserPwd']) < 6) {
                $this->ajaxReturn([
                    'code' => '400',
                    'msg' => '原密码长度不正确'
                ]);
            }

            if (strlen($data['UserPwd']) < 6) {
                $this->ajaxReturn([
                    'code' => '400',
                    'msg' => '新密码长度不正确'
                ]);
            }

            if (strlen($data['ReUserPwd']) < 6) {
                $this->ajaxReturn([
                    'code' => '400',
                    'msg' => '确认密码长度不正确'
                ]);
            }

            if ($data['UserPwd'] != $data['ReUserPwd']) {
                $this->ajaxReturn([
                    'code' => '400',
                    'msg' => '新密码两次输入不一致'
                ]);
            }

            $map['UserId'] = $this->user['userid'];
            $user = M('user')->where($map)->find();
            if ($user['userpwd'] != md5($data['OldUserPwd'])) {
                $this->ajaxReturn([
                    'code' => '400',
                    'msg' => '原密码不正确'
                ]);
            }

            $result = M('user')->where($map)->save(['UserPwd' => md5($data['UserPwd'])]);
            if ($result) {
                //操作记录
                operateLog($this->user['userid'], $this->user['realname'], '修改了用户个人信息密码' . $this->user['realname'], 3);
                $this->ajaxReturn([
                    'code' => '200',
                    'msg' => '密码重置成功',
                    'redirect' => U('User/info')
                ]);
            } else {
                $this->ajaxReturn([
                    'code' => '500',
                    'msg' => '密码重置失败'
                ]);
            }

        } else {
            $this->display();
        }

    }

    public function delUser()
    {
        $id = I('id');
        $result = M('user')->where(['UserId' => $id])->save(['isLock' => 1]);
        if ($result) {
            //操作记录
            operateLog($this->user['userid'], $this->user['realname'], '删除了用户账户' . D('User')->getUser($id, 'realname'), 2);
            $return = ['code' => '200', 'msg' => '删除成功', 'reload' => 'yes'];
        } else {
            $return = ['code' => '500', 'msg' => '删除失败'];
        }

        // 写入log日志
        $this->ajaxReturn($return);
    }

    public function resetMaxOrder()
    {
        $id = I("id");
        $result = S("Count-{$id}", 0);

        if($result) {
            $return = [
                'code'  => '200',
                'msg'   => '更新今日最大接单数成功'
            ];
        } else {
            $return = [
                'code'  => '500',
                'msg'   => '更新今日最大接单数失败'
            ];
        }

        $this->ajaxReturn($return);
    }

    /**
     * 切换在线状态
     */
    public function triggerLine()
    {
        $name = 'UserOnline-'.$this->user['userid'];
        $isOnline = S($name);
        if($isOnline==1){
            S($name,2,43200);
            $result = [
                'isOnline' => 2
            ];
        }else{
            S($name,1,43200);
            $result = [
                'isOnline' => 1
            ];
        }

        $this->ajaxReturn($result);
    }


}
