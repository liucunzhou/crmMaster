<?php
namespace Org\Net;

class Message{

	// 云片发送验证码
	static public function sendSms($mobile){
		$code			= self::createIdentifyingCode();
		$url			= "http://yunpian.com/v1/sms/send.json";
		$apikey			= "7f93b9773e107390d92a57b6cac5eeb0";
		$encoded_text	= urlencode("【由棠网络】您的验证码是{$code}");
		$post_string	= "apikey=$apikey&text=$encoded_text&mobile=$mobile";
		$result			= self::sockPost($url, $post_string);
		
		if($result){
			return $code;
		} else {
			return false;	
		}
	}
	
	static private function sockPost($url, $query){
		$data	= "";
		$info	= parse_url($url);
		$fp		=fsockopen($info["host"],80, $errno, $errstr,30);
		if(!$fp){
			return $data;
		}
		
		$head="POST ".$info['path']." HTTP/1.0\r\n";
		$head.="Host: ".$info['host']."\r\n";
		$head.="Referer: http://".$info['host'].$info['path']."\r\n";
		$head.="Content-type: application/x-www-form-urlencoded\r\n";
		$head.="Content-Length: ".strlen(trim($query))."\r\n";
		$head.="\r\n";
		$head.=trim($query);
		$write=fputs($fp,$head);
		$header = "";
		while ($str = trim(fgets($fp,4096))) {
			$header.=$str;
		}
		
		while (!feof($fp)) {
			$data .= fgets($fp,4096);
		}

		return $data;
	}
	
	
	// 火尼获取验证码
	static public function getIdentifyingCodeFromHuoni($mobile){
		$code					= self::createIdentifyingCode();
		$params['account']		= 'lczwx0624';
		$params['password']		= 'lcz19860109';
		$params['content']		= "【由棠网络】验证玛:{$code}(2分钟内有效，如您已经成功注册，请忽略此消息)";
		$params['sendtime']		= '';
		$params['phonelist']	= $mobile;
		$params['taskId']		= 'lczwx0624_'.date('YmdHis').'_http_'.rand(100000, 999999);
		$query	= http_build_query($params);
		$url	= 'http://sms.huoni.cn:8080/smshttp/infoSend?'.$query;
		$res	= file_get_contents($url);
		$res	= explode(",", $res);
		
		if (count($res) == 3){
			return $code;
		} else {
			return $res;
		}
	}
	
	// 获取创蓝验证码
	static public function getFromCl($mobile){
            $code   = self::createIdentifyingCode();
            $data   = "亲爱的用户，您的手机验证码是{$code}";
            $post_data 			= array();
            $post_data['account']	= iconv('GB2312', 'GB2312', "vipyswl");
            $post_data['pswd']		= iconv('GB2312', 'GB2312', "MJmeihu123");
            $post_data['mobile']	= $mobile;
            $post_data['msg']		= mb_convert_encoding("$data",'UTF-8', 'auto');
            $url	= 'http://222.73.117.158/msg/HttpBatchSendSM?';
            $o		= "";
            foreach ($post_data as $k=>$v){
                    $o.= "$k=".urlencode($v)."&";
            }
            $post_data=substr($o, 0, -1);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $result = curl_exec($ch);
            curl_close($ch);

            $result	= explode(",", $result);
            return $result[1] == 0 ? $code : false;
	}
	
	static private function createIdentifyingCode(){
		$code = '';
		for ($i=0;$i<6;$i++){
			$code.= rand(0, 9);
		}
		return $code;
	}
}