<?php
namespace KWS\Model;


use Think\Model;

class AuthGroupModel extends Model
{
    public function getAllRole()
    {

        $groups = $this->order('status')->getField('id,title,rules');

        return $groups;
    }
}