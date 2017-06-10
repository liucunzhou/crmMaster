<?php
namespace KWS\Model;


class SinaRsaModel
{
    private $_privateKey = null;
    private $_publicKey = null;

    /**
     * SinaRsaModel constructor.
     * @param string $rsaKeyConfig
     */
    public function __construct($rsaKeyConfig = '')
    {
        if (is_null($rsaKeyConfig) || ('' === $rsaKeyConfig))
            throw new Exception('empty rsa key config ' . (string)$rsaKeyConfig);

        $keyConfig = $rsaKeyConfig;
        if (!empty($keyConfig['public_key']))
            $this->_publicKey = $keyConfig['public_key'];
        if (!empty($keyConfig['private_key']))
            $this->_privateKey = $keyConfig['private_key'];
    }

    /**
     * 处理加密数据为string
     * @param $data
     * @return string
     * @throws Exception
     */
    private function _prepareHandledData($data)
    {
        if (empty($data))
            throw new Exception(__CLASS__ . '|' . __METHOD__ . '| empty rsa data ' . json_encode(array($data)));

        return is_string($data) ? $data : json_encode($data);
    }

    /**
     * 渲染解密后数据
     * @param null $data
     * @return array|mixed
     */
    private function _renderHandledData($data = null)
    {
        return empty($data) ? array() : json_decode($data, true);
    }

    /**
     * 使用公钥，加密数据
     * @param string $encryptData
     * @return string
     */
    public function encryptRsaData($encryptData = '')
    {
        $encryptData = $this->_prepareHandledData($encryptData);

        $cryptRes = '';
        for($i = 0; $i < ((strlen($encryptData) - strlen($encryptData) % 117) / 117 +1 ); $i++)
        {
            $cryptRes .= $this->_publicEncrypt(mb_strcut($encryptData, $i * 117, 117, 'utf-8'));
        }

        return $cryptRes;
    }

    /**
     * 使用私钥，解密数据
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function decryptData($data)
    {
        if (empty($this->_privateKey))
            throw new Exception(__CLASS__ . '|' . __METHOD__ . '| Unable to decrypt data without private key!');

        $decryptRes = '';
        $dataArray = explode('=', $data);
        foreach ($dataArray as $value)
        {
            $decryptRes .= $this->_privateDecrypt($value);
        }

        return $this->_renderHandledData($decryptRes);
    }

    /**
     * 数据块rsa公钥加密
     * @param $data
     * @return string
     * @throws Exception
     */
    private function _publicEncrypt($data) {
        if (openssl_public_encrypt($data, $encrypted, $this->_publicKey))
            $data = base64_encode($encrypted);
        else
            throw new Exception(__CLASS__ .'|' . __METHOD__ . '| Unable to encrypt data. The cut is bigger than the limit size 117.');

        return $data;
    }

    /**
     * 数据块私钥解密
     * @param $data
     * @return string
     */
    private function _privateDecrypt($data) {
        if (openssl_private_decrypt(base64_decode($data), $decrypted, $this->_privateKey))
            $data = $decrypted;
        else
            $data = '';

        return $data;
    }
}