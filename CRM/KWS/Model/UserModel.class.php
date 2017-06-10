<?php
namespace KWS\Model;

use Think\Model;

class UserModel extends Model
{
    protected $patchValidate = true;

    protected $_validate = [
        ['UserAccount', 'require', '用户名不能为空'],
        ['UserAccount', '', '该用户名已经注册', '0', 'unique', 1],
        ['UserPwd', '6,18', '密码长度不能少于6', 0, 'length', 1],
    ];
    protected $_auto = [
        ['UserPwd', 'md5', 1, 'function'],
        ["UserPwd", "buildPass", 2, "callback"],
        ['CreateTime', 'time', 3, 'function'],
        ['StoreId', 'setStores', 3, 'callback'],
        ['kid', 'setKid', 3, 'callback'],
    ];

    public function buildPass($passwd)
    {
        return !empty($passwd) ? md5($passwd) : false;
    }

    public function getAllUser($update = false,$isLock=false)
    {
        $users = S('Users');
        if(empty($users) || $update||$isLock) {
            $isLock&&$map['isLock'] = 0;
            $fields = 'UserId,UserNo,UserAccount,UserPwd,IsOnline,RoleId,RealName,Sex,Age,DepartId,Email,Tel,Mobile,isLock,MaxOrder,Kid,StoreId';
            $users = $this->getField($fields);
            S('Users', $users);
        }

        return $users;
    }

    /**
     * 获取用户信息
     * @param $id 管理员用户ID
     * @param string $field 要获取的字段名称
     * @return mixed
     */
    public function getUser($id, $field = '', $update = false)
    {
        $user = S('User-' . $id);
        if (empty($user) || $update) {
            $user = $this->where(['UserId' => $id])->find();
            S('User-' . $id, $user);
        }


        if (empty($field)) {
            return $user;
        } else {
            return $user[$field];
        }
    }

    /**
     * @param $DepartId
     * @param bool|false $isLast
     * @return int
     */
    public function getDepartId($DepartId, $isLast = false)
    {
        $department = D('Department')->getDepartment($DepartId, 'dpath');
        $dpath = explode('-', $department);
        if ($isLast) {
            $last = array_pop($dpath);
            return isset($dpath[$last]) ? $dpath[$last] : 0;
        } else {
            return isset($dpath[2]) ? $dpath[2] : 0;
        }

    }

    public function getUserStores()
    {
        $userStores = M('user')->getField('StoreId');
    }

    public function getUserByName($name, $field = '', $update = false)
    {
        $user = $this->where(['RealName' => $name])->find();

        if (empty($field)) {
            return $user;
        } else {
            return $user[$field];
        }
    }

    /**
     * 获取组部门下面的用户ID
     * @param $departId
     * @return array
     */
    public function getUserOfDepartId($departId)
    {

        $userIds = [];
        $map = [];
        $map['DepartId'] = $departId;
       /* $this->departments = D('Department')->getAllDepartment();
        $this->department = D('Department')->getDepartment($this->user['departid']);
        $tree = D('Department')->getTree($this->departments, $this->department[$this->user['departid']]);
        print_r($tree);
        exit;
        $pids = array_keys($tree);
        if (!empty($pids)) {
            $pids[] = $this->user['departid'];
            $this->where['DepartId'] = ['in', $pids];
        } else {
            $this->where['DepartId'] = $this->user['departid'];
        }*/
        $users = M('user')->field('UserId')->where($map)->select();
        foreach ($users as $k => $v) {
            $userIds[] = $v['userid'];
        }

        return $userIds;
    }

    /**
     * 获取kid下面的用户
     * @param $kid
     * @return array
     */
    public function getUserOfKid($kid, $role)
    {
        $map['Kid'] = $kid;
        if ($role == 'seller') {
            $map['RoleId'] = ['in', '1,3,4,5'];
        } elseif ($role == 'promoter') {
            $map['RoleId'] = ['in', '2,9,10'];
        }

        $users = M('user')->field('UserId')->where($map)->select();
        foreach ($users as $k => $v) {
            $userIds[] = $v['userid'];
        }

        return $userIds;
    }

    public function checkKidAndRoleId($kid, $uid, $roleId)
    {
        //没有选择角色和部门的禁止查看数据
        if (!$kid) {
            if ($uid != 815) {
                return 'departId';
            }
        }

        if (!$roleId) {
            if ($roleId != 95) {
                return 'roleId';
            }
        }
    }

    /**
     * @param $kid
     * @return mixed
     * 根据kid筛选离职用户
     */
    public function getDimissionByKid($kid)
    {
        !empty($kid) && $map['Kid'] = $kid;
        $map['isLock'] = ['in', [1, 2]];
        $res = M('user')->field('UserId')->where($map)->select();
        foreach ($res as $k => $v) {
            $dimissions[] = $v['userid'];
        }
        return $dimissions;
    }

    /**获取kid下面的销售或邀约手用于k7获取销售
     * @param $kid
     * @return array
     */
    public function getUserOfK($kid, $role)
    {
        $map['Kid'] = $kid;
        if ($role == 'seller') {
            $map['isLock'] = 0;
            $map['RoleId'] = 1;
        } elseif ($role == 'promoter') {
            $map['RoleId'] = 2;
        }

        $users = M('user')->field('UserId')->where($map)->select();
        foreach ($users as $k => $v) {
            $userIds[] = $v['userid'];
        }

        return $userIds;
    }

    protected function setStores()
    {
        $stores = I("store");
        return implode(',', $stores);
    }

    protected function setKid()
    {
        $DepartId = I('DepartId');
        $department = D('Department')->getDepartment($DepartId);
        $dpath = explode('-', $department['dpath']);
        return isset($dpath[2]) ? $dpath[2] : 0;
    }
}