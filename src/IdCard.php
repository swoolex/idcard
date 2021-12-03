<?php
/**
 * +----------------------------------------------------------------------
 * 身份证号码归属地解析
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

// namespace x\IdCard;


class IdCard {
    /**
     * 当前版本号
    */
    private $version = '1.0.1';
    /**
     * 失败原因
    */
    private $error = '';
    /**
     * 结果集
    */
    private $data = [];

    /**
     * 调用入口
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021-12-03
     * @deprecated 暂不启用
     * @global 无
     * @param string $id_card 身份证号码
     * @return false.array
    */
    public function handle($id_card) {
        if (empty($id_card)) {
            $this->error = '身份证号码为空';
            return false;
        }
        if ($this->cardeVif($id_card) === false) {
            $this->error = '不是正确的身份证号码';
            return false;
        }
        $array = require __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'region_map.php';
        $code = substr($id_card, 0, 6);
        if (empty($array[$code])) {
            $this->error = '身份证号码前6位识别失败，建议通知SW-X开发组成员，更新归属地址库';
            return false;
        }
        $str = $array[$code];
        if (stripos($str, '省')===false && stripos($str, '自治区')===false) {
            $arr = explode('市', $str);
            $this->data['province'] = $arr[0].'市';
            $this->data['city'] = $arr[0].'市';
            $this->data['area'] = $arr[1];
        } else {
            if (stripos($str, '自治区') !== false) {
                $arr = explode('自治区', $str);
                $this->data['province'] = $arr[0].'自治区';
                $str = $arr[1];
            } else {
                $this->data['province'] = substr($str, 0, stripos($str, '省')).'省';
                $str = str_replace($this->data['province'], '', $str);
            }

            $this->data['city'] = substr($str, 0, stripos($str, '市')).'市';
            $this->data['area'] = str_replace($this->data['city'], '', $str);
        }

        $sex_int = (int)substr($id_card, 16, 1);
        if ($sex_int % 2 === 0) {
            $this->data['sex'] = '女';
        } else {
            $this->data['sex'] = '男';
        }

        $date = substr($id_card, 6, 8);
        $this->data['time'] = strtotime($date);
        $this->data['date'] = date('Y年m月d日', $this->data['time']);
        $date = date_parse_from_format('Y年m月d日', $this->data['date']);
        $this->data['year'] = $date['year'];
        $this->data['month'] = $date['month'];
        $this->data['day']   = $date['day'];

        $date2 = date_parse_from_format('Y年m月d日', date('Y-m-d', time()));
        $age = $date2['year']-$date['year'];
        if ($date2['month'] >= $date['month']) {
            if ($date2['day'] >= $date['day']) {
                $age++;
            }
        }
        $this->data['age'] = $age;

        return $this->data;
    }

    /**
     * 获取失败原因描述
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021-12-03
     * @deprecated 暂不启用
     * @global 无
     * @return string
    */
    public function error() {
        return $this->error;
    }

    /**
     * 成员属性的方式读取结果集
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021-12-03
     * @deprecated 暂不启用
     * @global 无
     * @param string $name
     * @return mixed
    */
    public function __get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return false;
    }

    /**
     * 身份证合法性验证
     * @todo 无
     * @author 小黄牛
     * @version v1.0.1 + 2021.12.03
     * @deprecated 暂不弃用
     * @global 无
     * @param string $id_card 身份证号
     * @return bool
    */
    private function cardeVif($id_card){ 
        $id = strtoupper($id_card); 
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/"; 
        $arr_split = array(); 
        if (!preg_match($regx, $id)) return false;
        # 检查15位 
        if (15 == strlen($id)) { 
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/"; 
            @preg_match($regx, $id, $arr_split); 

            # 检查生日日期是否正确 
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4]; 
            if(!strtotime($dtm_birth)) return false;
        # 检查18位 
        } else { 
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/"; 
            @preg_match($regx, $id, $arr_split); 
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4]; 

            # 检查生日日期是否正确 
            if (!strtotime($dtm_birth)) return false;

            # 检验18位身份证的校验码是否正确。 
            # 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。 
        
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); 
            $arr_ch  = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); 
            $sign    = 0; 
            for ( $i = 0; $i < 17; $i++ ) { 
                $b = (int) $id{$i}; 
                $w = $arr_int[$i]; 
                $sign += $b * $w; 
            } 
            $n       = $sign % 11; 
            $val_num = $arr_ch[$n]; 
            if ($val_num != substr($id,17, 1)) return false;
        }

        return true;
    } 
}
