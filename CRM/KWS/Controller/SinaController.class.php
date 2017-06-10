<?php
/**
 * Created by PhpStorm.
 * User: LFC
 * Date: 2017/3/9
 * Time: 18:32
 */

namespace KWS\Controller;

use KWS\Model\SinaRsaModel;
use Think\Controller;

class SinaController extends Controller
{
    /**
     * 初始化
     */
    public function _initialize()
    {

    }

    /**
     * 新浪婚礼接口
     */
    public function weddingSeason()
    {
        $rsaKeyConfig = array(
            //公钥
            'public_key' => '',

            //私钥 请业务方将分配的私钥填入该value
            'private_key' => '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDaktnzoDeRkM9DbYBKjFcCVSxtnf1eqcAucc5HCF1bW3fycsOJ
xTbO67PV59YAH68KvYrppMZxCR1FIPXfraZwSAdHwxOtjVfNBN4glavO89ajADJG
cz2fayNTze84pflX8VlaaKEL5iLSr8n3bkvASbrrXOjrAcfC6WuhSt4VHQIDAQAB
AoGBAK4+RXu/LK1hqKkTISPMzwwwBDP6r+KIJomf9haQZ4QN3ggsTw+EZVX9dqa+
o84DdBJ22ce6sOupnWjfhsLi9vuwbQG5nyvtQjmADDSzUDff131kKjaWbwWeoaFc
U0uDMfLckaohe3qFyRtUOZPF1haZRStsCDXjj2zo4RaKVU2tAkEA8GrmfnkBwjbp
tk9UoFqinbQZdvMQs5YgH5419EHsy/vCyXva3PI5RZP7wY7CxqNZ10A29da3nr8g
nk6jHKMuUwJBAOi9gg9/o9k11iiq6MDCm2d9KBJ1WKSpgskV/qH3Ome4dAtAaTDv
3r+bKtnhz23LZM8PlSZFQQ3WB4pY/wGx4M8CQQDl8qkxEFYhR1p0WB4uHWJqCidN
ASDZU963vyAF1sRBOjTNd5wXNcqXhPoH4J7lVLoKSk4HBu6rdE0jfT0/l8gnAkBr
I3nw2og5oH/inrKgsbVKUaIVxTE2M9ZB7T8XnjCjuJSq7U6/OVzoBW0Xecy8NUpF
y12UjYLh9Knp9QgG5rSfAkAdgy6YzxDoSle5jvckN7jYB+tnBgbAGKuZUwkP4Zqu
hmvF5g6wI6G9M9OR8pgz23kTiyVYmxJn6beokvR3Wuf2
-----END RSA PRIVATE KEY-----',
        );

        $data = [];

        // $_POST['encrypt_data'] = 'EMVdhadCTAwGRstat/blgH2T9iRwYvf9gxAGzlRA0ePBcGlEFUvb/JOps3qlaIc581Flqh7uAilaro3DIbst3TWINmYMg68lNGVdIBvPnMapwUHK8OzPMvLXTp0OIHOHYr7BcMTkdPplkVGeg+9UlHnl5OZ+fZDgzuJXDiP6cMk=lhWIIiNMzg84ZM33FwYB46j5cdnBig3g465KnPuJxwuu/AIjRUFHktg/wA98B1Fe+ytyvCX0V5aE+y96JH4MzwNd6gSn1b1GbFrnHo9FChCIBoY7BPU6iXT/DztkPXRczxBkTPpVSfSu7kVEn2a+ScehHJjKgez7P9opGE4ooxE=s+t7ochU0FuMhfSnu8Yzf9ZgA+fWdVDIzzwcG2aUfu17WBjARU8hg6XbJz/TmEUdw28vl5AgNYl49TYh5QvLF5pfKDKJiIYjRCC0WG9Q0pGtM/OplrRuUir+pBevkEr6ZFIYHnW68Kvr9WrBTw+A5YGDrPJn24eEc9xeXaKI3BA=';
        if(!empty($_POST['encrypt_data'])) {
            $encryptData = $_POST['encrypt_data'];
            $RsaServer = new SinaRsaModel($rsaKeyConfig);
            $decryptData = $RsaServer->decryptData($encryptData);

            $assign = M('WeddingSeason')->where(['Assign' => $decryptData['leads_id']])->find();
            if(!empty($assign)) {
                $this->ajaxReturn([
                    'code'  => '100000',
                    'msg'   => 'Data has existed',
                    'data'  => ''
                ]);
            }

            foreach ($decryptData['data'] as $key => $val) {
                // 获取客户姓名
                if($val['name'] == 'name') {
                    $data['CustName'] = $val['value'];
                }

                // 获取客户手机号
                if($val['name'] == 'tel') {
                    $data['Mobile'] = $val['value'];
                }

                $data['Assign'] = $decryptData['leads_id'];
                // 获取客户的姓名
                $data['Sex'] = 0;
                // 根据手机号码,获取客户的所在城市
                $apiUrl = "http://apis.juhe.cn/mobile/get?phone={$data['Mobile']}&key=f2f24c985a5d3d02e8231726c7693ee2";
                $apiStr = file_get_contents($apiUrl);
                $apiData = json_decode($apiStr, true);
                $data['Province'] = $apiData['result']['province'];
                $data['City'] = empty($apiData['result']['city']) ? $apiData['result']['province'] : $apiData['result']['city'];
                $data['Location'] = empty($apiData['result']['city']) ? $apiData['result']['province'] : $apiData['result']['city'];
            }

        } else {
            $data = I("post.");
        }

        // if(empty($data['Mobile']) || empty($data['Location'])) {
        if(empty($data['Mobile'])) {
            $this->ajaxReturn([
                'code'  => '400',
                'msg'   => 'Data is Empty!'
            ]);
        }

        $data['InsertTime'] = time();
        $data['Status'] = 0;

        $result = M("WeddingSeason")->add($data);

        if($result) {
            $this->ajaxReturn([
                'code'  => '100000',
                'msg'   => 'success',
                'data'  => ''
            ]);
        } else {
            $this->ajaxReturn([
                'code'  => '100001',
                'msg'   => 'Data insert error',
                'data'  => ''
            ]);
        }
    }
}