<?php

namespace KWS\Controller;

/**
 * Description of TaskController
 *
 * @author liucunzhou
 */
class TaskController extends BaseController
{
    /**
     * 比对用户
     */
    public function index()
    {
        // ID相同UserAccount
        $sql = 'SELECT a.UserId as NewId, a.Kid, a.UserAccount as nua, b.UserId as OldId,'
                . 'b.UserAccount as nub,a.RoleId as arole,b.RoleId as brole '
                . 'from tk_user as a LEFT JOIN latest.UserInfo as b on a.UserId != b.UserId '
                . 'WHERE a.UserAccount = b.UserAccount';
        // echo $sql;
        // exit;
        $users = M()->query($sql);
        $this->assign('list', $users);
        
        $update = '';
        foreach($users as $key=>$val)
        {
            $update .= "update tk_assign_copy set NowUser={$val['newid']} where NowUser={$val['oldid']};\n";
        }
        $fileName = './source/assign_copy.update_nowuser.sql';
        file_put_contents($fileName, $update);
        
        layout('Layout/win');
        $this->display();
    }
    
    public function AssignInfo()
    {
        $sql = 'select a.Assignid from tk_assign a left join tk_assign_copy b on a.CustNo=b.CustNo where a.NowUser!=b.NowUser;';
        $assign = M()->query($sql);
        
        $update = '';
        foreach($assign as $key=>$val) {
            $update .= 'update tk';
        }
    }
    
    /**
     * 更新销售\销售部门\大区ID
     */
    public function updateSeller()
    {
        $map['RoleId'] = ['in', [1,3,4,5]];
        $users = M('User')->where($map)->select();
        
        $update = '';
        foreach($users as $key=>$val)
        {
            if($val['kid'] != 0) {
                $update .= "update tk_customer set Kid={$val['kid']},DepartId={$val['departid']} where DepartId=0 and salseId={$val['userid']};\n";
            }
        }
        
        echo $update;
        
        $filename = './source/customer.department.sql';
        file_put_contents($filename, $update);
    }
    
    /**
     * 更新贵广部门的ID
     */
    public function updatePromoter()
    {
        $map['RoleId'] = ['in', [2,9,10,11]];
        $users = M('User')->where($map)->select();
        
        $update = '';
        foreach($users as $key=>$val)
        {
            if($val['kid'] != 0) {
                $update .= "update tk_customer set PdepartId={$val['departid']} where PDepartId=0 and Opeartor={$val['userid']};\n";
            }
        }
        
        echo $update;
        
        $filename = './source/customer.pdepartment.sql';
        file_put_contents($filename, $update);
    }

    /**
     * 还原就K7洗单数据
     */
    public function backToK7()
    {
        C('DB_NAME', 'compare');

        $fields = 'CustNo,salseId';
        $result = M('customer')->field($fields)->where(['Kid' => 67, 'IsWashing' => 1])->select();
        $stream = '';
        foreach($result as $key=>$val) {
            $sql = "update tk_customer set Kid=67,salseId={$val['salseid']} where CustNo='{$val['custno']}' and IsWashing=1;\n";
            echo $sql.'<br>';
            $stream .= $sql;
        }

        $filename = './source/bakctok7.sql';
        file_put_contents($filename, $stream);
    }
    
    public function test()
    {
        file_put_contents('1.txt',time() ."\n", FILE_APPEND);
        echo 8;
    }
}
