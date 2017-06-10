<?php
/**
 * Created by PhpStorm.
 * User: LFC
 * Date: 2017/4/16
 * Time: 11:26
 */

namespace KWS\Controller;


use Think\Controller;

class InterfaceController extends Controller
{
    public function doAddCustomer()
    {
        $request = file_get_contents('php://input');
        $post = json_decode($request, true);
        foreach($post['entry'] as $key=>$value) {
            // 检测手机
            if(isMobile($value)) {
                $data['Mobile'] = $value;
            }

            // 检测门店
            if(stripos($value, '__store__') === 0) {
                $storeArr = explode("__", $value);
                $data['StoreId'] = $storeArr[2];
            }

            // 检测来源
            if(stripos($value, '__platform__') === 0) {
                $storeArr = explode("__", $value);
                $data['SourceFrom'] = $storeArr[2];
            }

            // 检测邀约手
            if(stripos($value, '__promoter__') === 0) {
                $storeArr = explode("__", $value);
                $data['Opeartor'] = $storeArr[2];
            }
        }

        empty($data) && $data = I("post.");

        // 检测无效数据
        if(empty($data['Mobile']) || empty($data['StoreId']) || empty($data['SourceFrom']) || empty($data['Opeartor'])) {
            echo '200';
            exit;
        }

        // 获取城市
        if(empty($post['entry']['info_remote_ip'])) {
            $ip = get_client_ip();
        } else {
            $ip = $post['entry']['info_remote_ip'];
        }
        $url = "http://restapi.amap.com/v3/ip?ip={$ip}&output=json&key=cfb78a03eefa2b6cc9a401cd25599e8c";
        $amap = file_get_contents($url);
        $position = json_decode($amap, true);
        $data['city'] = $position['city'];

        // 初始化个人信息
        $user = M("user")->find($data['Opeartor']);
        $jlog = [
            'StoreId' => $data['StoreId'],
            'Mobile' => $data['Mobile'],
            'SourceFrom' => $data['SourceFrom'],
            'Opeartor' => $data['Opeartor'],
            'City' => $data['city'],
            'Status' => 0,
            'InsertTime' => date('Y-m-d H:i:s')
        ];
        $result = M("JinshujuLog")->add($jlog);
        if($request) {
            echo '200';
        } else {
            echo '500';
        }
    }
}