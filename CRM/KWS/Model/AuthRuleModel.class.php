<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2016/10/8
 * Time: 17:57
 */

namespace KWS\Model;


use Think\Auth;
use Think\Model;

class AuthRuleModel extends Model
{
    public function getRuleBlack()
    {

        $arr = [];
        $Auth = new Auth();
        $rules = $this->select();

        $user = session('user');

        foreach($rules as $key=>$val){

            if($user['roleid'] == 12) continue;

            $checked = $Auth->check($val['name'], $user['userid']);
            if(!$checked) {
                $arr[] = str_replace(' ', '', $val['name']);
            }
        }

        return $arr;
    }
}