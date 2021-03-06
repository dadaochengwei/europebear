<?php
/**
 * Created by PhpStorm.
 * User: Tianjun
 * Date: 2020/3/1
 * Time: 19:12
 */

namespace dadaochengwei\europebear;


class Core
{
    /**
     * AES加密字符串
     * @param string $data
     * @return string $ret
     */
    public static function aesEncrypt($data)
    {
        $method = 'AES-128-CBC';
        $key = '2020xaddcwkj0301';
        $iv = '8695432183357458';
        $ret = base64_encode(openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv));
        return $ret;
    }

    /**
     * AES解密字符串
     * @param string $data
     * @return string $ret
     */
    public static function aesDecrypt($data)
    {
        $method = 'AES-128-CBC';
        $key = '2020xaddcwkj0301';
        $iv = '8695432183357458';
        $ret = openssl_decrypt(base64_decode($data), $method, $key, OPENSSL_RAW_DATA, $iv);
        return $ret;
    }

    /**
     * sha256加密
     * @param $data
     * @param bool $rawOutput
     * @return bool|string
     */
    public static function sha256($data, $rawOutput = false)
    {
        if (!is_scalar($data)) {
            return false;
        }
        $data = (string)$data;
        $rawOutput = !!$rawOutput;
        return hash('sha256', $data, $rawOutput);
    }

    /**
     * 隐藏手机号码中间4位用*代替
     * @param $mobile
     * @return mixed
     */
    public static function hiddenMobile($mobile)
    {
        return substr_replace($mobile, '****', 3, 4);
    }

    /**
     * 输出json
     * @param $arr
     */
    public static function outPutJson($arr)
    {
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        die;
    }

    /**
     * md5连接后缀加密
     * @param $str
     * @return string
     */
    public static function md5($str)
    {
        $suffix = 'DDCW-fw2020';
        return \md5($str . $suffix);
    }

    /**
     * 发送email
     * @param $accept
     * @param $title
     * @param $content
     */
    public static function sendEmail($accept, $title, $content)
    {
        $mail = Email::getInstance();
        $mail->send($accept, $title, $content);
    }

    /**
     * POST接值处理
     */
    public static function post($key, $default = '', $type = '')
    {
        $raw = self::rawJson();
        if (empty($raw)) {
            if ($type == 'int') {
                return isset($_POST[$key]) ? \intval($_POST[$key]) : $default;
            } elseif ($type == 'array') {
                if (!empty($_POST[$key])) {
                    return \implode(',', \array_filter($_POST[$key]));
                } else {
                    return null;
                }
            } else {
                return isset($_POST[$key]) ? \trim($_POST[$key]) : $default;
            }
        } else {
            if ($type == 'int') {
                return isset($raw[$key]) ? \intval($raw[$key]) : $default;
            } elseif ($type == 'array') {
                if (!empty($raw[$key])) {
                    return $raw[$key];
                    //return \implode(',', \array_filter($raw[$key]));
                } else {
                    return null;
                }
            } else {
                $postParams = isset($raw[$key]) ? \trim($raw[$key]) : $default;
                if ($postParams) {
                    return $postParams;
                } else {
                    return isset($raw[$key]) ? \trim($raw[$key]) : $default;
                }
            }
        }
    }

    /**
     * GET接值处理
     */
    public static function get($key, $default = '', $type = '')
    {
        if ($type == 'int') {
            return isset($_GET[$key]) ? \intval($_GET[$key]) : $default;
        } elseif ($type == 'array') {
            if (!empty($_GET[$key])) {
                return \implode(',', \array_filter($_GET[$key]));
            } else {
                return null;
            }
        } else {
            return isset($_GET[$key]) ? \trim($_GET[$key]) : $default;
        }
    }

    /**
     * raw appliction/json 接值
     * @return mixed
     */
    public static function rawJson()
    {
        $rawData = file_get_contents('php://input');
        return json_decode($rawData, true);
    }

    /**
     * 获取所有 以 HTTP开头的header参数
     * @return array
     */
    public static function getHeaders()
    {
        $headers = array();
        $REQUEST_TIME_FLOAT = $_SERVER['REQUEST_TIME_FLOAT'];
        foreach ($_SERVER as $key => $value) {

            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                $key = str_replace('_', ' ', $key);
                $key = str_replace(' ', '-', $key);
                $key = strtoupper($key);
                $headers[$key] = $value;
            }
        }
        $headers['TIME'] = $REQUEST_TIME_FLOAT;
        return $headers;
    }

    /**
     * create order number
     * @param string $pre
     * @return string
     */
    public static function createOrderNo($pre = '')
    {
        $yCode = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $orderNo = $pre . $yCode[intval(date('Y')) - 2020] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $orderNo;
    }
	
	/**
     * pagination
     * @param int $count
	 * @param int $pageSize
	 * @param int $page
     * @return array
     */
	public static function Pagination($count = 0, $page = 1, $pageSize = 10)
    {
        $maxPages = ceil($count / $pageSize);
        if ($page < 1) {
            $page = 0;
        }
        if ($page > $maxPages) {
            $page = $maxPages;
        }
        if ($count == 0) {
            $page = 1;
        }
        $offset = ($page - 1) * $pageSize;
        $limit = [$offset, $pageSize];
        return ['page' => $page, 'limit' => $limit];
    }

    /**
     * 转换容量
     * @param $bytes
     * @return string
     */
    public static function toSize($bytes)
    {
        if ($bytes >= pow(2, 40)) {
            $return = round($bytes / pow(1024, 4), 2);
            $suffix = "TB";
        } elseif ($bytes >= pow(2, 30)) {
            $return = round($bytes / pow(1024, 3), 2);
            $suffix = "GB";
        } elseif ($bytes >= pow(2, 20)) {
            $return = round($bytes / pow(1024, 2), 2);
            $suffix = "MB";
        } elseif ($bytes >= pow(2, 10)) {
            $return = round($bytes / pow(1024, 1), 2);
            $suffix = "KB";
        } else {
            $return = $bytes;
            $suffix = "Byte";
        }
        return $return . " " . $suffix;
    }

}