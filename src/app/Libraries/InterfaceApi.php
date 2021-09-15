<?php
namespace MBCore\MCore\Libraries;

use GuzzleHttp\Client;

class  InterfaceApi
{
    /**
     * @param string $type
     * @param $params
     * @param $url
     * @return array|mixed
     * @throws \Exception
     *
     * 请求远程接口
     */
    public static function client($type ,$params,$url)
    {
        $client = new Client();
        if($type == 'post'){
            $result = $client->post($url,[
                'form_params'=>$params
            ]);

        }elseif($type == 'get'){
            $result = $client->get($url);
        }else{
            throw new \Exception('接口请求方式异常');
        }
        try{
            $data = $result->getBody()->getContents();
            if(stripos($data,"Error")!== false){

                throw new \Exception('，接口请求内容错误');
            }
            $data = json_decode($data,1);
        }Catch(\Exception $e){
            throw new \Exception ($e->getMessage());
        }
        $response = isset($data['result'])?$data['result']:$data['msg'];
        return $response;
    }

}