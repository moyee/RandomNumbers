<?php
/**
 * Created by PhpStorm.
 * User: PHPPer
 * Date: 2018/6/24
 * Time: 21:55
 */

class random
{

    public $redis;

    public function __construct()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $this->redis = $redis;
    }

    /**
     * 生成用户名（附带靓号规则）
     * 尝试100次不成功则跳出
     *
     * @return int|string
     */
    public function get_username_number()
    {
        $i = 0;
        $username_uuid = '-1';
        while (true) {
            if ($i > 100) {
                break;
            }
            $username_uuid = mt_rand(80000000, 99999999);
            $filed = substr($username_uuid, 0, 5);
            $key = "regUname:$filed";
            //分区存放以便快速获取
            $result = $this->redis->sIsMember($key, $username_uuid);
            if (!$result) {
                $push = $this->addUsername($key, $username_uuid);
                if ($push) {
                    break;
                }
            }
            //验证是否为靓号
            $nice_number = $this->check_lianghao($username_uuid);
            if ($nice_number) {
                continue;
            }
            $i++;
        }
        return $username_uuid;
    }

    /**
     * 把用户名缓存至redis
     * @param $key
     * @param $username
     * @return mixed
     */
    public function addUsername($key, $username)
    {
        if (empty($key)) {
            $filed = substr($username, 0, 5);
            $key = "regUname:$filed";
        }
        return $this->redis->sAdd($key, $username);
    }

    /**
     * 释放用户名
     * @param $key
     * @param $username
     * @return mixed
     */
    public function delUsername($key, $username)
    {
        if (empty($key)) {
            $filed = substr($username, 0, 5);
            $key = "regUname:$filed";
        }
        return $this->redis->sRem($key, $username);
    }

    /**
     * 靓号规则
     * @param $number
     * @return bool
     */

    function check_lianghao($number)
    {
        //7. 保留8号段：留给渲染客户或服务商、需求商、联盟用户等重点客户 （假如销售直接给客户一个靓号，并赠送渲染金额，还是非常给力的）
        //6. 保留6号段：留给运营推广和优惠卡用，（学生用户、特定群体用户等）卡号就是用户名，激活码就是密码，
        // $preg_liuba = '/^[6|8]/';
        //if (preg_match($preg_liuba, $number)) {
        //    return true;
        // }

        //1. 保留前一千个号码： 10000 - 19999 作为我们内部员工使用--初始化从20000开始
        //9.  文婷建议 5位 9号段先屏蔽掉，
        $num_len = strlen($number);
        if ($num_len == 5) {
            $preg_gonghao = '/^[9|5]/';
            if (preg_match($preg_gonghao, $number)) {
                return true;
            }
        }

//    //4. 保留生日（6-8位的时候，）：
//    if ($num_len == 6 || $num_len == 8) {
//        $preg_shengri = '/^((((1[6-9]|[2-9]\d)\d{2})(0?[13578]|1[02])(0?[1-9]|[12]\d|3[01]))|(((1[6-9]|[2-9]\d)\d{2})(0?[13456789]|1[012])(0?[1-9]|[12]\d|30))|(((1[6-9]|[2-9]\d)\d{2})-0?2-(0?[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))-0?2-29))$/';
//        if (preg_match($preg_shengri, $number)) {
//            return true;
//        }
//    }
        //for($i = 0 ; $i<69 ; $i++){
        if ($num_len == 6 || $num_len == 8) {
            if ($number >= 194511 && $number <= 206499) {
                return true;
            }

            if ($number >= 19450101 && $number <= 20641231) {
                return true;
            }
        }
        // }

        //10位 400 + 800 号码屏蔽掉，避免公司上规模后不必要的麻烦。
        if ($num_len == 10) {
            $preg_rexian = '/^(400)|^(800)/';
            if (preg_match($preg_rexian, $number)) {
                return true;
            }
        }

        //2. 保留连号：如 AAAAAA
        //6. 部分连号：如 XXAAAA （主要判断后四位的情况）
        $preg_lianhao = '/(\d)\1{3,}$/';
        if (preg_match($preg_lianhao, $number)) {
            return true;
        }

        // 8. 复号，例如：ABABAB  ABCABC
        $preg_fuhao1 = '/(\d\d)\1{2,}/';
        if (preg_match($preg_fuhao1, $number)) {
            return true;
        }

        $preg_fuhao2 = '/(\d\d\d)\1{1,}/';
        if (preg_match($preg_fuhao2, $number)) {
            return true;
        }

        //6. 部分连号：如 XXAAAA （主要判断后四位的情况）
        // $preg_bflianhao = '/([\d])\1{1,}([\d])\2{3,}/';
        // if (preg_match($preg_bflianhao, $number)) {
        //     return true;
        //  }

        //3. 保留串号：如 ABCDFG(正序)
        //5. 部分串号：如 XXABCD （主要判断后四位的情况）
        $preg_chuanhao = '/(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){4,}/';
        if (preg_match($preg_chuanhao, $number)) {
            return true;
        }
        //3. 保留串号：如 ABCDFG(倒序)
//    $preg_daochuan = '/(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){3}/';
//    if (preg_match($preg_daochuan, $number)) {
//        return true;
//    }

        return false;
    }

}