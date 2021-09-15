<?php
namespace MBCore\MCore;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Blade;

//use MBCore\MCore\Console\Commands\MBCoreCommand;

//
class ServiceProvider extends BaseServiceProvider{

    /**
     * 在注册后进行服务的启动。
     *
     * @return void
     */
	public function boot()
	{

        // 模板机制中使用的量
        Blade::directive('getImageUrl', function($picturerHash,$size='0,300') {
            return "<?php echo $picturerHash?config('mbcore_mcore.storage_url').config('mbcore_mcore.storage_get_url_api').'/'.$picturerHash.'?size=$size&t=1&redirect=1':config('mbcore_mcore.storage_url').'/image/no_image.jpg'; ?>";
        });
        // <img src="@getImageUrl(c8545f2a2efa97d9b561644d389bf4c02f9cd77c)">


        // 【ok】【1】发布扩展包的配置文件
        $this->publishes([
            __DIR__.'/config/mbcore_mcore.php' => config_path('mbcore_mcore.php'),
        ], 'config');


	    //主域名根目录
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


        // 【6】注册 Artisan 命令
        if ($this->app->runningInConsole()) {
            $this->commands([
//               MBCoreCommand::class,
            ]);
        }
	}


    /**
     * 在容器中注册绑定。
     *
     * @return void
     */
	public function register()
	{
        // 默认的包配置
        $this->mergeConfigFrom(
            __DIR__.'/config/mbcore_mcore.php', 'mbcore_mcore'
        );
	}


}