<?php
namespace MBCore\MCore\Libraries;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;  //临时config解决方案
use Illuminate\Support\Facades\Cache;


class Helper
{
    /**
     * 获取当前访问平台
     * @param Request $request
     * @return array|null|string
     */
    public static function getPlatform(Request $request)
    {

        $appid = $request->header('appid',false);
        $platform = null;
        if($appid){
            $ConfigOAuthServer = config('mbcore_oauth_server');
            $roles = isset($ConfigOAuthServer['roles'][$appid])?$ConfigOAuthServer['roles'][$appid]:false;
            if($roles){
                // // 渠道：Andorid/iOS/weapp/weixin
                $platform = strtolower($roles['channel']);
                if($platform=='weixin') $platform='wechat';
            }
        }

        /**
         * 验证平台
         */
        // 如果有platform参数
        if (!$platform)
        $platform = preg_match('/mbcore/i',$request->server('HTTP_USER_AGENT')) && preg_match('/iphone|ios/i',$request->server('HTTP_USER_AGENT')) ? 'ios' : null;

        // 如果UA中有mbcore字样
        if (!$platform)
            $platform = preg_match('/mbcore/i',$request->server('HTTP_USER_AGENT',null)) ? 'android' : null;

        // 判定微信环境
        if (!$platform){
            $platform = preg_match('/micromess/i',$request->server('HTTP_USER_AGENT',null)) ? 'wechat' : null;
            if($request->get('platform',null)=='miniProgram'){
                $platform = 'weapp';
            }
        }
        // 未来客户端
        if (!$platform)
            $platform = preg_match('/mbcclient/i',$request->server('HTTP_USER_AGENT',null)) ? 'client' : null;

        // 判定手机环境:android-wap
        if (!$platform)
            $platform = preg_match('/android/i',$request->server('HTTP_USER_AGENT',null)) ? 'android-wap' : null;
        // 判定手机环境:iphone-wap
        if (!$platform)
            $platform = preg_match('/iphone/i',$request->server('HTTP_USER_AGENT',null)) ? 'iphone-wap' : null;
        // 判定手机环境:ipad-wap
        if (!$platform)
            $platform = preg_match('/ipad/i',$request->server('HTTP_USER_AGENT',null)) ? 'ipad-wap' : null;

        // 否则为一般浏览器
        if (!$platform)
            $platform = 'pc';

        return $platform;
    }


    /**
     * 安全base64_encode将可能会被浏览器破坏的符号替换成其他符号
     * @param string $data
     * @return string
     */
    public static function safeEncode($data)
    {
        return strtr(base64_encode($data),[
            '=' => null,
            '/' => '_',
            '+' => '-'
        ]);
    }

    /**
     * 安全解码 对应 safeEncode
     * @param $data
     * @return string
     */
    public static function safeDecode($data)
    {
        return base64_decode(strtr($data,[
            '_' => '/',
            '-' => '+'
        ]));
    }

    /**
     * 简单加密
     * @param mixed $data 要加密的内容
     * @param string $key 密钥
     * @return string
     */
    public static function Encrypt($data, $key)
    {
        $iv = openssl_random_pseudo_bytes (16);
        $data = openssl_encrypt(serialize($data),'rc4',$key,1,null);
        return static::safeEncode($data);
    }

    /**
     * 简单解密
     * @param $data
     * @param $key
     * @return mixed|string
     */
    public static function Decrypt($data,$key)
    {
        $data = static::safeDecode($data);
        $iv = substr($data,0,16);
        $data = @unserialize(openssl_decrypt($data,'rc4',$key,1,null));
        return $data;
    }


    /**
     * 上传w文件
     * @param $resource
     * @return mixed
     * @throws \Exception
     *
     * upload
     */
    public static function fileSave($inputName,Request $request,$resource = null)
    {
//        $client = new Client();
        $client = new Client(['verify'=> false]);
        $uploadFileUrl = config("mbcore_mcore.storage_url").config('mbcore_mcore.storage_upload_api').'/';

        if(!$resource) {
            $picture = $request->file($inputName);
            // 如果图片不存在
            if (!$picture) {
                return "";
            }
            $resource = fopen($picture, 'r');
        }
        // 上傳到遠程
        $request = $client->request('post',$uploadFileUrl,[
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $resource,
                    'filename' => 'image.jpeg'
                ],
                [
                    'name' => 'type',
                    'contents' => 'image',
                ],
                [
                    'name' => 'timestamp',
                    'contents' => Carbon::now()->format('YmdHis')
                ],
                [
                    'name' => 'storage_bucket',
                    'contents' => config("mbcore_mcore.storage_bucket",'mbcore-test'),
                ]
            ]
        ]);
        $response = $request->getBody()->getContents();

        $response = json_decode($response,1);
        //dd($response);
        if (!$response || $response['code']!=1){
            //dd($response['result']['msg']);
            throw new \Exception($response['result']['msg'] ?: 'unknown');
        }
        //dd("test");
        return $response['result']['serverId'];
    }

    /**
     * @param $inputName
     * @param Request $request
     * @return array|string
     * @throws \Exception
     *
     * 多文件上传
     */
    public static function multiFileSave($inputName,Request $request)
    {
        $client = new Client(['verify'=> false]);
        $uploadFileUrl = config("mbcore_mcore.storage_url").config('mbcore_mcore.storage_upload_api').'/';

        $picture = $request->file($inputName);
        // 如果图片不存在
        if (!$picture) {
            return "";
        }
        $result = [];
        foreach ($picture as $key=>$val){
            $resource = fopen($val, 'r');
            // 上傳到遠程
            $request = $client->request('post',$uploadFileUrl,[
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $resource,
                        'filename' => 'image.jpeg'
                    ],
                    [
                        'name' => 'type',
                        'contents' => 'image',
                    ],
                    [
                        'name' => 'timestamp',
                        'contents' => Carbon::now()->format('YmdHis')
                    ],
                    [
                        'name' => 'storage_bucket',
                        'contents' => config("mbcore_mcore.storage_bucket",'mbcore-test'),
                    ]
                ]
            ]);
            $response = $request->getBody()->getContents();
            $response = json_decode($response,1);
            if (!$response || $response['code']!=1){
                throw new \Exception($response['result']['msg'] ?: 'unknown');
            }
            //dd("test");
            $result[] =  $response['result']['serverId'];
        }

        return $result;
    }

    /**
     *Load Config
     */
    public static function LoadConfig(){
        //处理配置文件路径
        //baseadmin_assets_path
        //((config("mbcore_mcore.app_install_way","root")=="group")?"/".config("mbcore_mcore.app_name",""):"").
        //Config::set('database.connections.mysql_test', config("mbcore_mcore.mysql_test",[])); //动态设定config

        //$app_name = "";
        if(config("mbcore_mcore.app_install_way","root")=="group"){
            $basePath = config('mbcore_baseadmin.baseadmin_assets_path','');
            Config::set('mbcore_baseadmin.baseadmin_assets_path', "/".config("mbcore_mcore.app_name","").$basePath);

            //$app_name = "/".trim(config('mbcore_mcore.app_name'),"/")."/";
            $baseUserPath = config('mbcore_baseuser.baseuser_assets_path','');
            Config::set('mbcore_baseuser.baseuser_assets_path', "/".config("mbcore_mcore.app_name","").$baseUserPath);

            $basePath = config('mbcore_ueditor.ueditor_assets_path','');
            Config::set('mbcore_ueditor.ueditor_assets_path', "/".config("mbcore_mcore.app_name","").$basePath);

            $basePath = config('mbcore_special_ueditor.ueditor_assets_path','');
            Config::set('mbcore_special_ueditor.ueditor_assets_path', "/".config("mbcore_mcore.app_name","").$basePath);
        }

        //主域名根目录
        //$rootUrl = \URL::current();
        //if (app('url')->isValidUrl($rootUrl)) {
        //    app('url')->forceRootUrl($rootUrl.$app_name);
        //}
        //\URL::forceRootUrl($baseurl.$app_name);

        $request_scheme = \Request::server("REQUEST_SCHEME");
        $request_scheme = $request_scheme?$request_scheme:"http"; //如果丢失时默认http
        $http_host = \Request::server('HTTP_HOST');
        $baseurl = $request_scheme ."://".  $http_host;

        //分组名获取
        $app_name = "";
        // 验证状态开启
        if (config('mbcore_mcore.app_install_way') === 'group' && config('mbcore_mcore.app_name')!="" ) {
            $app_name = "/".trim(config('mbcore_mcore.app_name'),"/")."/";
        }
        //\Log::info("*************************");
        //\Log::info($baseurl.$app_name);
        //dd($baseurl.$app_name);
        //\Log::info("*************************");
        \URL::forceRootUrl($baseurl.$app_name);

    }

    /**
     * @param $tag
     * @return array
     *
     * tag 转 数组
     */
    public static function tagToArray($tag){
        $temp = str_replace(array(';','；'),'|',$tag);
        return explode('|',$temp);
    }

    /**
     * @param $parameters
     * @param string $size
     * @return string
     *
     * Get Image Url
     */
    public static function getImageUrl($parameters,$size='0,1000',$mode = 1) {

        if(is_array($parameters)){
            $picturerHash = isset($parameters['pictureHash'])?$parameters['pictureHash']:"";
            $size = isset($parameters['size'])?$parameters['size']:'0,1000';
            $is_old = isset($parameters['is_old'])?$parameters['is_old']:0;
            $type = isset($parameters['type'])?$parameters['type']:"";
            $extraPath = isset($parameters['extraPath'])?'/'.$parameters['extraPath']:"";

            if($is_old && $type!=''){
                $baseUrl = config("mbcore_mcore.storage_local.baseurl","");
                //$filegroup = config("mbcore_mcore.storage_local.group","");
                $filegroup = isset($parameters['storage_local_group'])?$parameters['storage_local_group']:config("mbcore_mcore.storage_local.group","");
                if($filegroup) $filegroup = '/'.trim($filegroup,'/');
                $filepath = config("mbcore_mcore.storage_local.tag.".$type,"");
                if($baseUrl!="" && $filegroup!=""  && $filepath!="" ){
                    if (!empty($picturerHash)) {
                        $fileUrl = trim($baseUrl,'/').$filegroup.$filepath.$extraPath.'/'.trim($picturerHash,'/')."?".$size;
                        return $fileUrl;
                    } else {
                        return config('mbcore_mcore.storage_url').'/image/no_image.jpg';
                    }
                }
            }

        }else{
            $picturerHash = $parameters;
        }


        $pattern = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|([\s()<>]+|(\([\s()<>]+))*\))+(?:([\s()<>]+|(\([\s()<>]+))*\)|[^\s`!(){};:\'".,<>?«»“”‘’]))@';
//        $pattern = "/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i";
        if(preg_match($pattern, $picturerHash)){
            return $picturerHash;
        }
        return $picturerHash?config('mbcore_mcore.storage_url').config('mbcore_mcore.storage_get_url_api').'/'.$picturerHash.'?mode='.$mode.'&size='.$size.'&t=1&redirect=1':config('mbcore_mcore.storage_url').'/image/no_image.jpg';
    }


    /**
     * @param $userHash
     * @param null $size
     * @return string
     *
     * 获取用户头像
     */
    public static function getUserAvatar($userHash,$size=null)
    {
        $pattern = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|([\s()<>]+|(\([\s()<>]+))*\))+(?:([\s()<>]+|(\([\s()<>]+))*\)|[^\s`!(){};:\'".,<>?«»“”‘’]))@';
//        $pattern = "/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i";
        if(preg_match($pattern, $userHash)){
            return $userHash;
        }
        return $userHash?config('mbcore_mcore.storage_url').'/api/avatar/'.$userHash.'?size='.$size.'&t=1&redirect=1':config('mbcore_mcore.storage_url').'/image/no_image.jpg';

    }


    public static function  getReferer($url){
        if (empty(config('mbcore_mcore.domain')) ){
            return $url;
        } else {
            if(in_array($url,['#','href'])) return "#";
            return $url . (strpos($url, '?') ? '&' : '?') . 'domain=' . config('mbcore_mcore.domain');
        }
    }

    /**
     * @param $tel
     * @param null $onlyMob
     * @return array
     *
     * 电话号验证
     */
    public static function telephoneNumber($tel,$onlyMob=null)
    {
        $isMob = "/^1[3-5,4,7,8]{1}[0-9]{9}$/";
        $isTel="/^([0-9]{3,4}-)?[0-9]{7,8}$/";
        $special = '/^(4|8)00(-\d{3,4}){2}$/';//'/^(4|8)00(\d{4,8})$/';
        $data3 = substr($tel, 0,3);
        $data2 = substr($tel, 0,2);
        $msg = 'success';
        $code = 1;
        if(in_array($data2,['14'])){
            if($data3 != '147'){
                $msg = $data3.'号段不存在';
                $code = 0;

                return [
                    'code' => $code,
                    'msg'=>$msg
                ];
            }
        }

        if($onlyMob){//只验证手机号，不验证座机和400|800的号码
            if (preg_match($isMob, $tel)) {
                return [
                    'code' => $code,
                    'msg' => $msg
                ];
            } else {
                $msg = '手机号码格式不正确';
                $code = 0;

                return [
                    'code' => $code,
                    'msg' => $msg
                ];
            }
        }else {// 手机、座机、以及400|800号码的验证
            if (preg_match($isMob, $tel)) {
                return [
                    'code' => $code,
                    'msg' => $msg
                ];
            } elseif (preg_match($special, $tel)) {
                return [
                    'code' => $code,
                    'msg' => $msg
                ];
            } elseif (preg_match($isTel, $tel)) {
                return [
                    'code' => $code,
                    'msg' => $msg
                ];
            } else {
                $msg = '手机或电话号码格式不正确。如果是固定电话，必须形如(010-87876787 或者 400-000-0000)!';
                $code = 0;

                return [
                    'code' => $code,
                    'msg' => $msg
                ];
            }
        }
//        if(!preg_match($isMob,$tel) && !preg_match($special,$tel) && !preg_match($isTel,$tel))
//        {
//            $msg = '手机或电话号码格式不正确。如果是固定电话，必须形如(010-87876787)!';
//            $code = 0;
//
//        }



    }

    /**
     * @param $idcard
     * @return array
     *
     * 身份证验证
     */
    public static function validateIdCard($idcard)
    {
        $idcard = strtoupper($idcard);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx, $idcard))
        {
            return [
                'code'=>0,
                'msg'=>'身份证号格式不正确'
            ];
        }
        if(15==strlen($idcard)) //检查15位
        {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

            @preg_match($regx, $idcard, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth))
            {
                return [
                    'code'=>0,
                    'msg'=>'身份证号格式不正确'
                ];
            } else {
                return [
                    'code'=>1,
                ];
            }
        }
        else      //检查18位
        {
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $idcard, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)) //检查生日日期是否正确
            {
                return [
                    'code'=>0,
                    'msg'=>'身份证号格式不正确'
                ];
            }
            else
            {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ( $i = 0; $i < 17; $i++ )
                {
                    $b = (int) $idcard{$i};
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($idcard,17, 1))
                {
                    return [
                        'code'=>0,
                        'msg'=>'身份证号格式不正确'
                    ];
                } //phpfensi.com
                else
                {
                    return [
                        'code'=>1
                    ];
                }
            }
        }

    }

    /**
     * @param $bankCard
     * @return array
     *
     * 银行卡合法性验证
     */
    public static function validateBankCard($bankCard) {
        if (!preg_match('/^\d+$/', $bankCard))
            return [
                'code'=>0,
                'msg'=>'非法的银行卡号'
            ];
        $arr_no = str_split($bankCard);
        $last_n = $arr_no[count($arr_no)-1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n){
            if($i%2==0){
                $ix = $n*2;
                if($ix>=10){
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                }else{
                    $total += $ix;
                }
            }else{
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;

        if($last_n == ($total%10)){
            return [
                'code'=>1
            ];
        }else{
            return [
                'code'=>0,
                'msg'=>'非法的银行卡号'
            ];
        }
    }

    /**
     * @param $arrays
     * @param $sort_key
     * @param int $sort_order
     * @param int $sort_type
     * @return bool
     *
     * 多维数组自定义排序
     */
    public static function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);

        return $arrays;
    }

    /**
     * @return string
     * 唯一订单编号
     */
    public static function orderNumber(){

        return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

    /**
     * @return string
     *
     * PMCore Url
     */
    public static function pmCoreUrl(){
        $url = config('mbcore_mcore.pmcore_url','https://pmcore.mbcore.com/');

        return $url;
    }
    /**
     * @param $param
     * @return mixed|string
     * @throws \Exception
     *
     * Create System Order
     */
    public static function createSystemOrder($param){
        $url = static::pmCoreUrl().'mpay/api/order/create';
        $client = new Client();
        $form_params = [
            'out_trade_no'=>$param['out_trade_no'],
            'seller_id'=>$param['seller_id'],
            'buyer_id'=>$param['buyer_id'],
            'buyer_type'=>$param['buyer_type'],
            'group_id'=>$param['group_id'],# 可选-组订单编号
            'total_amount'=>$param['total_amount'],
            'subject'=>$param['subject'],
            'body'=>$param['body'],# 可选
            'platform'=>$param['platform'],
            'attach'=>$param['attach'],# 可选
            'return_url'=>$param['return_url'],# 可选
            'notify_url'=>$param['notify_url'],
        ];

        $result = $client->post($url,[
            'form_params'=>$form_params
        ]);

        try{
            $data = $result->getBody()->getContents();
            //return json_decode($data,1);
            if(stripos($data,"Error")!== false){

                throw new \Exception('接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
//            $data = [];
            throw new \Exception($e->getMessage());
        }


        return $data;
    }

    /**
     * @param $orderNumber
     * @return mixed|string
     * @throws \Exception
     *
     * get System Order Query
     */
    public static function getSystemOrderQuery($orderNumber){
        $url = static::pmCoreUrl().'mpay/api/order/query';
        $client = new Client();
        $result = $client->post($url,[
            'form_params'=>[
                'hash'=>$orderNumber,
            ]
        ]);
        try{
            $data = $result->getBody()->getContents();
            if(stripos($data,"Error")!== false){

                throw new \Exception('接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
            $data = [];
        }

        return $data;
    }

    /**
     * @param $s
     * @return bool|string
     *
     * 取得汉字拼音首字母
     */
    public static function pinyinToFirst($s) {
        $ascii = ord($s[0]);
        if($ascii > 0xE0) {
            $s = iconv('UTF-8', 'GB2312//IGNORE', $s[0].$s[1].$s[2]);
        }elseif($ascii < 0x80) {
            if($ascii >= 65 && $ascii <= 90) {
                return strtolower($s[0]);
            }elseif($ascii >= 97 && $ascii <= 122) {
                return $s[0];
            }else{
                return false;
            }
        }

        if(strlen($s) < 2) {
            return false;
        }

        $asc = ord($s[0]) * 256 + ord($s[1]) - 65536;

        if($asc>=-20319 && $asc<=-20284) return 'a';
        if($asc>=-20283 && $asc<=-19776) return 'b';
        if($asc>=-19775 && $asc<=-19219) return 'c';
        if($asc>=-19218 && $asc<=-18711) return 'd';
        if($asc>=-18710 && $asc<=-18527) return 'e';
        if($asc>=-18526 && $asc<=-18240) return 'f';
        if($asc>=-18239 && $asc<=-17923) return 'g';
        if($asc>=-17922 && $asc<=-17418) return 'h';
        if($asc>=-17417 && $asc<=-16475) return 'j';
        if($asc>=-16474 && $asc<=-16213) return 'k';
        if($asc>=-16212 && $asc<=-15641) return 'l';
        if($asc>=-15640 && $asc<=-15166) return 'm';
        if($asc>=-15165 && $asc<=-14923) return 'n';
        if($asc>=-14922 && $asc<=-14915) return 'o';
        if($asc>=-14914 && $asc<=-14631) return 'p';
        if($asc>=-14630 && $asc<=-14150) return 'q';
        if($asc>=-14149 && $asc<=-14091) return 'r';
        if($asc>=-14090 && $asc<=-13319) return 's';
        if($asc>=-13318 && $asc<=-12839) return 't';
        if($asc>=-12838 && $asc<=-12557) return 'w';
        if($asc>=-12556 && $asc<=-11848) return 'x';
        if($asc>=-11847 && $asc<=-11056) return 'y';
        if($asc>=-11055 && $asc<=-10247) return 'z';
        return false;
    }
    /**
     * @param $zh
     * @return string
     *
     * 获取整条字符串汉字拼音首字母
     */
    public static function firstSpelling($zh){
        $ret = "";
        $s1 = iconv("UTF-8","gb2312", $zh);
        $s2 = iconv("gb2312","UTF-8", $s1);
        if($s2 == $zh){$zh = $s1;}
        for($i = 0; $i < strlen($zh); $i++){
            $s1 = substr($zh,$i,1);
            $p = ord($s1);
            if($p > 160){
                $s2 = substr($zh,$i++,2);
                $ret .= static::pinyinToFirst($s2);
            }else{
                $ret .= $s1;
            }
        }
        return $ret;
    }
    /**
     * @param $app_alias
     * @return mixed
     * @throws \Exception
     *
     * Get System Category Data
     */
    public static function getSystemCategory($app_alias){
        //获取数据
        if(!in_array($app_alias,[1,2,3,4,5])){
            throw new \Exception('非法的group_id');
        }
        $group_id = $app_alias;
        $params = [
            'group_id'=>$group_id
        ];
        $url = config('mbcore_mcore.core_category_url','https://mbdev-config.pettyb.com/');
        $urlApi =$url.'service/category/get';
//        $client = new Client();
        $client = new Client(['verify'=> false]);
        $result = $client->request('post', $urlApi, [
            'form_params' => $params
        ]);
        try{
            $data = $result->getBody()->getContents();

            $data = json_decode($data,1);
            // 接口code返回抛出异常信息
            if($data['code'] != 1){
                throw new \Exception($data['result']['msg']);
            }

        }Catch(\Exception $e){

            throw new \Exception($e->getMessage());
        }
        return $data;
    }

    /**
     * 两个位置的距离计算
     * @param float $longitude1
     * @param float $latitude1
     * @param float $longitude2
     * @param float $latitude2
     * @param int $unit
     * @param int $decimal
     * @return float
     */
    public static function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit=2, $decimal=2){

        $EARTH_RADIUS = 6370.996; // 地球半径系数
        //$PI = 3.1415926;
        $PI = M_PI;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI /180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if($unit==2){
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }

    /**
     * @param $str
     * @return string
     *
     * Unicode编码方法【将中文转为Unicode字符】
     */
    public static function unicodeEncode($str){
        # split word
        preg_match_all('/./u',$str,$matches);
        $unicodeStr = "";
        foreach($matches[0] as $m){
            # 拼接
            $unicodeStr .= "&#".base_convert(bin2hex(iconv('UTF-8',"UCS-4",$m)),16,10);
        }
        return $unicodeStr;
    }

    /**
     * @param $unicode_str
     * @return string
     *
     * unicode解码方法【将的unicode字符转换成中文】
     */
    public static function unicodeDecode($unicode_str){
        $json = '{"str":"'.$unicode_str.'"}';
        $arr = json_decode($json,true);
        if(empty($arr)) return '';
        return $arr['str'];
    }

    /**
     * @param $arr
     * @param $key
     * @return mixed
     *
     * 数据库查询数据更具指定字段去除重复数据
     */
    public static function unique_array($arr, $key) {
        $tmp_arr = array();
        $tmp_array = array();
        foreach ($arr as $k => $v) {
            # 搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
            if (in_array($v[$key], $tmp_arr)) {
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
                $tmp_array[] = $v;
            }
        }
        # sort 函数对数组进行排序
        sort($tmp_array);
        return $tmp_array;
    }

    /**
     * @param $msg
     * @param int $code
     * @param int $httpCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function returnSuccess($msg, $code = 1, $httpCode = 200)
    {
        return response()->json([
            'code' => $code,
            'result' => $msg
        ], $httpCode, [], 271);
    }

    /**
     * @param $msg
     * @param int $code
     * @param int $httpCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function returnError($msg, $code = 0, $httpCode = 200)
    {
        return static::returnSuccess([
            'msg' => $msg,
        ],$code,$httpCode);

    }

}