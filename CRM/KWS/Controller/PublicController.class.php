<?php
namespace KWS\Controller;

use Think\Controller;

class PublicController extends Controller
{

    public function _initialize()
    {
        header("content-type:text/html;charset=uft8");
    }

    /**
     * 系统登录
     */
    public function login()
    {
        if (IS_POST) {
            $map['uname'] = I('uname');
            $password = I('password');
            if (empty($map['uname'])) {
                $this->ajaxReturn(['code' => '400', 'msg' => '账号不能为空', 'id' => 'uname'], 'json');
            }
            if (empty($password)) {
                $this->ajaxReturn(['code' => '400', 'msg' => '密码不能为空', 'id' => 'password'], 'json');
            }

            $verify = I('verify');
            if (empty($verify)) {
                $this->ajaxReturn(['code' => '400', 'msg' => '验证码不能为空', 'id' => 'verify'], 'json');
            }
            if (!check_verify($verify)) {
                $this->ajaxReturn(['code' => '400', 'msg' => '验证码错误', 'id' => 'verify'], 'json');
            }
            $data['password'] = md5($password);
            $check = M('User')->where($map)->find();
            if ($check) {
                $data ['last_login_time'] = time();
                $result = M('User')->where(['id' => $check['id']])->save($data);
                if ($result) {
                    session("member", $check);
                    $this->ajaxReturn(['code' => '200', 'msg' => '登录成功!', 'redirect' => U('Index/index')], 'json');
                } else {
                    $this->ajaxReturn(['code' => '400', 'msg' => '登录账号或密码有误!', 'id' => 'referer'], 'json');
                }
            } else {
                $this->ajaxReturn(['code' => '400', 'msg' => '登录账号或密码有误!', 'id' => 'referer'], 'json');
            }
        } else {
            layout(false);
            $this->display();
        }
    }

    /**
     * 执行登录
     */
    public function doLogin()
    {
        // 获取登陆者的IP
        $ip = get_client_ip();
        $maxLoginCount = 5;
        S($ip, 0, ['expire' => '7200']);
        $loginCount = (int)S($ip);

        if ($loginCount >= $maxLoginCount) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '已超过最大登录次数,请2小时后再试！'
            ]);
        } else {
            S($ip, ++$loginCount, ['expire' => '7200']);
        }

        // 检测登录名
        $data['UserAccount'] = I('UserAccount');
        if (empty($data['UserAccount'])) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '登录名不能为空'
            ]);
        }

        // 检测密码
        $data['UserPwd'] = I('UserPwd');
        if (empty($data['UserPwd'])) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '登录密码不能为空'
            ]);
        } else if (strlen($data['UserPwd']) < 6) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '密码长度不能少于6位'
            ]);
        }
        
        $data['UserPwd'] = md5($data['UserPwd']);
        $user = M('user')->where($data)->find();
        if (empty($user)) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '用户或密码不正确'
            ]);
        }

        if ($user['islock'] == 1) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '该账户已锁定'
            ]);
        } elseif ($user['islock'] == 2) {
            $this->ajaxReturn([
                'code' => '400',
                'msg' => '该账户已离职'
            ]);
        }

        // 设置session
        unset($user['UserPwd']);
        session('user', $user);
        if(in_array($user['roleid'],[1,3,4,5])){
            M('user')->where(['UserId'=>$user['userid']])->save(['IsOnline'=>1]);
            S('UserOnline-'.$user['userid'],1,43200);
        }

        // k队列
       /* $kid = $user['kid'];
        if (in_array($user['roleid'] ,[1,3,4,5]) && $kid) {
            $queue = S('kqueue-' . $kid);
            empty($queue) && $queue = [];
            if (!in_array($user['userid'], $queue)) {
                array_push($queue, $user['userid']);
                $queue = array_values($queue);
                S('kqueue-' . $kid, $queue);
            }
        }*/

        $userId = cookie('tk_user_id');
        if($userId != $user['userid']) {
            file_put_contents("diffent.txt", $userId."___________".$user['userid']."\n", FILE_APPEND);
        }

        // 添加登录日志
        D("OperateLog")->addLog([
            'UserId' => $user['userid'],
            'OperateLog' => $user['useraccount'] . '登录系统',
            'TypeId' => 0,
            'InertTime' => date("Y-m-d H:i:s"),
            'IP' => $ip
        ]);
        onlineLog($user['userid'], $user['realname'], 'login');

        $this->ajaxReturn([
            'code' => '200',
            'msg' => '登录成功',
            'redirect' => U('User/info')
        ]);
    }

    /**
     * 退出系统
     */
    public function logout()
    {
        //operateLog($this->user['userid'], $this->user['realname'], '退出crm系统', 0);
        $user = session('user');
        $onlineName = 'UserOnline-'.$this->user['userid'];
        S($onlineName,0);
        session('user', null);
        $this->redirect('Public/login');
    }

}