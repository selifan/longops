<?php
/**
* @name demo-backend.php - backend module for the demo.php
* Demonstration for class.longops.php
* @author Alexander Selifonov
* @license MIT
*/

require_once('../src/class.longops.php');

class myLongOp extends LongProcess {
    private $curPos = 0;
    private $count = 0;

    public function start($params=array()) { // perform initialization before main working loop
        $this->curPos = 0;
        $this->count = 100;
        return array('lastItem'=>$this->curPos, 'itemCount'=>$this->count);
    }
    public function resume($params=array()) { // restore state before continue main working loop
        $this->curPos = isset($params['lastItem'])? $params['lastItem'] : 'xxx';
        if(isset($params['itemCount'])) $this->count = $params['itemCount'];

    }
    public function cancel() { // cleanup code before cancel job
        # cleanup code before cancellng job
        $ret = array('message'=>'You cancelled this job at '.date('H:i:s'));
        return $ret;
    }
    public function saveState() {
        # save current state before pausing, return 'message' if needed, to show it user
        return array('lastItem'=>$this->curPos, 'itemCount'=>$this->count,'message'=>"done $this->curPos of $this->count");
    }
    public function action() {  // perform single action in main working loop
        $this->curPos ++;
        usleep(250000);
//      # advanced finish if you need it for some reason:
//      if($this->curPos>40) return 'We have finished right now!';
        return array('lastItem'=>$this->curPos, 'itemCount'=>$this->count);
    }
    public function finish() {  // cleanup code before succsessful finish of the whole job
        # make cleanup procedures...
        return array('lastItem'=>$this->curPos, 'itemCount'=>$this->count, 'message'=>'Finished at '.date('H:i:s'));
    }
}

session_start();

# $params = array_merge($_GET,$_POST);

# if(isset($params['longops_action'])) {
    # 'maxtime': we give 2 seconds for one working session
    $myHandler = new myLongOp();
    $longop = new LongOps($myHandler, array('processid'=>'LONGOPT_OOP', 'maxtime'=>2));
    $longop->dispatch();
# }
# else die('Empty backend call (no longops_action passed) !');
