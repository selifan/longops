<?php
/**
* @package longops
* @name class.longops.php
* Helper for creating "long operations" under small time execution limit for PHP files
* @Author Alexander Selifonov <alex [at] selifan {dot} ru>
* @Version 0.9
* @link http://www.selifan.ru
* modified 17.09.2013 [started: 11.09.2013]
**/

abstract class LongProcess {
    public abstract function start($params=array()); // perform initialization before main working loop
    public abstract function resume($params=array()); // restore state before continue main working loop
    public abstract function cancel(); // cleanup code before cancel job
    public abstract function saveState(); // save current state before pausing
    public abstract function action();  // perform single action in main working loop
    public abstract function finish();  // cleanup code before succsessful finish of the whole job

}

class LongOps {

    private $_maxtime = 0;
    private $_startTime = 0;
    private $_params = false;
    private $_aborted = 0;
    private $_cliparam = array();
    private $_processId = '_LONGOPS_';
    private $_errormessage = '';
    private $_handler = null;
    private $lastItem = 0;
    private $itemCount = 0;

    public function getErrorMessage() { return $this->_errormessage; }

    public function __construct($handlerfunc, $options=false) {

        $this->_handler = $handlerfunc;
        if(!isset($_SESSION) ) {
            if(!headers_sent()) session_start();
            if(!isset($_SESSION)) die('LongOps ERROR: Cannot start session !');
        }
        if(is_array($options)) {
            if(!empty($options['processid'])) $this->_processId = (string)$options['processid'];
        }
        $this->_cliparam = array_merge($_GET,$_POST);
        $serverlimit = ini_get('max_execution_time');
        $this->_maxtime = ceil($serverlimit/2);
        if($this->_maxtime == 0) $this->_maxtime = 5;
        if(isset($options['maxtime']) and $options['maxtime'] > 0) $this->_maxtime = min($options['maxtime'],$serverlimit*0.8);

        if(isset($this->_cliparam['longops_action'])) {
            if($this->_cliparam['longops_action'] === 'abort') {
                $this->_aborted = 1;
            }
        }

        $this->_startTime = time();
    }

    public function isAborted() { return $this->_aborted; }

    public function startProcess() {

        $_SESSION[$this->_processId] = array();
        $_SESSION[$this->_processId]['iteration'] = 1;
        $_SESSION[$this->_processId]['checkpoint'] = array('lastItem'=>0, 'itemCount'=>0, 'message'=>'started');
        $_SESSION[$this->_processId]['userparams'] = $this->_params;
        $opts = array_merge($_SESSION[$this->_processId]['checkpoint'], $this->_params) ;
        $result = $this->runCallback($opts); # if time is out, we won't come back here!
        # if operation ended before time is out, quit with "finished" response
        $this->endProcess($result);
    }

    /**
    * Call this function periodically from your script.
    * When it's time to stop operation, needed parameters are saved to SESSION, execution paused,
    * and resonse with "done NN %" will be returned to the client
    */
    public function isTimedOut($params=false) {

        $this->_errormessage = '';
        $this->_aborted = $ret = 0;
        if(empty($this->_startTime)) {
            $this->_errormessage = 'isTimedOut: wrong call? start time not fixed';
#           exit($this->_errormessage); // debug
            return false;
        }

        $elapsed = time() - $this->_startTime; // elapsed time in seconds
        if($elapsed >= $this->_maxtime) {
            return 1;
        }
        return 0;
    }
    public function pauseProcess($params=false) {
        $itemNo = isset($params['lastItem']) ? $params['lastItem'] : 1;
        $itemCount = isset($params['itemCount']) ? $params['itemCount'] : 100;
        $comment = isset($params['message']) ? $params['message'] : 'Processing...';
        $_SESSION[$this->_processId]['checkpoint'] = array('lastItem'=>$itemNo,'itemCount'=>$itemCount,'message'=>$comment);

        $ret = "working|".floor($itemNo*100/$itemCount).'|'.$comment;
        exit($ret);
    }
    /**
    * When client request "resume" put your comment there...
    *
    * @param mixed $params
    * @return assoc/array with all data saved when last "pause" occured. You use it to resume your work from the right "checkpoint"
    */
    public function resumeProcess($params=false) {

        $this->_errormessage = '';
        if(!isset($_SESSION[$this->_processId]['checkpoint'])) {
            $this->_errormessage = 'Cannot find checkpoint info';
            return false;
        }
        $_SESSION[$this->_processId]['starttime'] = time();
        $uparams = isset($_SESSION[$this->_processId]['userparams'])? $_SESSION[$this->_processId]['userparams'] : array();
        $opts = array_merge($_SESSION[$this->_processId]['checkpoint'],$uparams, $params) ;

        $result = $this->runCallback($opts);
        if($this->isTimedOut()) $this->pauseProcess($result);
        else                    $this->endProcess($result);

    }

    /**
    * You call abortProcess() when "abort" request has come from client (that means user pressed "Stop/abort" button)
    *
    * @param mixed $params
    */
    public function abortProcess($params=false) {

        $this->_errormessage = '';
/*        if(!isset($_SESSION[$this->_processId]['checkpoint'])) {
            die('abortProcess withount SESSION['.$this->_processId.']');
            return false;
        }
*/
        unset($_SESSION[$this->_processId]);
        $percent = isset($params['lastItem']) ? floor($params['lastItem']*100/$params['itemCount']) : 0;
        $message = isset($params['message']) ? $params['message'] : 'Aborted by user';
        exit("aborted|$percent|$message");

    }

    /**
    * call endProcess() from your "long process" when all actions done, (100% performed)
    * It performs cleanup actions and sends "finished" response to the client
    */
    public function endProcess($params=false) {

        unset($_SESSION[$this->_processId]);
        $msg = isset($params['message']) ? $params['message'] : 'Process finished';
        exit("finished|100|$msg");
    }
    /**
    * Dispatching incoming request
    *
    * @param mixed $params
    */
    public function dispatch($params=false) {

        $this->_params = is_array($params) ? $params : array_merge($_GET,$_POST);
        $action = isset($this->_params['longops_action']) ? $this->_params['longops_action'] : '';

        if(is_object($this->_handler) and is_a($this->_handler, 'LongProcess')) {
            if(!$action) $action = 'start';
            $this->mainLoop($action);
        }
        else {
            if(!$action) die('LongOps ERROR: No action passed');
            switch($action) {

                case 'start':
                    $this->startProcess();
                    break;
                case 'resume':
                case 'abort':
                    $result = $this->resumeProcess($params);
                    break;
                default:
                    die("LongOps ERROR: Undefined longopt action [$action]");

            }
            exit;
        }
    }
    /**
    * organizing "main working" loop inside this class, calling interface function from user object
    *
    * @param mixed $action
    */
    private function mainLoop($action) {
        $result = array();
        switch($action) {
            case 'start':
                $_SESSION[$this->_processId] = array();
                $_SESSION[$this->_processId]['iteration'] = 1;
                $_SESSION[$this->_processId]['checkpoint'] = array('lastItem'=>0, 'itemCount'=>0, 'message'=>'started');
                $_SESSION[$this->_processId]['userparams'] = $this->_params;
                $opts = array_merge($_SESSION[$this->_processId]['checkpoint'], $this->_params) ;
                $result = $this->_handler->start($opts);
                $_SESSION[$this->_processId]['checkpoint']['itemCount'] = isset($result['itemCount']) ? $result['itemCount'] : 100;
                break;
            case 'resume':
                $params = array();
                $params['lastItem'] = $_SESSION[$this->_processId]['checkpoint']['lastItem'];
                $params['itemCount'] = $_SESSION[$this->_processId]['checkpoint']['itemCount'];
                $this->_handler->resume($params);
#                $result = $this->resumeProcess($params);
                break;
            case 'abort':
                $result = $this->_handler->cancel();
                $this->abortProcess($result);
                break;
            default:
                die('Undefined command passed:'.$action);
        }
        while(true) {
            $result = $this->_handler->action();
            if(is_string($result)) {
                $this->endProcess(array('message'=>$result));
            }
            elseif($result === FALSE) {
                $this->endProcess(array('message'=>'Advanced finish'));
            }
            if($this->isTimedOut()) {
                $result = $this->_handler->saveState();
                if(!isset($result['lastItem'])) {
                    $result = $_SESSION[$this->_processId]['checkpoint'];
                }
                $this->pauseProcess($result);
            }
            $_SESSION[$this->_processId]['checkpoint']['lastItem'] +=1;

            if(isset($result['itemCount'])) $_SESSION[$this->_processId]['checkpoint']['itemCount'] = $result['itemCount'];

            if($_SESSION[$this->_processId]['checkpoint']['lastItem'] >= $_SESSION[$this->_processId]['checkpoint']['itemCount']
              or !empty($result['finished'])) break;
        }
        $result = $this->_handler->finish();
        $this->endProcess($result);
    }

    private function runCallback($params) {
        $ar = explode('::', $this->_handler);
        if(count($ar)>1) {
            if(method_exists($ar[0],$ar[1])) {
                $class = $ar[0];
                $method = $ar[1];
                return $class::$method($this, $params);
            }
            else die('LongOps ERROR: Undefined callback class::function passed '.$this->_handler);
        }
        else {
            if(function_exists($this->_handler))
                return call_user_func_array($this->_handler, array($this, $params));
            else die('LongOps ERROR: Undefined callback function passed '.$this->_handler);
        }
    }
} // LongOps() end