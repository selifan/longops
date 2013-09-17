<?php
/**
* @name demo-backend.php - backend module for the demo.php
* Demonstration for class.longops.php
* @author Alexander Selifonov
* @license MIT
*/

require_once('../src/class.longops.php');

session_start();

$params = array_merge($_GET,$_POST);

if(isset($params['longops_action'])) {
    # 'maxtime': we give 2 seconds for one working session
    $longop = new LongOps('myLongOperation', array('processid'=>'LONGOPT_DEMO', 'maxtime'=>2));
    $longop->dispatch($params);
}
else die('Empty backend call (no longops_action passed) !');

function myLongOperation($longoptObj, $opt=array()) {

    if($longoptObj->isAborted()) {

        # Here make your "Before Abort" cleanup (close/delete unfinished files, drop temp tables in DB etc.)
        # After that you can call abortProcess();
        $opt['message'] = 'Stopped by user at '.date('H:i:s');
        $longoptObj->abortProcess($opt);

    }

    if(empty($opt['lastItem'])) {
        $opt['lastItem'] = 0;
        $opt['itemCount'] = 50;
        # Starting operation: Peform initial tasks (create output file, count items to work with etc.
    }
    else {
        # We're going to resume from paused job:
        # Re-open output file(s) and fseek to the end of it, restore other working parameters.
    }

    # Main working loop
    while($opt['lastItem'] < $opt['itemCount']) {
        # our long action works here:

        $opt['lastItem'] += 1;
        usleep(500000); # 0.5 sec delay simulates loong job...

        if($longoptObj->isTimedOut($opt)) {
            # here You have to close output file, to reopen it in next work-session for appending - fopen(fname,'a')
            $opt['message'] = 'Processed:'.$opt['lastItem'].' of '.$opt['itemCount'] . ' items';
            $longoptObj->pauseProcess($opt); # will exit($response);
        }
    }

    # Here is the place for "final" cleanups, when the "long job" successfully finished.

    $opt['message'] = 'Success, handled items: '.$opt['lastItem'];
    return $opt;
}