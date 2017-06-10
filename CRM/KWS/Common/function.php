<?php
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}


function curl_post($url, $fields)
{
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url);
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    // curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
    // curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
    $rs = curl_exec($ch);
    curl_close ($ch);

    return $rs;
}

/** excel导入函数
 * @param $file
 * @return array
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 */
function importExecl($file, $type = 'xls')
{
    if (!file_exists($file)) {
        return array("error" => 0, 'message' => 'file not found!');
    }
    Vendor("PHPExcel.PHPExcel.IOFactory");
    $excel_type = in_array($type, ['xls', 'xlt']) ? 'Excel5' : 'Excel2007';
    $objReader = PHPExcel_IOFactory::createReader($excel_type);
    try {
        $PHPReader = $objReader->load($file);
    } catch (Exception $e) {
    }
    if (!isset($PHPReader)) return array("error" => 0, 'message' => 'read error!');
    $allWorksheets = $PHPReader->getAllSheets();
    $i = 0;
    foreach ($allWorksheets as $objWorksheet) {
        $sheetname = $objWorksheet->getTitle();
        $allRow = $objWorksheet->getHighestRow();//how many rows
        $highestColumn = $objWorksheet->getHighestColumn();//how many columns
        $allColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $array[$i]["Title"] = $sheetname;
        $array[$i]["Cols"] = $allColumn;
        $array[$i]["Rows"] = $allRow;
        $arr = array();
        $isMergeCell = array();
        foreach ($objWorksheet->getMergeCells() as $cells) {//merge cells
            foreach (PHPExcel_Cell::extractAllCellReferencesInRange($cells) as $cellReference) {
                $isMergeCell[$cellReference] = true;
            }
        }
        for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
            $row = array();
            for ($currentColumn = 0; $currentColumn < $allColumn; $currentColumn++) {
                $cell = $objWorksheet->getCellByColumnAndRow($currentColumn, $currentRow);
                $afCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn + 1);
                $bfCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn - 1);
                $col = PHPExcel_Cell::stringFromColumnIndex($currentColumn);
                $address = $col . $currentRow;
                $value = $objWorksheet->getCell($address)->getValue();
                if (substr($value, 0, 1) == '=') {
                    return array("error" => 0, 'message' => 'can not use the formula!');
                    exit;
                }
                if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                    $cellstyleformat = $cell->getParent()->getCacheData($cell->getCoordinate())->getFormattedValue();
                    if (preg_match('/^([0-9A-F]*-[0-9A-F]*-[0-9A-F]*)/i', $cellstyleformat)) {
                        $value = gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
                    }
                    /*else{
                        $value=PHPExcel_Style_NumberFormat::toFormattedString($value,$cellstyleformat);
                        }*/
                }

                $temp = '';
                if ($isMergeCell[$col . $currentRow] && $isMergeCell[$afCol . $currentRow] && !empty($value)) {
                    $temp = $value;
                } elseif ($isMergeCell[$col . $currentRow] && $isMergeCell[$col . ($currentRow - 1)] && empty($value)) {
                    $value = $arr[$currentRow - 1][$currentColumn];
                } elseif ($isMergeCell[$col . $currentRow] && $isMergeCell[$bfCol . $currentRow] && empty($value)) {
                    $value = $temp;
                }
                $row[$currentColumn] = $value;
            }
            $arr[$currentRow] = $row;
        }
        $array[$i]["Content"] = $arr;
        $i++;
    }
    //	spl_autoload_register(array('Think','autoload'));//must, resolve ThinkPHP and PHPExcel conflicts
    unset($objWorksheet);
    unset($PHPReader);
    unset($PHPExcel);
    unlink($file);
    return array("error" => 1, "data" => $array);
}


/** excel导出函数
 * @param $expName
 * @param $expTitle
 * @param $expCellName
 * @param $expTableData
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 */
function exportExcel($expName, $expTitle, $expCellName, $expTableData)
{
    $fileName = $expName . date('_YmdHis');//or $xlsName 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new PHPExcel();
    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

    $objPHPExcel->getActiveSheet()->setTitle($expTitle);
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
    for ($i = 0; $i < $cellNum; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 2), $expTableData[$i][$expCellName[$j][0]]);
        }
    }

    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $expName . '.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}


/**
 * 系统邮件发送函数
 * @param string $to 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * @return boolean
 */
function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null)
{
    $config = C('THINK_EMAIL');
    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
    $mail = new PHPMailer(); //PHPMailer对象
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug = 0;                     // 关闭SMTP调试功能
    // 1 = errors and messages
    // 2 = messages only
    $mail->SMTPAuth = true;                  // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';                 // 使用安全协议
    $mail->Host = $config['SMTP_HOST'];  // SMTP 服务器
    $mail->Port = $config['SMTP_PORT'];  // SMTP服务器的端口号
    $mail->Username = $config['SMTP_USER'];  // SMTP服务器用户名
    $mail->Password = $config['SMTP_PASS'];  // SMTP服务器密码
    $mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);
    $replyEmail = $config['REPLY_EMAIL'] ? $config['REPLY_EMAIL'] : $config['FROM_EMAIL'];
    $replyName = $config['REPLY_NAME'] ? $config['REPLY_NAME'] : $config['FROM_NAME'];
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->AltBody = "为了查看该邮件，请切换到支持 HTML 的邮件客户端";
    $mail->MsgHTML($body);
    $mail->AddAddress($to, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }

    return $mail->Send() ? true : $mail->ErrorInfo;
}

/**
 * 获取当天开始时间函数
 * @param $day string
 * @return time int
 */
function firstTime($day = '')
{
    if (empty($day)) {
        $day_str = date('Y-m-d 00:00:00', time());
    } else {
        $day_str = date('Y-m-d 00:00:00', strtotime($day));
    }

    return strtotime($day_str);
}

/**
 * 获取当天最后时间函数
 * @param $day string
 * @return time int
 */
function lastTime($day = '')
{
    if (empty($day)) {
        $day_str = date('Y-m-d 23:59:59', time());
    } else {
        $day_str = date('Y-m-d 23:59:59', strtotime($day));
    }

    return strtotime($day_str);
}


/**
 * @param $url
 * @param $filepath
 * @param int $level
 * @param int $size
 * @return bool
 */
function qrcode($url, $filepath, $level = 3, $size = 4)
{

    if (!$url) return false;

    Vendor('phpqrcode.phpqrcode');
    //容错级别
    $errorCorrectionLevel = intval($level);
    $matrixPointSize = intval($size);//生成图片大小
    //生成二维码图片
    $object = new QRcode();

    $resulut = $object->png($url, $filepath, $errorCorrectionLevel, $matrixPointSize, 2, true);
}

/**
 * 生成百度编辑器
 * @param $key
 * @param string $content
 * @param bool|false $more
 * @return string
 */
function ueditor($key, $content = '', $more = false)
{
    $scriptStr = '';
    if (!$more) {
        $scriptStr = '<script type="text/javascript" charset="utf-8" src="/assets/plug-in/ueditor/ueditor.config.js"></script>
                      <script type="text/javascript" charset="utf-8" src="/assets/plug-in/ueditor/ueditor.all.min.js"> </script>
                      <script type="text/javascript" charset="utf-8" src="/assets/plug-in/ueditor/lang/zh-cn/zh-cn.js"></script>';
    }

    $divStr = '<div><script id="' . $key . '" name="' . $key . '" type="text/plain" style="width:100%;">' . htmlspecialchars_decode($content) . '</script></div><script type="text/javascript">var ' . $key . ' = UE.getEditor(\'' . $key . '\');</script>';

    return $scriptStr . $divStr;
}

/**
 * 验证码
 */
function verify()
{
    $config = array('fontSize' => 30,    // 验证码字体大小
        'length' => 3,     // 验证码位数
        'useNoise' => false, // 关闭验证码杂点
    );
    $verify = new \Think\Verify($config);
    $verify->entry();
    return $verify;
}

/**
 * 表是否存在
 * @param $table_name
 * @return bool
 */
function table_exist($table_name)
{
    $sql = "SHOW TABLES LIKE '$table_name'";
    $tables = M()->query($sql);
    return empty($tables) ? false : true;
}

/**
 * 添加排序字段
 * @param array $array
 * @return string
 */
function US($array = [])
{
    $get = I("get.");
    foreach($array as $key=>$val) {
        $get[$key] = $val;
    }

    if($get['asc'] == 'desc'){
        $get['asc'] = 'asc';
    } else {
        $get['asc'] = 'desc';
    }

    $action = CONTROLLER_NAME.'/'.ACTION_NAME;
    return U($action, $get);
}

/**
 * 获取接单状态
 * @param $status
 * @return string
 */
function get_accept_status($status)
{
    switch ($status){
        case -2:
            $str = "<font color='red'>待分配</font>";
            break;
        case -1:
            $str = "<font color='red'>无人接受</font>";
            break;
        case 0:
            $str = "<font color='#ea6153'>未接受</font>";
            break;
        case 1:
            $str = "<font color='green'>已接受</font>";
            break;
        case 2:
            $str = "暂未接受";
            break;
        default :
            $str = '';
    }

    return $str;
}

function get_assign_type($id, $type)
{
    $str = "";
    if ($id == 0) {
        $str = "自动分配";
    } else if ($id == 1) {
        $str = "组长指定";
    } else if ($id == 2) {
        $str = "指定销售";
    } else if ($id == 3) {
        $str = "晚值";
    } else if ($id == 4) {
        $str = "奖励";
    } else if ($id == -1) {
        $str = "之前录入";
    }

    return $str;
}


function getLists($pid = 0, $lists = array(), $deep = 1, $depart){
    global $lists;
    $res = $depart-> where(['ParentId'=> $pid])->select();

    if(!empty($res)){
        foreach($res as $k=>$v){
            if(!empty($v)){
                $row = str_repeat('&nbsp;&nbsp;', $deep).$deep.'|--'.$v['departname'];
                $lists[] = $row;
                getLists($v['departid'], $lists, $deep + 1, $depart);
            }else{
                $lists[] = str_repeat('&nbsp;', $deep).'|--'.$res['departname'];
            }
        }
    }else{
        return false;
    }

    return $lists;
}

function get_deep($dpath)
{
    $dpath = explode('-', $dpath);
    return count($dpath);
}

function getfiles($path, $allowFiles, &$files = array())
{
    if (!is_dir($path)) return null;
    if(substr($path, strlen($path) - 1) != '/') $path .= '/';
    $handle = opendir($path);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $path2 = $path . $file;
            if (is_dir($path2)) {
                getfiles($path2, $allowFiles, $files);
            } else {
                if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                    $files[] = array(
                        'url'=> substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                        'mtime'=> filemtime($path2)
                    );
                }
            }
        }
    }

    return $files;
}

/* php版本低于5.5 */
function array_column($input, $columnKey, $indexKey = NULL)
{
    $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
    $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
    $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
    $result = array();

    foreach ((array)$input AS $key => $row)
    {
        if ($columnKeyIsNumber)
        {
            $tmp = array_slice($row, $columnKey, 1);
            $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
        }
        else
        {
            $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
        }
        if ( ! $indexKeyIsNull)
        {
            if ($indexKeyIsNumber)
            {
                $key = array_slice($row, $indexKey, 1);
                $key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
                $key = is_null($key) ? 0 : $key;
            }
            else
            {
                $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
            }
        }

        $result[$key] = $tmp;
    }

    return $result;
}

function percent($row, $sum)
{
    if($sum == '0') {
        return "0%";
    } else {

        return round($row / $sum * 100, 2) . "%";
    }
}

function check_rule($black, $rule)
{
    return in_array($rule, $black) ? 'remove' : '';
}

/**
 * @param $uid
 * @param $uname
 * @param $log
 * @param $typeId
 * 用户操作日志
 */
function operateLog($uid,$uname,$log,$typeId)
{
    D("OperateLog")->addLog([
        'UserId' => $uid,
        'OperateLog' => $uname.$log,
        'TypeId' => $typeId,
        'InertTime' => date("Y-m-d H:i:s"),
        'IP' => $_SERVER["REMOTE_ADDR"]
    ]);
}

function onlineLog($userid,$userName,$type)
{
    $data =[
        'UserId'=>$userid,
        'UserName'=> $userName,
        'LogType' => $type,//1登录 2退出 3离线 4上线
        'InsertTime' => date("Y-m-d H:i:s")
    ];

    return M('onlineLog')->add($data);
}

/**
 * 生成订单号
 * @return string
 */
function create_order_no()
{
    return date("YmdHis").rand(1000,9999);
}
