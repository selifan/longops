# LongOps: Helper for performing long PHP operations that exceeds max_execution_time limit

If you use "virtual server" hosting for your sites, then you know the problem.
It's name "max_execution_time". Ususaly this means "30 seconds for your PHP code", no more.
And ini_set('max_execution_time',6000) doesn't help, because your ISP won't allow it. So if you want to perform some long operation, like making non-standard data backups or import/export really big data from/to file, you have a problem.

`longOps` is a helper class that allows you to "split" your long-lasting operation to smaller "chunks" and perform the job in "step by step" manner, so every "step" never exceeds php execution limit time.
Additionally you can get the real progress bar of the whole work (not just animated gif) and get a "red button" for stopping it.
All you have to do is rewrite your "long" function so it can resume the job exactly on the point where it was "paused" in previous run. And it should check if "user asked cancel", perform cleanup operation and pass control to the "abortProcess()" class method.

Detailed documentation can be found in the doc folder :

["English documentation"](doc/longops.en.htm)

## Features

* Time-unlimited execution of your PHP scripts
* Job can be cancelled by user at any time (maximun waiting is equal to "time execution", configured by you for one "chunk" of processing.
* Provided javascript module  `longops.jQuery.js` makes all nesessary HTML code for client side (jQuery and jQuery.UI used to show dialog window, progress bar and send AJAX queries)

If you don't use jQuery or jQuery.UI, you'll have to write your own client-side js code.

## License

Code distributed under [MIT license](LICENSE)
