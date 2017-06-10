<?php
namespace Service\Controller;

use Think\Controller;

class IndexController extends Controller
{

    public function index()
    {
        header('Content-Type:text/html; charset=utf-8');
        echo "<pre>";
        // 推送客资区间
        $time1  = strtotime('today 22:00:00');
        $time2  = strtotime('tomorrow 08:30:00');
        $time   = time();
        if ($time < $time2 && $time > $time1) return;

        // 获取推送客资
        $map['Status'] = 0;
        $map['Times'] = 0;
        $Receive = M('TigerJinshuju');
        $list = $Receive->where($map)->order('Id desc')->limit(3)->select();

        if(empty($list)) return;

        $url = "http://slave.koreavisit.cn/api.php?m=Service&c=Receive&a=distribute";
        $result = curl_post($url, ['data'=>json_encode($list)]);

        $fileStr = '';
        $result = json_decode($result, true);
        foreach ($result as $key=>$val) {
            if ($val == '1') {
                $update = ['Status' => 1, 'Times' => 1, 'ReceivedTime'=>date('Y-m-d H:i:s')];
            } else {
                $update = ['Status' => 0, 'Times' => 1];
            }
            $Receive->where(['Id'=>$key])->save($update);

            $sql = $Receive->_sql();
            $time = date('Y-m-d H:i:s');
            $fileStr .= "{$sql}\t({$time})\n";
        }

        $path = './data/tiger/jinshuju.log';
        file_put_contents($path, $fileStr, FILE_APPEND);
    }

    /**
     * 第二次推送
     */
    public function push2()
    {
        header('Content-Type:text/html; charset=utf-8');
        // 推送客资区间
        $time1  = strtotime('today 22:00:00');
        $time2  = strtotime('tomorrow 08:30:00');
        $time   = time();
        if ($time < $time2 && $time > $time1) return;

        // 获取推送客资
        $map['Status'] = 0;
        $map['Times'] = 1;
        $Receive = M('Receive');
        $list = $Receive->where($map)->order('Id desc')->limit(3)->select();

        if(empty($list)) return;

        $url = "http://slave.koreavisit.cn/api.php?m=Service&c=Receive&a=distribute";
        $result = curl_post($url, ['data'=>json_encode($list)]);
        $result = json_decode($result, true);
        foreach ($result as $key=>$val) {
            if ($val == '1') {
                $update = ['Status' => 0, 'Times' => 2];
            } else {
                $update = ['Status' => 1, 'Times' => 1, 'ReceivedTime'=>date('Y-m-d H:i:s')];
            }
            $Receive->where(['Id'=>$key])->save($update);
        }
    }

    /**
     * 第三次推送
     */
    public function push3()
    {
        header('Content-Type:text/html; charset=utf-8');
        // 推送客资区间
        $time1  = strtotime('today 22:00:00');
        $time2  = strtotime('tomorrow 08:30:00');
        $time   = time();
        if ($time < $time2 && $time > $time1) return;

        // 获取推送客资
        $map['Status'] = 0;
        $map['Times'] = 2;
        $Receive = M('Receive');
        $list = $Receive->where($map)->order('Id desc')->limit(3)->select();

        if(empty($list)) return;

        $url = "http://slave.koreavisit.cn/api.php?m=Service&c=Receive&a=distribute";
        $result = curl_post($url, ['data'=>json_encode($list)]);
        $result = json_decode($result, true);

        foreach ($result as $key=>$val) {
            if ($val == '1') {
                $update = ['Status' => 1, 'Times' => 1];
            } else {
                $update = ['Status' => 0, 'Times' => 3];
            }
            $Receive->where(['Id'=>$key])->save($update);
        }
    }

    /**
     * 第二次推送
     */
    public function push4()
    {
        header('Content-Type:text/html; charset=utf-8');
        // 推送客资区间
        $time1  = strtotime('today 22:00:00');
        $time2  = strtotime('tomorrow 08:30:00');
        $time   = time();
        if ($time < $time2 && $time > $time1) return;

        // 获取推送客资
        $map['Status'] = 0;
        $map['Times'] = 3;
        $Receive = M('Receive');
        $list = $Receive->where($map)->order('Id desc')->limit(3)->select();

        if(empty($list)) return;

        $url = "http://slave.koreavisit.cn/api.php?m=Service&c=Receive&a=distribute";
        $result = curl_post($url, ['data'=>json_encode($list)]);
        $result = json_decode($result, true);

        foreach ($result as $key=>$val) {
            if ($val == '1') {
                $update = ['Status' => 1, 'Times' => 1];
            } else {
                $update = ['Status' => 0, 'Times' => 4];
            }
            $Receive->where(['Id'=>$key])->save($update);
        }
    }


}