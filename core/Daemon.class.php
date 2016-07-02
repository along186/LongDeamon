<?php
/**
 * @desc: PHP守护进程核心文件
 * ==============================================
 * along.com Lib
 * 版权所有 @2015 along.com
 * ----------------------------------------------
 * 这不是一个自由软件，未经授权不许任何使用和传播。
 * ----------------------------------------------
 * 权限（全局限制访问）
 * ==============================================
 * @date: 2016年5月19日 上午9:16:18
 * @author:liufeilong<alonglovehome@163.com>
 * @version: 1.0.0.0
 */
class Daemon {
    
    private $_task_num = NULL;
    
    private $_cur_pid_num = 0;  //当前进程数
     
    private $_data_dir = 'Data'; //输出日志文件
    
    private $_daemon_log = 'logs/Daemon.process.signal.log';
    
    private $_pid_file = 'run/Daemon.pid'; //进程ID存放路径
    
    private $_uid = 0; //进程所属用户ID
    
    private $_gid = 0; //进程所属用户组ID
    
    private $_sleep_time = 2; //子进程间歇时间
    
    private $_task_arr = array();
    
    private $_cur_class_path = NULL;
    
    private $_cur_class_name = NULL;
    
    private $_cur_obj = NULL;
    
    private $_cur_method = NULL;
    
    private $_cur_method_param = array();
    
    private $_cur_data_file = NULL;

    /**
     * 构造函数
     * @date: 2016年5月19日  上午9:44:12
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    public function __construct(){
        //1.初始化配置信息
        $this->_initConfig();
        
        //2.初始化任务列表
        $this->_initTask();
        
        //3.检查当前系统是否支持pcntl
        $this->_checkPcntl();
        
        //4.检查当前运行环境
        $this->_checkRunMode();
        
        //5.安装信号处理器
        $this->_signal();
    }
    /**
     * 初始化配置
     * @date: 2016年5月19日  上午10:28:15
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _initConfig(){
        $config_path = DAEMON_PATH.DS.'config/Daemon.ini';
        $config = parse_ini_file($config_path,TRUE);
        if($config['Daemon']['type'] == 'abs'){
            if(empty($config['Daemon']['data_dir'])){
                throw new DaemonException('data_dir must be set');
            }else{
                $this->_data_dir = $config['Daemon']['data_dir'];
            }
            if(empty($config['Daemon']['daemon_log_file'])){
                throw new DaemonException('daemon_log_file must be set');
            }else{
                $this->_daemon_log = $config['Daemon']['daemon_log_file'];
            }
            if(empty($config['Daemon']['pid_file'])){
                throw new DaemonException('pid_file must be set');
            }else{
                $this->_pid_file = $config['Daemon']['pid_file'];
            }
        }else{
            $this->_data_dir = DAEMON_PATH.DS.$this->_data_dir.DS;
            $this->_daemon_log = DAEMON_PATH.DS.$this->_daemon_log;
            $this->_pid_file = DAEMON_PATH.DS.$this->_pid_file;
            
        } 
    }
    /**
     * 为进程信号安装信号处理器
     * @date: 2016年5月19日  上午11:45:32
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _signal(){
        pcntl_signal(SIGTERM, array(&$this,'sig_handler'));
        pcntl_signal(SIGKILL, array(&$this,'sig_handler'));
        pcntl_signal(SIGHUP,  array(&$this,'sig_handler'));
        pcntl_signal(SIGUSR1, array(&$this,'sig_handler'));
        pcntl_signal(SIGTTOU, array(&$this,'sig_handler'));
        pcntl_signal(SIGTTIN, array(&$this,'sig_handler'));
        pcntl_signal(SIGINT, array(&$this,'sig_handler'));
    }
    /**
     * 检查环境是否支持pcntl支持
     * @date: 2016年5月20日  下午2:35:50
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _checkPcntl(){
        if(!function_exists('pcntl_signal_dispatch')){
            //如果当前php版本小于5.3,只能通过declare(ticks=N)来调用通过pcntl_signal()安装的信号处理器
            //每执行10条低级语句进行一个ticks
            declare(ticks = 10);
        }
        if(!function_exists('pcntl_signal')){
            die('PHP does not appear to be compiled with the PCNTL extension.  This is neccesary for daemonization \n');
        }
    }
    /**
     * 检查当前运行模式
     * @date: 2016年5月20日  下午2:53:12
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _checkRunMode(){
        set_time_limit(0);
        // 只允许在cli下面运行
        if (php_sapi_name() != "cli"){
            die("only run in command line(cli) mode\n");
        }
    }
    /**
     * 信号处理器
     * @date: 2016年5月18日  下午4:04:31
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    public function sig_handler($signo){ 
        switch ($signo) {
            case SIGTERM:
                $curpid = getmypid();
                $fp = fopen($this->_daemon_log, 'a');
                $message = "[".date('Y-m-d H:i:s')."]: $curpid process is killed by SYSTEM SIGTERM \r\n";
                fwrite($fp, $message);
                fclose($fp);
                exit($curpid);
                break;
            case SIGUSR1:
                $curpid = getmypid();
                $fp = fopen($this->_daemon_log, 'a');
                $message = "[".date('Y-m-d H:i:s')."]: $curpid process is killed by Daemon stop,it's in the middle of the exit\r\n";
                fwrite($fp, $message);
                fclose($fp);
                exit;
                break; 
            default:
                $fp = fopen($this->_daemon_log, 'a');
                $message = "[".date('Y-m-d H:i:s')."]: the process is receive $signo \r\n";
                fwrite($fp, $message);
                fclose($fp);
                break;
        }
    
    }
    /**
     * run
     * @date: 2016年5月17日  下午5:01:34
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _run(){
        $pid = pcntl_fork();
        if($pid == '-1'){
            die('could not fork');
        }elseif($pid){
            exit(); //在终端执行的父进程退出
        }else{
            //创建第一个子进程
            $sid = posix_setsid();
            if($sid < 0){
                exit();
            }
            posix_setuid($this->_uid);
            posix_setgid($this->_gid);
            for(;;){
                $pid = pcntl_fork();
                if($pid == '-1'){
                    die('could not fork');
                }elseif($pid){
                    $msg = "Daemon create ".$this->_task_arr['data'][$this->_cur_pid_num]['class']." task in ".$pid." process";
                    $this->_writelog($msg);
                    $this->_cur_pid_num++;
                    if($this->_cur_pid_num > ($this->_task_num-1)){
                        echo "Daemon create ".$this->_task_num." process 【ok】\n";
                        $this->_childSignalHandler();
                        break;
                    }
                }else{
                    $curpid = getmypid();
                    $curpid = $curpid.':';
                    file_put_contents($this->_pid_file,$curpid,FILE_APPEND);
                    $this->_cur_class_path = $this->_task_arr['data'][$this->_cur_pid_num]['path'];
                    @include_once($this->_cur_class_path);
                    $this->_cur_class_name = $this->_task_arr['data'][$this->_cur_pid_num]['class'];
                    $this->_createClassObj($this->_cur_class_name);
                    $this->_cur_method = $this->_task_arr['data'][$this->_cur_pid_num]['method'];
                    $this->_cur_method_param = $this->_task_arr['data'][$this->_cur_pid_num]['params'];
                    $this->_cur_data_file = $this->_data_dir.$this->_cur_class_name.'.log';
                    $this->_sleep_time = $this->_task_arr['data'][$this->_cur_pid_num]['sleep'];
                    while(true){
                        //循环执行任务类中对应的任务方法
                        $method = $this->_cur_method;
                        $data = $this->_cur_obj->$method($this->_cur_pid_num);
                        $this->_writeTaskDatalog($data,$this->_cur_data_file);
                        sleep($this->_sleep_time);
                    }
                }
            }
        }
        
    }
    /**
     * 记录Daemon日志
     * @param 参数类型 参数变量
     * @return
     * @date   : 2016年5月17日 下午5:48:46
     * @author : liufeilong <liufeilong@roadoor.com>
     * @vesion : 1.0.0.0
     */
    private function _writelog($message){
        $fp = fopen($this->_daemon_log, 'a');
        $message = "[".date('Y-m-d H:i:s')."]: ".$message." \r\n";
        fwrite($fp, $message);
        fclose($fp);
    }
    /**
     * 记录任务输出日志
     * @date: 2016年5月19日  下午12:21:00
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _writeTaskDatalog($message,$file){
        $fp = fopen($file, 'a');
        $message = "===================[".date('Y-m-d H:i:s')."]=====================\r\n ".$message." \r\n";
        fwrite($fp, $message);
        fclose($fp);
    }
    /**
     * 获得任务类实例
     * @date: 2016年5月19日  下午3:51:42
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _createClassObj($class){
        if(!is_object($this->_cur_obj) || !isset($this->_cur_obj)){
            if(class_exists($class,false)){
                $this->_cur_obj = new $class();
            }
        }
    }
    /**
     * 监听子进程
     * @date: 2016年5月20日  下午1:11:30
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _childSignalHandler(){
       $pid = pcntl_wait($status);
       while($pid > 0){
            //pid大于0说明子进程退出了，接下来，我们来处理子进程退出信息
            $msg = $pid." process is exit";
            $this->_writelog($msg);
            $pid = pcntl_wait($status);
        }
        return true;
    }
    /**
     * daemon start
     * @date: 2016年5月18日  上午9:35:34
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _start(){
        if(file_exists($this->_pid_file)){
            echo "PID file already exists\n";
            exit();
        }else{
            $this->_run();
        }
    }
    /**
     * daemon stop
     * @date: 2016年5月18日  上午10:30:25
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _stop(){
        if(file_exists($this->_pid_file)){
            $pids = file_get_contents($this->_pid_file);
            $pids = trim($pids,':');
            $pidarr = array();
            $pidarr = explode(':', $pids);
            $pidarr = array_reverse($pidarr);
            $c = count($pidarr);
            for($i=0;$i < $c;$i++){
                posix_kill($pidarr[$i], SIGUSR1);
            }
            unlink($this->_pid_file);
            echo "Daemon stop...............";
            for($i=0;$i<3;$i++){
                echo "..";
                ob_flush();
                flush();
                sleep(1);
            }
            echo "【OK】\n";
        }else{
            echo "Daemon Process is not in the beginning...........\n";
        }
    }
    /**
     * daemon reload
     * @date: 2016年5月18日  上午10:36:07
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _restart(){
        $this->_stop();
        $this->_start();
    
    }
    /**
     * daemon status
     * @date: 2016年5月18日  上午10:38:25
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _status(){
        if (file_exists($this->_pid_file)) {
            $pids = file_get_contents($this->_pid_file);
            $pids = trim($pids,':');
            $pidarr = array();
            $pidarr = explode(':', $pids);
            for($i=0,$c=count($pidarr);$i < $c;$i++){
                system(sprintf("ps ax | grep %s | grep -v grep", $pidarr[$i]));
            }
        }
    }
    /**
     * daemon help
     * @date: 2016年5月18日  上午10:38:45
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    private function _help($proc){
        printf("%s start | stop | restart | status | help \n", $proc);
    }
    /**
     * daemon main
     * @date: 2016年5月18日  上午10:39:37
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
     */
    public function main($argv){
        if(count($argv) < 2){
            $this->_help($argv[0]);
            printf("please input help parameter\n");
            exit();
        }
        if($argv[1] === 'stop'){
            $this->_stop();
        }else if($argv[1] === 'start'){
            $this->_start();
        }else if($argv[1] === 'restart'){
            $this->_restart();
        }else if($argv[1] === 'status'){
            $this->status();
        }else{
            $this->help($argv[0]);
        }
    }
    /**
     * 初始化任务列表
     * @date: 2016年5月19日  上午10:52:32
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _initTask(){
        $xmlstring = join("", file(DAEMON_PATH.DS.'config/TaskList.xml'));       
        $this->_task_arr = $this->_xml_to_array($xmlstring);
        $this->_task_num = count($this->_task_arr['data']);
    }
    /**
     * 把xml字符串转换成数组
     * @date: 2016年5月19日  上午11:02:22
     * @author: liufeilong(alonglovehome@163.com)
     * @version: 1.0.0.0
    */
    private function _xml_to_array($xmlstring) {
        return json_decode(json_encode(simplexml_load_string($xmlstring)),true);
    }
}