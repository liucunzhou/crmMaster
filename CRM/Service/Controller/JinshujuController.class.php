<?php
namespace Service\Controller;

use Think\Controller;

class JinshujuController extends Controller
{

    private $apiKey = 'fxG6aepCTlYjKU6MoU_Aew';
    private $apiSecret = 'Wh9kbUcZnCJhep8qpEtKoA';

    public function index()
    {
        header('Content-Type:text/html; charset=utf-8');
        $request = file_get_contents('php://input');
        $post = json_decode($request, true);
        // 获取表单字段
        $formName = $post['form'];
        $inputData = $post['entry'];
        $inputField = $this->parseFormFields($formName);
        // 格式化提交的数据
        $data = $this->parseFormData($inputField, $inputData);
        $data['Status'] = 0;
        $data['Times'] = 0;
        $data['InsertTime'] = date('Y-m-d H:i:s');

        // 检测无效数据
        $valid = $this->checkDataValid($data);
        if(!$valid) exit('200');

        $request = M("TigerJinshuju")->add($data);
        if($request) {
            echo 200;
        } else {
            echo 500;
        }
        return;
    }

    private function checkDataValid($data)
    {
        // 检测门店信息
        if(empty($data['StoreId']) || empty($data['SourceId']) || empty($data['PromoterId'])) {
            return false;
        }

        // 检测联系方式
        if(empty($data['Mobile']) && empty($data['WeChat']) && empty($data['QQ']) && empty($data['WeiboName'])) {
            return false;
        }

        return true;
    }

    public function getFormFields($formName='MoZhcx')
    {
        $cacheName = 'From-Fields-'.$formName;
        $result = S($cacheName);
        if(empty($result)) {
            // header('Content-Type:text/json; charset=utf-8');
            $url = "https://jinshuju.net/api/v1/forms/{$formName}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':' . $this->apiSecret);
            $result = curl_exec($ch);
            S($cacheName, $result, ['expire'=>86400]);
            curl_close($ch);
        }

        return json_decode($result, true);
    }


    public function parseFormFields($formName='MoZhcx')
    {
        // header('Content-Type:text/json; charset=utf-8');
        $result = $this->getFormFields($formName);
        $fields = $result['fields'];

        $data = [];
        foreach ($fields as $field) {
            $keys = array_keys($field);
            $key = $keys[0];
            $label = $field[$key]['label'];
            $dbField = '';
            // 检测门店ID字段
            if(strpos($label, '门店') !== false) {
                $dbField = 'StoreId';
            }

            // 检测来源ID字段
            if(strpos($label, '来源') !== false) {
                $dbField = 'SourceId';
            }

            // 检测推广人员ID字段
            if(strpos($label, '推广') !== false) {
                $dbField = 'PromoterId';
            }

            // 检测手机号字段
            if(strpos($label, '手机') !== false) {
                $dbField = 'Mobile';
            }

            // 检测微信账号字段
            if(strpos($label, '微信') !== false) {
                $dbField = 'WeChat';
            }

            // 检测QQ账号字段
            if(stripos($label, 'qq') !== false) {
                $dbField = 'QQ';
            }

            // 检测微博ID字段
            if(strpos($label, '微博') !== false) {
                $dbField = 'WeiboName';
            }

            // 备注
            if(strpos($label, '备注') !== false) {
                $dbField = 'Remark';
            }

            if(!empty($dbField)) {
                $data[$key] = [
                    'field' => $dbField,
                    'label' => $label
                ];
            }
        }

        return $data;
    }

    public function parseFormData($inputField, $inputData)
    {
        $data = [];
        foreach($inputData as $key=>$val){
            if(isset($inputField[$key])) {
                $field = $inputField[$key]['field'];
                $data[$field] = $val;
            }
        }

        return array_filter($data);
    }
}