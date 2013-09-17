<?php
/**
* @name demo.php
* Demonstration for class.longops.php
* @author Alexander Selifonov
* @license MIT
*/

session_start(); // We'll need SESSION to store the state between "working iterations"

?>
<!DOCTYPE html>
<head>
<title>Longops Demonstration</title>
<meta charset="utf-8">
<style type="text/css">
  body { margin:0; padding: 0; font:normal 12px verdana,tahoma,arial; text-align:center }
 .div_shade {
      box-shadow:4px 4px 4px rgba(80,80,80,0.8);
      -webkit-box-shadow:4px 4px 4px rgba(80,80,80,0.8);
      -moz-box-shadow:4px 4px 4px rgba(80,80,80,0.8);
  }
  .result { border:1px solid #aae; text-align:left; padding:8px; background: #eef; width:80%; margin-left:auto; margin-right:auto;}
</style>
<link rel="stylesheet" href="jquery-ui-custom.css" type="text/css" />

<script src="jquery.min.js"></script>
<script src="jquery-ui-custom.min.js"></script>
<script src="../src/longops.jQuery.js"></script>

<script type="text/javascript">
function startLongop() {
    var options = {
        backend: 'demo-backend.php'
       ,title : 'Very long operation (Procedural)'
       ,comment: 'Relax and enjoy the progress.'
//       ,autoClose: 2 // when process finished, modal dialog will close in 2 seconds
       ,dialogClass:'div_shade'
       ,width:400
       ,onSuccess: function() {
           $('#result').append('Your long operation successfully finished<br>');
       }
       ,onCancel: function() { $('#result').append('Your long operation was canceled<br>'); }
    },
    params = { useroperation:'backupme', userdate:'2013-09-16'};
    longOps.start(params, options);
}
</script>

</head>
<h2>PHP LongOps (Long Operations) demonstration</h2>
<br><br><br>
To start "long operation" emulation, Click the button below: <br><br>
<input type="button" class="button" onclick="startLongop()" value="Start the demo" />
<br><br>
<div id="result" class="result" style="height:80px; overflow:auto"></div>

</body></html>