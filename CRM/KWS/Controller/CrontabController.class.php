<?php
namespace KWS\Controller;

use Think\Controller;

Class CrontabController extends Controller
{
    /**
     * 未验证的客咨
     * 前七天
     */
    public function unchecked()
    {
        $Customer = M('customer');
        $fileds = $Customer->getDbFields();
        $date = date("Y-m-d",strtotime('-6 days'));
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';
        $map['InsertTime'] = ['between',[$start, $end]];
        $map['Status'] = 25;
        $map['Kid'] = ['neq',67];
        $map['IsWashing'] = ['neq',2];
        $storeIds = $this->washingStores();
       // $map['StoreId'] = ['not in',[13,33,168,228,202,65,203]];
        $map['StoreId'] = ['in',$storeIds];
        $data = $Customer->where($map)->select();
        //echo $Customer->_sql();
       //print_r($data);
       // exit;
        $washModel = M('wash');
        $cdata['IsWashing'] = 2;
        foreach($data as $k=>$v){
            $where['CustId'] = $v['custid'];

            $Customer->where($where)->save($cdata);

            foreach($fileds as $val){
                $d[$val] = $v[strtolower($val)];
            }
            $d['Kid'] = 67;
            $washModel->add($d);
        }

    }

    protected function washingStores()
    {
        $map['Business'] = ['in',['photo','baby']];
        $stores = M('store')->field('StoreId')->where($map)->select();
        $arr = array_column($stores,'storeid');
        return $arr;
    }
    /**
     * 无效的客咨
     * 前一天
     */
    public function uneffective()
    {
        $Customer = M('customer');
        $fileds = $Customer->getDbFields();

        $date = date("Y-m-d",strtotime('-1 days'));
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';
        $map['InsertTime'] = ['between',[$start, $end]];
        $map['Status'] = 12;
        $map['Kid'] = ['neq',67];
        //$map['StoreId'] = ['not in',[13,33,168,228,202,65,203]];
        $storeIds = $this->washingStores();
        $map['StoreId'] = ['in',$storeIds];
        //$map['IsWashing'] = 'NULL';
        $data = $Customer->where($map)->select();
        //echo $Customer->_sql();
        //print_r($data);exit;
        $washModel = M('wash');
        $cdata['IsWashing'] = 2;
        foreach($data as $k=>$v){
            $where['CustId'] = $v['custid'];
            $Customer->where($where)->save($cdata);
            foreach($fileds as $val){
                $d[$val] = $v[strtolower($val)];
            }
            $d['Kid'] = 67;
            $washModel->add($d);
        }
    }

    /**
     * 初始化
     */
     public function initWash()
     {
         $Customer = M('customer');
         $fileds = $Customer->getDbFields();
         //print_r($fileds);
         //print_r(M('customer'));
            exit;
         $map['Status'] = ['in',[12,25]];
         $map['StoreId'] = ['not in',[202,65,203]];
         $data = $Customer->where($map)->select();

         $washModel = M('wash');
         $cdata['IsWashing'] = 2;
         foreach($data as $k=>$v){
             $where['CustId'] = $v['custid'];

             $Customer->where($where)->save($cdata);
             foreach($fileds as $val){
                 $d[$val] = $v[strtolower($val)];
             }
             $d['IsWashing'] = 1;
             $d['Kid'] = 67;
             $washModel->add($d);
         }
     }

    /**
     * @return array|mixed
     */
    public function getLockUsers()
    {
        $map['isLock'] = ['in',[1,2]];
        $users = M('user')->where($map)->getField('UserId,RealName,isLock');
        $users = array_keys($users);
        return $users;
    }

    /**
     * user表，store表
     */
    public function emptyLockUserStoreId()
    {
        $userIds = $this->getLockUsers();
        $UserModel = M('user');
        $storeModel = M('store');
        foreach($userIds as $val){
            $userId = $val;
            $map['UserId'] = $userId;
            $data['StoreId'] = '';
            $UserModel->where($map)->save($data);
            $Stores = $this->stores;
            foreach($Stores as $k=>$v){
                $sotreUsers = explode(',',$v['users']);
                if(in_array($userId,$sotreUsers)){
                    $key = array_search($userId, $sotreUsers);
                    unset($sotreUsers[$key]);
                    $sotreUsersStr = implode(',', $sotreUsers);
                    $storeModel->where(['StoreId'=>$v['storeid']])->save(['Users'=>$sotreUsersStr]);
                }
            }
            //print_r($Stores);
        }
    }
    public function updateSeller()
    {
        // ignore_user_abort(true);
        $Customer = M('Customer');
        $count = $Customer->where(['Kid'=>0, 'salseId'=>['eq', '0'], 'InsertTime'=>['between',['2016-12-03', '2016-12-07']]])->count();

        $Assign = M('Assign');
        $limit = 10000;
        $User = M('User');

        ob_clean();
        $pages = ceil($count / $limit);
        for( $i = 0; $i< $pages; $i++ ) {
            $customers = $Customer->field('CustNo,Kid,Opeartor')->where(['Kid' => 0, 'salseId'=>['eq', 0], 'InsertTime'=>['between',['2016-12-03', '2016-12-07']]])->order('InsertTime desc')->limit($i * $limit, $limit)->select();
            foreach($customers as $key=>$val) {
                if(empty($val['kid'])) {
                    $data = [];
                    // 匹配对应的销售
                    $assign = $Assign->where(['CustNo' => $val['custno']])->find();
                    // 设置销售的Id
                    if(!empty($assign['nowuser'])) {
                        $data['salseId'] = $assign['nowuser'];
                        $Customer->where(['CustNo' => $val['custno']])->save($data);
                        echo $Customer->_sql();
                        echo "<br>";
                    }
                }

                // echo $val['custno'];
                // echo "<br>";
            }
            ob_flush();
            flush();
            echo 'execute 1000...<br >';
        }
    }

    public function NowToSalse()
    {
        // ignore_user_abort(true);
        $Customer = M('Customer');
        $count = $Customer->where(['Kid'=>0,'salseId'=>['neq', '0'], 'InsertTime'=>['between',['2016-12-03', '2016-12-07']]])->count();

        $limit = 1000;
        $User = M('User');

        ob_clean();
        $pages = ceil($count / $limit);
        for( $i = 0; $i< $pages; $i++ ) {
            $customers = $Customer->field('CustNo,Kid,Opeartor,salseId')->where(['Kid' => 0, 'salseId'=>['neq', '0'], 'InsertTime'=>['between',['2016-12-03', '2016-12-07']]])->order('InsertTime desc')->limit($i * $limit, $limit)->select();
            foreach($customers as $key=>$val) {
                if(empty($val['kid'])) {
                    $data = [];
                    // 设置K及邀约手的部门ID
                    $user = $User->find($val['opeartor']);
                    $data['Kid'] = $user['kid'];
                    $data['PdepartId'] = $user['departid'];

                    // 匹配对应的销售
                    $seller = $User->find($val['salseid']);
                    // 获取销售的部门
                    $data['DepartId'] = $seller['departid'];

                    $Customer->where(['CustNo' => $val['custno']])->save($data);
                }

                // echo $val['custno'];
                // echo "<br>";
            }
            ob_flush();
            flush();
            echo 'execute 1000...<br >';
        }
    }

    /**
     * 根据邀约手匹配部门
     */
    public function checkDepart()
    {
        $users = M('User')->where(['Kid'=>['neq', 67]])->select();
        $str = '';

        foreach($users as $key=>$val) {
            if($val['roleid'] == 1 && $val['kid'] != 0) {
                echo $str = "update tk_customer set Kid={$val[kid]} where salseId={$val[userid]};\n";
                echo "<br />";
                echo $str = "update tk_customer set DepartId={$val['departid']} where salseId={$val[userid]};\n";
                echo "<br />";
            }
        }

        file_put_contents('customer.depart.sql', $str);
    }


    public function createSql()
    {
        $sql = 'SELECT a.UserId as NewId,b.UserId as OldId,a.UserAccount,a.RealName from tk_user as a LEFT JOIN latest.UserInfo as b on a.UserAccount = b.UserAccount WHERE a.UserId != b.UserId';
        $users = M()->query($sql);
        $str = '';
        foreach($users as $key=>$val){
            $str .= "update tk_customer set salseId={$val[newid]} where CustNo in (select CustNo from AssignInfo where NowUser={$val[oldid]});\n";
            $str .= "update tk_assign set NowUser={$val[newid]} where CustNo in (select CustNo from AssignInfo where NowUser={$val[oldid]});\n";
        }

        echo $str;
        file_put_contents("pre.sql", $str);
    }

    /**
     * 重复邀约手
     */
    public function checkPromoter()
    {
        // $sql = 'SELECT a.UserId as NewId,b.UserId as OldId,a.UserAccount,a.RealName,b.RoleId from tk_user as a LEFT JOIN latest.UserInfo as b on a.UserAccount = b.UserAccount WHERE a.UserId != b.UserId and a.RoleId=2 and a.Kid!=4';
        $sql = 'SELECT a.UserId as NewId,b.UserId as OldId,a.UserAccount as nua,b.UserAccount as nub,a.RoleId as arole,b.RoleId as brole from tk_user as a LEFT JOIN latest.UserInfo as b on a.UserId = b.UserId WHERE a.UserAccount != b.UserAccount';
        $users = M()->query($sql);
        $str = '';
        $table = '<table>';
        foreach($users as $key=>$val){
            // echo $str = $val['roleid']."update tk_customer set Opeartor={$val[newid]} where Opeartor={$val['oldid']};\n";
            $table .= "<tr>";
            $table .= "<td>({$val['arole']})".$val['nua']."</td><td>({$val['brole']})".$val['nub']."</td>";
            $table .= "</tr>";
        }
        $table .= "</table>";
        echo $table;
        echo count($users);
        // echo $str;
        $filename = './source/promoter.sql';
        file_put_contents($filename, $str);
    }

    /**
     * 推送金数据数据
     */
    public function pushJinshujuData()
    {
        $url = "http://crm.e5ws.com/index.php?m=KWS&c=Api&a=doAddCustomer";
        $logs = M("JinshujuLog")->where(['Status'=>0])->limit(1)->select();
        foreach($logs as $key=>$val) {
            $data = [];
            $data['StoreId'] = $val['storeid'];
            $data['Mobile'] = $val['mobile'];
            $data['SourceFrom'] = $val['sourcefrom'];
            $data['Opeartor'] = $val['opeartor'];
            $result = curl_post($url, $data);
            var_dump($result);
        }
    }
}