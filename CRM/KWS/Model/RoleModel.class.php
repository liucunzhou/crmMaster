<?php
namespace KWS\Model;

use Think\Model;

/**
 * 角色模型
 */
class RoleModel extends Model
{
    /**
     * 获取所有角色
     */
    public function getAllRoles($id){
        $roles = M('role')->select();
        foreach($roles as $k=>$v){
            $role[$v['roleid']] = $v['rolename'];
        }

        if($id){
            return $role[$id];
        }else{
            return $role;
        }

    }
}