<?php
namespace Service\Model;

use Think\Model;

class UserModel extends Model
{
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
}