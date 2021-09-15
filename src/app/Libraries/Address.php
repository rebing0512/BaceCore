<?php
namespace MBCore\MCore\Libraries;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;


class Address
{
    /**
     * @return string
     *
     * Address Url
     */
    public static function addressUrl(){
        $mbcore_url = config("mbcore_mcore.pmcore_url",'https://pmcore.mbcore.com/');
        $url = $mbcore_url.'addresscenter/';
        return $url;


    }

    /**
     * @param $userId
     * @param $appid = null
     * @return array|mixed|string
     * @throws \Exception
     *
     * 获取我的默认地址【AddressCenter】
     */
    public static  function getDefaultAddress($userId,$appid=null){
        if(!$appid){
            $appid = config('app.id');
        }
        $url = static::addressUrl().'api/getDefaultAddress/';
        $client = new Client(['verify'=> false]);
        $result = $client->post($url,[
            'form_params'=>[
                'userid'=>$userId,
                'appid'=>$appid,
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
     * @param $address_id
     * @return array|mixed
     *
     * 获取我指定地址【getAppointAddress】
     */
    public static  function getAppointAddress($address_id){
        $url = static::addressUrl().'api/getAppointAddress/';
        $client = new Client(['verify'=> false]);
        $result = $client->post($url,[
            'form_params'=>[
                'address_id'=>$address_id,
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
}