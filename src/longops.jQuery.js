/**
* @package longops
* @name longops.jQuery.js
* javascript functions for "class.longops.php"
* @requires jquery 1.3+, jquery.UI 1.6+ (developed/tested on jQuery 1.4.4 and UI 1.8.13)
* class.longops.php is a helper for creating "long operations" under limited time execution for PHP files
* @Author Alexander Selifonov <alex [at] selifan {dot} ru>
* @Version 0.01.001
* @link http://www.selifan.ru
* modified 16.09.2013 (dd.mm.yyyy)
**/
longOps = {

    backend : './'
   ,b_aborting : false
   ,b_canClose : false
   ,autoClose: 0
   ,width:300
   ,lng : {
       btnStop:'Cancel'
      ,btnClose:'Close'
      ,btnStopping:'Stopping...'
   }
   ,onSuccess:null
   ,onError: null
   ,onCancel: null
   ,start: function (params, options) {
        longOps.b_aborting = longOps.b_canClose = false;
        wndtitle = typeof(options.title)=='undefined' ? 'Long operation' : options.title;
        comment = typeof(options.comment)=='undefined' ? '' : options.comment+'<br><br>';
        dlgClass = typeof(options.dialogClass)=='undefined' ? '' : options.dialogClass;
        if(typeof(options.backend)=='string') this.backend = options.backend;
        if(typeof(options.btnStop)=='string') this.lng.btnStop = options.btnStop;
        if(typeof(options.btnClose)=='string') this.lng.btnClose = options.btnClose;
        if(typeof(options.btnStopping)=='string') this.lng.btnStopping = options.btnStopping;
        if(typeof(options.width)!='undefined') this.width = options.width;
        if(typeof(options.width)!='onSuccess') this.onSuccess = options.onSuccess;
        if(typeof(options.width)!='onError') this.onError = options.onError;
        if(typeof(options.width)!='onCancel') this.onCancel = options.onCancel;

        if(typeof(options.autoClose)!='undefined') this.autoClose = options.autoClose-0;
        var htm = "<div id='div_longop'>"+comment+"<div id='progress_bar' /><br><div id='longop_comment'>&nbsp;</div></div>";
        $('#div_longop').remove();
        if(params) {
            if(typeof(params)==='string') params += '&longops_action=start';
            else if(typeof(params)==='object') params.longops_action = 'start';
            else { alert('Parameters shoul be string or array/object!'); return false; }
        }
        else params = { longops_action:'start' };
        $(htm).dialog({
            title: wndtitle
           ,closeOnEscape: true
           ,dialogClass : dlgClass
           ,modal: true
           ,width: this.width
           ,draggable: false
           ,resizable: false
           ,open: function( event, ui ) {
               $('#progress_bar', '#div_longop').progressbar();
               $(".ui-dialog-titlebar-close", '#div_longop').hide();
//               $.post(longOps.backend, params, longOps.loCallback);
               $.ajax({
                    url: longOps.backend,
                    data: params,
                    async: true,
                    error: function(ajaxrequest){
                        longOps.b_canClose = true;
                        $('#longop_comment','#div_longop').html(ajaxrequest.responseText);
                    },
                    success: longOps.loCallback
               });


           }
           ,buttons: {
               btCancel:{ text: longOps.lng.btnStop, id:'btnCancel', click: function() { longOps.cancel(); }}
              ,btCLose :{ text: longOps.lng.btnClose, click: function() { $( this ).dialog( "close" );}}
           }
           ,beforeclose: function () { return (longOps.b_canClose); }

        });
    }

    ,cancel: function() {
        longOps.b_aborting = true;
        $('#btnCancel').button( "option", "label", longOps.lng.btnStopping ).button('disable');
    }

    ,closeLongOpWindow: function () {
        $('#div_longop').dialog('close').remove();
    }

    ,loCallback: function(data) {
        var sp = data.split('|');
        if(sp[0] === 'finished' || sp[0] ==='aborted') {
            longOps.b_canClose = true;
            $('#progress_bar').progressbar('option','value',100);
            $('#longop_comment','#div_longop').html('');
            $('#btnCancel').button('disable');
            if( sp[0] === 'finished' && longOps.autoClose>0)
              window.setTimeout(longOps.closeLongOpWindow,longOps.autoClose*1000);
            longOps.finalAction(sp[0]);
        }
        else if(sp[0]==='working') {
            $('#progress_bar','#div_longop').progressbar('option','value',parseInt(sp[1]));
//            $.post(this.backend,{longops_action: (longOps.b_aborting ? 'abort':'resume')}, longOps.loCallback);
            $.ajax({
                url: longOps.backend,
                data: {longops_action: (longOps.b_aborting ? 'abort':'resume')},
                async: true,
                error: function(ajaxrequest){
                    longOps.b_canClose = true;
                    $('#btnCancel').button('disable');
                    $('#longop_comment','#div_longop').html(ajaxrequest.responseText);
                    longOps.finalAction('error');
                },
                success: longOps.loCallback
           });

        }
        else {
            longOps.closeLongOpWindow();
            alert(data);
            longOps.finalAction('error');
            return false;
        }
        if(sp.length>1) $('#longop_comment').html(sp[2]); // comment passed
    }
   ,finalAction: function(actype) {
        var faction = null;
        if(actype==='finished') faction = this.onSuccess;
        else if(actype==='aborted') faction = this.onCancel;
        else faction = this.onError;
        if(faction) {
            if($.isFunction(faction)) faction();
            else if(typeof(faction)==='string') eval(faction);
        }
    }
}
