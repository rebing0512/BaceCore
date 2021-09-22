<?php
namespace Jenson\Core\Console\Commands;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use League\Flysystem\Exception;
use Illuminate\Support\Facades\Config;  //临时config解决方案

class CoreDBCommand extends BaseCommand
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //定义期望输入
    protected $signature = 'Jesnon:updatedb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jesnon Update DB Command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        //migrate 执行状态，默认不执行
        $migrate_run = false;
        $migrate_command = "php artisan migrate";

        $MysqlVersion = DB::select('SELECT VERSION() as mysql_version');
        $version =$MysqlVersion[0]->mysql_version;


        //$command = "php -v";
        $command = "php artisan migrate --pretend";
        //try{

            //执行命令获取需要执行的文件信息
            $retlog = exec($command,$output,$retval);
        //}catch(Exception $e){
        //    $this->info($e); //命令失败
        //}
        //$retval == 1 说明错误  == 0 说明执行成功

        // $this->info("retval:".$retval);
        // $this->info("retlog:".$retlog);
        $this->info('---------migrate --pretend success!---------');

        //$retval == 1 说明错误  == 0 说明执行成功
        if($retval){
            $this->info($this->getsrt("[Err] Command migrate --pretend failure!")); //命令失败
            return true;
        }
        //如果无可以更新的则不做任何其他事情
        if($retlog=="Nothing to migrate."){
            $this->info($this->getsrt("---------[Info] No database update!---------")); //没有数据库更新
            return true;
        }


        //如果仍需进行操作则进行数据处理
        $sqlArr = [];
        $i = 0;
        $this->info($this->getsrt("---------SQL parsing---------"));
        foreach($output as $val){
            //$this->info($this->getsrt("---------".$i."--------:".$val));
            $i++;
            $this_sql = "";
            if($val == "Migration table created successfully."){
                $this->info("---------[Info] SQL:migrations no validation---------");
                continue;
            }
            if(strpos($val,'Exception') !== false){
                //$this->info($this->getsrt("---------".$i."--------"));
                //如果存在异常，直接跳出终止操作
                $migrate_run = false;
                break;
            }
            //继续执行
            //UpdateMytest2Table: alter table `mytest2` add `deleted_at` timestamp null
            try{

                $this_sql = trim( explode(':',$val)[1] );
                $sql_information_schema = "information_schema.tables";
                if(strpos($this_sql,$sql_information_schema) !== false){
                    $this->info("---------[Info] SQL:information_schema.tables select ignore ---------");  // 输出sql语句
                    continue; //抛弃查询 information_schema.tables 的sql
                }
                //$this->info("SQL".$i.":".$this_sql);  // 输出sql语句
                if(!empty($this_sql)){
                    $sqlArr[md5($this_sql)] = $this_sql;
                }
                $migrate_run = true;
            }catch (\Exception $e){
                $this->info("[Err] SQL Str Err");
            }
        }

        //错误处理
        if(!$migrate_run){
            $this->info($this->getsrt("[Err] Database update failed"));//数据库更新失败
            $this->sendMsg("数据库更新失败，存在Exception Code 001！");
            //此处异常说明文件语法本身就有错误
            return false;
        }



        //////////////------------------------///////////////////////
        //做特殊内容解析处理
        //DDL: drop、create、alter
        $DDLArr = ['drop','create','alter'];
        //需要特殊处理的数组
        $ReturnArr = [];
        foreach($sqlArr as $val){
            $thisArr = explode(' ',$val);
            if(in_array($thisArr[0],$DDLArr)){
                $myReturnArr = ['exec'=>$thisArr[0],'table'=>trim($thisArr[2] ,"`")];
                $ReturnArr[$thisArr[0]][$thisArr[2]]=$myReturnArr;
            }
        }

        //备份结构的DB
        $BAKDB = [];
        $BAKDB_TEMP = [];

        //连接test数据库  需要配置test数据库
        Config::set('database.connections.mysql_test', config("mbcore_mcore.mysql_test",[])); //动态设定config
        //需要建立结构验证数据库
        $TestDB = DB::connection('mysql_test');
        $TestSchema = Schema::connection('mysql_test');
        //////////--------------------------
        //备份
        //show tables
        //$DBTable =  Schema::connection('mysql_test')->hasTable('users1');//$TestDB->table("1mytest_bak");//statement('SELECT * FROM `1mytest_bak`');
        //修改的
        $this->info($this->getsrt("---------Test the database backup table.---------"));
        if(isset($ReturnArr['alter'])&&is_array($ReturnArr['alter'])){
            foreach($ReturnArr['alter'] as $key=>$val){
                //echo $key;
                $table = $val['table'];
                if(!isset($BAKDB[$table]) && !isset($BAKDB_TEMP[$table]) ){
                    if($TestSchema->hasTable($table)){
                        $TestDB->statement('drop table if exists `'.$table.'_bak`');
                        if ($version <= "5.6.00") {
                            $TestDB->statement('CREATE TABLE `' . $table . '_bak` SELECT * FROM `' . $table . '`');
                        } else {
                            $TestDB->statement('CREATE TABLE `' . $table . '_bak` like `' . $table . '`');
                        }
                        $BAKDB[$table] = 1;
                    }else{
                        //$this->info($this->getsrt("---------alter:No backup, the database does not exist:".$table."---------"));//未备份，数据库不存在
                        $BAKDB_TEMP[$table] = 1;
                    }
                }
            }
        }
        //删除的
        if(isset($ReturnArr['drop'])&&is_array($ReturnArr['drop'])){
            foreach($ReturnArr['drop'] as $key=>$val){
                //echo $key;
                $table = $val['table'];
                if(!isset($BAKDB[$table]) && !isset($BAKDB_TEMP[$table]) ){
                    if($TestSchema->hasTable($table)){
                        $TestDB->statement('drop table if exists `'.$table.'_bak`');
                        if ($version <= "5.6.00") {
                            $TestDB->statement('CREATE TABLE `' . $table . '_bak` SELECT * FROM `' . $table . '`');
                        } else {
                            $TestDB->statement('CREATE TABLE `' . $table . '_bak` like `' . $table . '`');
                        }
                        $BAKDB[$table] = 1;
                    }else{
                        //$this->info($this->getsrt("---------drop:No backup, the database does not exist:".$table."---------"));
                        $BAKDB_TEMP[$table] = 1;
                    }
                }
            }
        }
        /////////---------------------------


        //启动数据库事务
        $TestDB->beginTransaction();
        $this->info($this->getsrt("---------Start the database transaction!---------"));//启动数据库事务！
        //验证数据库sql
        try {
            foreach( $sqlArr as $key=>$val){
                //$this->info($val);
                $TestDB->statement($val);
            }

            $TestDB->commit();
            //标记可以运行命令
            $migrate_run = true;
            $this->info($this->getsrt("---------Execute the transaction commit!---------"));//
        }catch(QueryException $e){
            //如果执行一场说明和数据库本身有冲突
            $migrate_run = false;
            $TestDB->rollBack();

            /////////---------------------------
            //恢复表结构  删除和修改的表 重建
            foreach($BAKDB as $key=>$val){
                $table = $key;
                $TestDB->statement('drop table if exists `'.$table.'`');
                if ($version <= "5.6.00") {
                    $TestDB->statement('CREATE TABLE `' . $table . '` SELECT * FROM `' . $table . '_bak`');
                } else {
                    $TestDB->statement('CREATE TABLE `' . $table . '` like `' . $table . '_bak`');
                }
            }
            //对于新增的表直接删除
            if(isset($ReturnArr['create'])&&is_array($ReturnArr['create'])){
                foreach($ReturnArr['create'] as $key=>$val){
                    $table = $val['table'];
                    $TestDB->statement('drop table if exists `'.$table.'`');
                }
            }
            /////////---------------------------
            $this->info($this->getsrt("[Err] Execute the transaction QueryException!"));//执行事务回
            $this->info($e);//执行事务回

        }

        //验证执行方法
        if(!$migrate_run){
            $this->info($this->getsrt("[Err] Database update failed!"));//数据库更新失败！
            $this->sendMsg("数据库更新失败，存在Exception Code 002！");
            //此处异常说明数据库字段修改冲突
            return false;
        }else{
            system($migrate_command);
            $this->info($this->getsrt("---------Database update successfully!---------"));//数据库更新成功！
            return true;
        }

    }


    private function getsrt($str){
        return $str;
        //return iconv("UTF-8", "GB2312//IGNORE",  $str);
    }

    private function sendMsg($str){
        //执行发送消息通知命令

    }



}
