<?php
/*
Copyright (C) 2014-2015 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Authors: Alexander Wagner, Stefan Weil

References:

File upload (general)

* http://www.php.net/manual/en/features.file-upload.post-method.php

File upload with dropzone

* http://www.dropzonejs.com/
* http://www.startutorial.com/articles/view/how-to-build-a-file-upload-form-using-dropzonejs-and-php
* http://maxoffsky.com/code-blog/howto-ajax-multiple-file-upload-in-laravel/

Websockets:

* https://en.wikipedia.org/wiki/Server-sent_events
* https://developer.mozilla.org/en-US/docs/WebSockets/Writing_WebSocket_client_applications
* http://code.google.com/p/phpwebsocket/
* http://dharman.eu/?menu=phpWebSocketsTutorial

Keyboard input

* http://jsfiddle.net/angusgrant/E3tE6/
* http://stackoverflow.com/questions/3181648/how-can-i-handle-arrowkeys-and-greater-than-in-a-javascript-function-which
* http://stackoverflow.com/questions/5597060/detecting-arrow-key-presses-in-javascript
* http://www.quirksmode.org/js/keys.html

Key symbols

* http://www.tcl.tk/man/tcl8.4/TkCmd/keysyms.htm

* wmctrl, suckless-tools (lsw, sprop, wmname, ...)

* display.im6, evince

Authorization

* http://aktuell.de.selfhtml.org/artikel/php/loginsystem/

Overlays

* http://answers.oreilly.com/topic/1823-adding-a-page-overlay-in-javascript/

*/

    session_start();
    if (isset($_REQUEST['monitor'])) {
        $monitor = $_REQUEST['monitor'];
        $_SESSION['monitor'] = $monitor;
    } elseif (!isset($_SESSION['monitor'])) {
        $_SESSION['monitor'] = '???';
    }
    $_SESSION['referer'] = 'index.php';
    require('auth.php');

    // Connect to database and get configuration constants.
    require_once('DBConnector.class.php');
    $dbcon = new DBConnector();

    // Support localisation.
    require_once('gettext.php');

    $user = false;
    if (isset($_SESSION['username'])) {
        # PHP session based authorization.
        $username = $_SESSION['username'];
        $address = $_SESSION['address'];
        $user = "$username@$address";
    } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
        # .htaccess basic authorization.
        $user = $_SERVER['PHP_AUTH_USER'];
    }

    $winvnc = CONFIG_START_URL . "/theme/" . CONFIG_THEME . "/winvnc-palma.exe";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PalMA</title>

<link rel="icon" href="theme/<?=CONFIG_THEME?>/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="pure-min.css">
<link rel="stylesheet" href="palma.css" type="text/css">

<script type="text/javascript" src="dropzone.js"></script>

<script type="text/javascript">

// Screen section which responds to keyboard input.
var focus_section = '1';

function sendToNuc(command) {
  var xmlHttp = new XMLHttpRequest();
  if (!xmlHttp) {
    // TODO
    alert('XMLHttpRequest failed!');
    return;
  }
  var url = 'control.php?' + command;
  var response = "";
  xmlHttp.open("get", url, true);
  xmlHttp.onreadystatechange = function () {
      if (xmlHttp.readyState == 1) {
          // Server connection established (IE only).
      } else if (xmlHttp.readyState == 2) {
          // Data transferred to server.
      } else if (xmlHttp.readyState == 3) {
          // Server is answering.
      } else if (xmlHttp.readyState == 4) {
          // Received all data from server.
          return xmlHttp.responseText;
      } else {
          alert("Got xmlHttp.readyState " + xmlHttp.readyState);
      }
  };
  xmlHttp.send(null);
  //~ alert("sendToNuc " + url);
}

function keyControl(number, image, key, handler, disabled, title) {
  var td = document.createElement('td');

  var keyHandler = getHandlerCommand(handler, key);
  if ( (keyHandler == "") || (keyHandler == null) ) {
    keyHandler = "default";
  }

  var button = document.createElement('button');
  var i = document.createElement('i');
  i.setAttribute('class', 'fa fa-fw ' + image);
  button.setAttribute('class', 'controlbutton pure-button pure-button-primary pure-input-rounded');
  button.appendChild(i);
  button.setAttribute('onmousedown',
                     'sendToNuc("window=' + number + '&keydown=' + encodeURIComponent(keyHandler) + '")');
  button.setAttribute('onmouseup',
                     'sendToNuc("window=' + number + '&keyup=' + encodeURIComponent(keyHandler) + '")');
  button.setAttribute('title', title);
  if (disabled) {
    button.setAttribute('disabled', '');
  }
  td.appendChild(button);
  return td;
}

function remoteControl(number, control) {

    var overlay = document.createElement('div');
    overlay.setAttribute('id', 'overlay');
    overlay.setAttribute('class', 'overlay');

    var container = document.createElement('div');
    container.setAttribute('id', 'container');

    var caption = document.createElement('div');
    caption.setAttribute('id', 'caption');
    caption.appendChild(document.createTextNode('<?=_("Screen section")?> '+ number));

    var close = document.createElement('div');
    var i = document.createElement('i');
    i.setAttribute('class', 'fa fa-times');
    i.setAttribute('title', '<?=_("Close the advanced control")?>');
    close.setAttribute('id', 'close');
    close.appendChild(i);
    close.setAttribute('onclick',       'restore()');

    container.appendChild(close);
    container.appendChild(caption);
    var table = addDetailedControlsDiv(number, control);
    container.appendChild(table);
    overlay.appendChild(container);
    var appendTo = document.getElementById('maindisplay');
    appendTo.appendChild(overlay);

    document.body.appendChild(overlay);
}

function restore() {
    document.body.removeChild(document.getElementById('overlay'));
}

function downloadFile(screensection) {

    // wrong path if copied to /home/directory
    // TODO: check file and path

   var url = document.URL;
   var url_path = url.split("/");

   var file = document.getElementById("file" + screensection).innerHTML;
   var file = document.getElementById("file" + screensection).getAttribute("title");

   // Download with download.php
   var download = url_path[0]+"/"+url_path[1]+"/"+url_path[2]+"/"+url_path[3]+"/download.php?file="+encodeURIComponent(file);

   var name = "Download";

   if(file.indexOf("www.") > -1) {
        window.open("http://"+file, name);
   } else {
        window.open(download, name);
    }
}

function is_valid_url(url)
{
     return url.match(/^(ht|f)tps?:\/\/[a-z0-9-\.]+\.[a-z]{2,4}\/?([^\s<>\#%"\,\{\}\\|\\\^\[\]`]+)?$/);
}

function urlToNuc() {

    var url = document.getElementById('url_field').value;
    //~ alert(url);

    if (is_valid_url(url)) {
        sendToNuc('openURL='+url);
    } else {
        var urlfield = document.getElementById('url_field');
        urlfield.setAttribute('value', '<?=_("Enter valid URL")?>');
    }

    setTimeout(function(){location.reload()}, 1000);
}

function addControls(number, control, is_overlay) {
  // Add the basic controls up, down, left, right.
  var tr;
  var td;
  var button;

  if (typeof control == "undefined") {
    control = ["default", false, false, false, false,
               false, false, false, false, false, false, false];
  }

  // get handler
  var handler = control[0];

  var up = control[1];
  var down = control[2];
  var left = control[3];
  var right = control[4];

  // Show the overlay button only if extended controls are supported
  // (zoomin, zoomout, home, end, prior, next, download).
  var disableOverlayButton = is_overlay ||
    !(control[5] || control[6] || control[7] || control[8] ||
      control[9] || control[10] || control[11]);

  var div = document.createElement('div');
  var table = document.createElement('table');
  table.setAttribute('class', 'control');
  tr = document.createElement('tr');
  tr.appendChild(document.createElement('td'));
  // TODO: try fa-arrow-up, fa-carret-up, fa-long-arrow-up, fa-angle-up, fa-play
  tr.appendChild(keyControl(number, 'fa-play fa-rotate-270', 'up',
                            handler, !up, '<?=_("Cursor control")?>'));
  tr.appendChild(document.createElement('td'));
  table.appendChild(tr);
  tr = document.createElement('tr');
  tr.appendChild(keyControl(number, 'fa-play fa-rotate-180', 'left',
                            handler, !left, '<?=_("Cursor control")?>'));
  td = document.createElement('td');
  button = document.createElement('button');
  button.appendChild(document.createTextNode(number));
  button.setAttribute('class', 'pure-button pure-button-primary pure-input-rounded');
  if (disableOverlayButton) {
      button.setAttribute('disabled', '');
  } else {
      button.setAttribute('id', 'more');
      button.setAttribute('onclick', 'remoteControl(' + number + ', ' + JSON.stringify(control) + ')');
      button.setAttribute('title', '<?=_("Advanced control")?>');
  }
  td.appendChild(button);
  tr.appendChild(td);
  tr.appendChild(keyControl(number, 'fa-play', 'right',
                            handler, !right, '<?=_("Cursor control")?>'));
  table.appendChild(tr);
  tr = document.createElement('tr');
  tr.appendChild(document.createElement('td'));
  tr.appendChild(keyControl(number, 'fa-play fa-rotate-90', 'down',
                            handler, !down, '<?=_("Cursor control")?>'));
  tr.appendChild(document.createElement('td'));
  table.appendChild(tr);
  div.appendChild(table);

  return div;
}

function addSimpleControlsTd(number, controls) {
    var td = document.createElement('td');
    td.appendChild(addControls(number, controls[number], false));
    td.setAttribute('id', 'section' + number);
    return td;
}

function addDetailedControlsDiv(number, control) {

    var handler = control[0];
    // up down left right zoomin zoomout home end prior next download
    var up = control[1];
    var down = control[2];
    var left = control[3];
    var right = control[4];
    var zoomin = control[5];
    var zoomout = control[6];
    var home = control[7];
    var end = control[8];
    var prior = control[9];
    var next = control[10];
    var download = control[11];

    var controlpanel;
    var updown, zoom, bar;

    var table, tr, td;

    controlpanel = document.createElement('div');
    controlpanel.setAttribute('class', 'controlpanel');

    updown = addControls(number, control, true);
    updown.setAttribute('class', 'updown');

    controlpanel.appendChild(updown);

    zoom = document.createElement('div');
    zoom.setAttribute('class', 'zoom');
    table = document.createElement('table');

    tr = document.createElement('tr');
    td = keyControl(number, 'fa-search-plus', 'zoomin',
                    handler, !zoomin, '<?=_("Zoom in")?>');
    tr.appendChild(td);
    table.appendChild(tr);

    tr = document.createElement('tr');
    td = keyControl(number, 'fa-search-minus', 'zoomout',
                    handler, !zoomout, '<?=_("Zoom out")?>');
    tr.appendChild(td);
    table.appendChild(tr);

    zoom.appendChild(table);

    controlpanel.appendChild(zoom);

    bar = document.createElement('div');
    bar.setAttribute('class', 'bar');
    table = document.createElement('table');

    tr = document.createElement('tr');

    td = keyControl(number, 'fa-step-backward', 'home',
                    handler, !home, "<?=_('Jump to start')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-backward', 'prior',
                    handler, !prior, "<?=_('Page up')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-forward', 'next',
                    handler, !next, "<?=_('Page down')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-step-forward', 'end',
                    handler, !end, "<?=_('Jump to end')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-download', 'download',
                    handler, !download, '<?=_("Download this file")?>');
    td.setAttribute('onclick', 'downloadFile(' + number + ')');
    tr.appendChild(td);

    table.appendChild(tr);

    bar.appendChild(table);

    controlpanel.appendChild(bar);

    return controlpanel;
}

function showLayout(layout, controls) {

    //~ console.log("Layout: " + layout);
    //~ for (i = 0; i < controls.length; i++) {
    //~     console.log("SL " + i + ": " + controls[i]);
    //~ }

    document.onkeydown = function(evt) {
        evt = evt || window.event;
        var section = focus_section;
        var handler = controls[focus_section][0];
        var keyHandler;
        //~ console.log("Key down: " + evt.keyCode);
        switch (evt.keyCode) {
        case 33: // page up
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'prior'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 34: // page down
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'next'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 35: // end
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'end'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 36: // home
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'home'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 37: // left
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'left'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 38: // up
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'up'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 39: // right
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'right'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 40: // down
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'down'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        }
    };

    document.onkeypress = function(evt) {
        evt = evt || window.event;
        //~ console.log("Key press: " + evt.keyCode);
        var charCode = evt.which || evt.keyCode;
        var charStr = String.fromCharCode(charCode);
        var section = focus_section;
        var handler = controls[focus_section][0];
        var keyHandler;
        switch (charStr) {
        case "1": // select section 1
        case "2": // select section 2
        case "3": // select section 3
        case "4": // select section 4
            focus_section = charStr;
            break;
        case "+": // zoom in
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'zoomin'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case "-": // zoom out
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'zoomout'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        }
    };

  var md = document.getElementById('maindisplay');
  var tr;
  var td;
  while (md.firstChild) {
    md.removeChild(md.firstChild);
  }
  switch (layout) {
  case 'g1x1':
    // Show only one segment (full screen).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    md.appendChild(tr);
    break;
  case 'g2x1':
    // Show two segments (side by side).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    break;
  case 'g1x2':
    // Show two segments (one on top of the other).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    md.appendChild(tr);
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    break;
  case 'g1a2':
    // Show three segments (one on the left, two on the right).
    tr = document.createElement('tr');
    td = addSimpleControlsTd(1, controls);
    td.setAttribute('rowspan', '2');
    tr.appendChild(td);
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(3, controls));
    md.appendChild(tr);
    break;
  case 'g2x2':
    // Show four segments (one in each quadrant).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(3, controls));
    td = document.createElement('td');
    tr.appendChild(addSimpleControlsTd(4, controls));
    md.appendChild(tr);
    break;
  default:
    // No supported screen layout selected. This should never happen.
    tr = document.createElement('tr');
    td = document.createElement('td');
    td.appendChild(document.createTextNode('Bitte Bildschirmaufteilung auswählen!'));
    tr.appendChild(td);
    md.appendChild(tr);
    break;
  }
}

function miniDisplaySelect(element) {
    sendToNuc('layout=' + element.id);
}

function getHandlerCommand(handle, task) {

    // console.log("getHandlerCommand "+handle+" - "+task);
    // to deactivate buttons just add 'undefined' as keystroke

    var handler = [];

    handler["default"] = {};
    // handler["default"]["init"] = "";
    handler["default"]["up"] = "Up";
    handler["default"]["down"] = "Down";
    handler["default"]["left"] = "Left";
    handler["default"]["right"] = "Right";
    handler["default"]["next"] = "Next";
    handler["default"]["prior"] = "Prior";
    handler["default"]["home"] = "Home";
    handler["default"]["end"] = "End";
    handler["default"]["zoomin"] = "ctrl+plus";
    handler["default"]["zoomout"] = "ctrl+minus";
    handler["default"]["download"] = "download";

    // Handler for web pages.
    handler["dwb"] = {};
    handler["dwb"]["up"] = " Up";
    handler["dwb"]["down"] = "Down";
    handler["dwb"]["left"] = "Left";
    handler["dwb"]["right"] = "Right";
    handler["dwb"]["next"] = "Next";
    handler["dwb"]["prior"] = "Prior";
    handler["dwb"]["home"] = "Home";
    handler["dwb"]["end"] = "End";
    handler["dwb"]["zoomin"] = "plus";
    handler["dwb"]["zoomout"] = "minus";
    handler["dwb"]["download"] = "download";

    // Handler for images.
    handler["eog"] = {};
    handler["eog"]["up"] = "alt+Up";
    handler["eog"]["down"] = "alt+Down";
    handler["eog"]["left"] = "alt+Left";
    handler["eog"]["right"] = "alt+Right";
    handler["eog"]["next"] = "alt+Next";
    handler["eog"]["prior"] = "alt+Prior";
    handler["eog"]["home"] = "undefined";
    handler["eog"]["end"] = "undefined";
    handler["eog"]["zoomin"] = "ctrl+plus";
    handler["eog"]["zoomout"] = "ctrl+minus";
    handler["eog"]["download"] = "download";

    // Controls in LibreOffice: no zoom in calc and writer, has to be activated first
    // by pressing <Ctrl+Shift+o> (switch view mode on/off) not implemented yet
    handler["libreoffice"] = {};
    handler["libreoffice"]["up"] = "Up";
    handler["libreoffice"]["down"] = "Down";
    handler["libreoffice"]["left"] = "Left";
    handler["libreoffice"]["right"] = "Right";
    handler["libreoffice"]["next"] = "Next";
    handler["libreoffice"]["prior"] = "Prior";
    handler["libreoffice"]["home"] = "undefined";
    handler["libreoffice"]["end"] = "undefined";
    handler["libreoffice"]["zoomin"] = "undefined";
    handler["libreoffice"]["zoomout"] = "undefined";
    handler["libreoffice"]["download"] = "download";

    // Handler for MS Excel and LibreOffice Calc documents.
    handler["libreoffice-calc"] = {};
    handler["libreoffice-calc"]["up"] = "Up";
    handler["libreoffice-calc"]["down"] = "Down";
    handler["libreoffice-calc"]["left"] = "Left";
    handler["libreoffice-calc"]["right"] = "Right";
    handler["libreoffice-calc"]["next"] = "Next";
    handler["libreoffice-calc"]["prior"] = "Prior";
    handler["libreoffice-calc"]["home"] = "Home";
    handler["libreoffice-calc"]["end"] = "End";
    handler["libreoffice-calc"]["zoomin"] = "undefined";
    handler["libreoffice-calc"]["zoomout"] = "undefined";
    handler["libreoffice-calc"]["download"] = "download";

    // Handler for MS Powerpoint and LibreOffice Impress documents.
    handler["libreoffice-impress"] = {};
    handler["libreoffice-impress"]["up"] = "Up";
    handler["libreoffice-impress"]["down"] = "Down";
    handler["libreoffice-impress"]["left"] = "Left";
    handler["libreoffice-impress"]["right"] = "Right";
    handler["libreoffice-impress"]["next"] = "Next";
    handler["libreoffice-impress"]["prior"] = "Prior";
    handler["libreoffice-impress"]["home"] = "Home";
    handler["libreoffice-impress"]["end"] = "End";
    handler["libreoffice-impress"]["zoomin"] = "plus";
    handler["libreoffice-impress"]["zoomout"] = "minus";
    handler["libreoffice-impress"]["download"] = "download";

    // Handler for MS Word and LibreOffice Writer documents.
    handler["libreoffice-writer"] = {};
    handler["libreoffice-writer"]["up"] = "Up";
    handler["libreoffice-writer"]["down"] = "Down";
    handler["libreoffice-writer"]["left"] = "Left";
    handler["libreoffice-writer"]["right"] = "Right";
    handler["libreoffice-writer"]["next"] = "Next";
    handler["libreoffice-writer"]["prior"] = "Prior";
    handler["libreoffice-writer"]["home"] = "undefined";
    handler["libreoffice-writer"]["end"] = "undefined";
    handler["libreoffice-writer"]["zoomin"] = "undefined";
    handler["libreoffice-writer"]["zoomout"] = "undefined";
    handler["libreoffice-writer"]["download"] = "download";

    // Handler for videos.
    handler["vlc"] = {};
    handler["vlc"]["up"] = "undefined";
    handler["vlc"]["down"] = "undefined";
    handler["vlc"]["left"] = "undefined";
    handler["vlc"]["right"] = "space";
    handler["vlc"]["next"] = "undefined";
    handler["vlc"]["prior"] = "undefined";
    handler["vlc"]["home"] = "undefined";
    handler["vlc"]["end"] = "undefined";
    handler["vlc"]["zoomin"] = "undefined";
    handler["vlc"]["zoomout"] = "undefined";
    handler["vlc"]["download"] = "undefined";

    // Handler for shared desktops (VNC).
    handler["vnc"] = {};
    handler["vnc"]["up"] = "Up";
    handler["vnc"]["down"] = "Down";
    handler["vnc"]["left"] = "Left";
    handler["vnc"]["right"] = "Right";
    handler["vnc"]["next"] = "undefined";
    handler["vnc"]["prior"] = "undefined";
    handler["vnc"]["home"] = "undefined";
    handler["vnc"]["end"] = "undefined";
    handler["vnc"]["zoomin"] = "plus";
    handler["vnc"]["zoomout"] = "minus";
    handler["vnc"]["download"] = "undefined";

    // Handler for PDF documents.
    handler["zathura"] = {};
    handler["zathura"]["up"] = "Up";
    handler["zathura"]["down"] = "Down";
    handler["zathura"]["left"] = "Left";
    handler["zathura"]["right"] = "Right";
    handler["zathura"]["next"] = "Next";
    handler["zathura"]["prior"] = "Prior";
    handler["zathura"]["home"] = "Home";
    handler["zathura"]["end"] = "End";
    handler["zathura"]["zoomin"] = "plus";
    handler["zathura"]["zoomout"] = "minus";
    handler["zathura"]["download"] = "download";

    var send_keys = handler["default"]["up"];

    if (typeof(handler[handle]) !== "undefined") {
        send_keys = handler[handle][task];
    }

    // console.log(send_keys);

    return send_keys;
}

Dropzone.options.palmaDropzone = {
    init: function() {
      this.on("complete", function() {
        if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
          // File finished uploading, and there aren't any left in the queue.
          // console.log("File(s) uploaded");
          setTimeout(function() {
            location.reload()
          }, 1);
          // location.reload(); // verlangt Eingabe von Enter zum wiederholten Schicken der Daten
        }
      });
    }
};

function showHelp(visible) {
    // Hide or show an overlay window with help text and some extra functions.
    var element = document.getElementById('helpwindow');
    if (visible) {
        element.style.visibility = "visible";
    } else {
        element.style.visibility = "hidden";
    }
}

function updateUserList(address, user) {
    // Update the user list on screen from the table in the database.

    // Get the <tbody> element which contains the user entries.
    var list = document.getElementById('userlist');

    // First we remove all existing <tr> elements.
    while (list.firstChild) {
        list.removeChild(list.firstChild);
    }

    if (address.length > 0) {
        // Add an entry for each user. Iterate over addresses:
        // One user may be connected several times with different devices.
        // We don't expect more than one user from the same device.
        var m;
        for (m = 0; m < address.length; m++) {
            var n;
            for (n = 0; n < user.length; n++) {
                if (address[m].userid == user[n].userid) {
                    break;
                }
            }
            var device = address[m].device;
            var tr = document.createElement('tr');
            var td = document.createElement('td');
            var i = document.createElement('i');
            i.setAttribute('class', 'fa fa-fw fa-' + device);
            td.appendChild(i);
            td.appendChild(document.createTextNode(user[n].name));
            tr.appendChild(td);
            list.appendChild(tr);
        }
    } else {
<?php
    if ($user) {
?>
        // All users were disconnected.
        alert("<?=_('You were disconnected!')?>");
        window.location = 'logout.php';
<?php
    } else {
?>
        // If there is no user, we display an empty entry.
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.appendChild(document.createTextNode("\u00a0"));
        tr.appendChild(td);
        list.appendChild(tr);
<?php
    }
?>
    }
}

function updateWindowList(window) {
    // Update the window list on screen from the table in the database.

    // Get the <tbody> element which contains the window entries.
    var list = document.getElementById('windowlist');

    // First we remove all existing <tr> elements.
    while (list.firstChild) {
        list.removeChild(list.firstChild);
    }

    var tr;
    var td;

    if (window.length == 0) {
        // If there is no window, we display an empty entry.
        tr = document.createElement('tr');
        td = document.createElement('td');
        td.appendChild(document.createTextNode("\u00a0"));
        tr.appendChild(td);
        list.appendChild(tr);
    } else {
        // Add an entry for each window.
        var n;
        for (n = 0; n < window.length; n++) {
            var screensection = window[n].section;
            var file = window[n].file;
            var handler = window[n].handler;
            var s0 = false;
            var s1 = false;
            var s2 = null;
            var s3 = null;
            var s4 = null;
            var status = window[n].state;
            switch (screensection) {
                case 1:
                    s1 = 'selected';
                    break;
                case 2:
                    s2 = 'selected';
                    break;
                case 3:
                    s3 = 'selected';
                    break;
                case 4:
                    s4 = 'selected';
                    break;
                default:
                    s0 = 'selected';
                    status = 'inactive';
                    break;
            }
            var tr = document.createElement('tr');
            var td;
            var i;
            var div;
            var option;
            td = document.createElement('td');
            td.setAttribute('id', 'itemstatus');
            i = document.createElement('i');
            if (status == 'active') {
                i.setAttribute('class', 'fa fa-eye');
            } else {
                i.setAttribute('class', 'fa fa-ban');
            }
            i.setAttribute('id', 'status_' + screensection);
            i.setAttribute('title', '<?=_("Toggle visibility")?>');
            i.setAttribute('onclick',
                "sendToNuc('window=" + screensection + "&toggle=TRUE')");
            td.appendChild(i);
            tr.appendChild(td);
            var select = document.createElement('select');
            select.setAttribute('onchange', "sendToNuc('switchWindows=TRUE&before=" + screensection + "&after='+(this.value))");
            select.setAttribute('name', 'window');
            select.setAttribute('title', '<?=_("Select screen section for display")?>');
            option = document.createElement('option');
            option.setAttribute('value', '0');
            if (s0) {
                option.setAttribute(s0, '');
            }
            option.appendChild(document.createTextNode('-'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '1');
            if (s1) {
                option.setAttribute(s1, '');
            }
            option.appendChild(document.createTextNode('1'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '2');
            if (s2) {
                option.setAttribute(s2, '');
            }
            option.appendChild(document.createTextNode('2'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '3');
            if (s3) {
                option.setAttribute(s3, '');
            }
            option.appendChild(document.createTextNode('3'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '4');
            if (s4) {
                option.setAttribute(s4, '');
            }
            option.appendChild(document.createTextNode('4'));
            select.appendChild(option);
            td.appendChild(select);
            tr.appendChild(td);
            td = document.createElement('td');
            div = document.createElement('div');
            div.setAttribute('id', 'file' + screensection);
            // display only the last part of the URL or file name.
            // Long names are truncated, and the truncation is indicated.
            var fname = file;
            if (fname.substring(0, 4) == 'http') {
                // Remove a terminating slash from an URL.
                // The full URL will be shown as a tooltip.
                fname = fname.replace(/\/$/, '');
                fname = fname.replace(/^.*\//, '');
                div.setAttribute('title', file);
            } else {
                // For files only the full base name is shown as a tooltip.
                fname = fname.replace(/^.*\//, '');
                div.setAttribute('title', fname);
            }
            if (fname.length > 18) {
                fname = fname.substring(0, 15) + '...';
            }
            div.appendChild(document.createTextNode(fname));
            td.appendChild(div);
            tr.appendChild(td);
            td = document.createElement('td');
            td.setAttribute('id', 'itemtrash');
            i = document.createElement('i');
            i.setAttribute('class', 'fa fa-trash-o');
            i.setAttribute('onclick', "sendToNuc('window=" + screensection + "&delete=" + file + "')");
            i.setAttribute('title', '<?=_("Remove this object")?>');
            td.appendChild(i);
            tr.appendChild(td);
            list.appendChild(tr);
        }
    }
}

function updateControlsBySection(window) {

    // get section and handler for each window
    var sectionControls = [];

    for (n = 0; n < window.length; n++) {
        var win_id = window[n].win_id;
        var section = window[n].section;
        var file = window[n].file;
        var handler = window[n].handler;

        // alert("Section: " + section + " - Handler: " + handler);

        if (handler.indexOf("eog") > -1) {
            // up down left right zoomin zoomout home end prior next download
            control = ["eog", true, true, true, true, true, true, false, false, true, true, true];
        } else if (handler.indexOf("libreoffice") > -1) {
            // Controls in LibreOffice: no zoom in calc and writer, has to be activated first
            // by pressing <Ctrl+Shift+o> (switch view mode on/off) not implemented yet
            control = ["libreoffice", true, true, true, true, false, false, false, false, true, true, true];
                if (handler.indexOf("--calc") > -1) {
                    control = ["libreoffice-calc", true, true, true, true, false, false, true, true, true, true, true];
                }
                if (handler.indexOf("--impress") > -1) {
                    control = ["libreoffice-impress", true, true, true, true, true, true, true, true, true, true, true];
                }
                if (handler.indexOf("--writer") > -1) {
                    control = ["libreoffice-writer", true, true, true, true, false, false, false, false, true, true, true];
                }
        } else if (handler.indexOf("dwb") > -1) {
            control = ["dwb", true, true, true, true, true, true, true, true, true, true, true];
        } else if (handler.indexOf("vlc") > -1) {
            control = ["vlc", false, false, false, true, false, false, false, false, false, false, false];
        } else if (handler.indexOf("vnc") > -1) {
            control = ["vnc", true, true, true, true, true, true, false, false, false, false, false];
        } else if (handler.indexOf("zathura") > -1) {
            control = ["zathura", true, true, true, true, true, true, true, true, true, true, true];
        } else {
            control = ["undefined", false, false, false, false, false, false, false, false, false, false, false];
        }

        sectionControls[section] = control;
    }

    // Fill empty sections with default values.
    for (i = sectionControls.length; i < 5; i++) {
        sectionControls[i] = ["default",
                              false, false, false, false,
                              false, false, false, false,
                              false, false, false];
    }

    return sectionControls;
}

function updateScreen() {
    sendToNuc('window=all&closeOrphans=true');
}

function clearURLField(defaultText) {
    var browseto = document.getElementById('url_field');
    browseto.setAttribute('value', 'http://');
}

// lastJSON is used to reduce the database polling rate.
var lastJSON = '';

function pollDatabase() {
    var xmlHttp = new XMLHttpRequest();
    if (xmlHttp) {
        xmlHttp.open("get", 'db.php?json=' + lastJSON, true);
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 2) {
                // Data transferred to server.
            } else if (xmlHttp.readyState == 3) {
                // Server is answering.
            } else if (xmlHttp.readyState == 4) {
                // Received all data from server.
                var status = xmlHttp.status;
                if (status == 200) {
                    // Got valid response.
                    var text = xmlHttp.responseText;
                    lastJSON = text;
                    var db = JSON.parse(text);
                    var i;
                    var layout = '';
                    for (i = 0; i < db.setting.length; i++) {
                        if (db.setting[i].key == 'layout') {
                            layout = db.setting[i].value;
                            break;
                        }
                    }
                    var controls = updateControlsBySection(db.window);
                    // for (i = 0; i < controls.length; i++) {
                    //    console.log(i + ": " + controls[i]);
                    //    }
                    showLayout(layout, controls);
                    updateUserList(db.address, db.user);
                    updateWindowList(db.window);
                    // updateScreen(db.window);
                    setTimeout("pollDatabase()", 1);
                } else {
                    // Got error. TODO: handle it.
                }
            } else {
                // Got unexpected xmlHttp.readyState. TODO: handle it.
            }
        };
        xmlHttp.send(null);
    }
}

// Start polling the database.
pollDatabase();

</script>

</head>

<body id="workbench">

<div id="workbench_right">
    <?php
      # Show authorized user name (and address) and allow logout.
      if ($user) {
        echo("<p><a href=\"logout.php\" title=\"" .
            _('Disconnect the current user') .
            "\">$user<i class=\"fa fa-sign-out\"></i></a></p>");
      }
    ?>

    <table class="userlist" summary="<?=_('User list')?>" title="<?=_('List of connected users')?>">
        <caption><?=_('User list')?><i class="fa fa-users"></i></caption>
        <tbody id="userlist">
            <tr><td><!-- filled by function updateUserList() --></td></tr>
        </tbody>
    </table>
    <button class="pure-button pure-button-primary pure-input-rounded"
            onClick="sendToNuc('logout=ALL')"
            title="<?=_('Disconnect all users and reset the work place')?>">
        <?=_('Disconnect all users')?>
    </button>

        <table class="itemlist" summary="<?=_('Display list')?>"
               title="<?=_('List of files, URLs and shared displays')?>">
            <caption><?=_('Display list')?><i class="fa fa-desktop"></i></caption>
            <tbody id="windowlist">
                <tr><td><!-- filled by function updateWindowList() --></td></tr>
            </tbody>
        </table>
    <button class="pure-button pure-button-primary pure-input-rounded"
            onClick="sendToNuc('closeAll=TRUE')"
            title="<?=_('Close all windows and remove uploaded files')?>">
        <?=_('Close all windows')?>
    </button>
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="showHelp(true)"
        title="<?=_('Show help and offer some extras')?>">
        <?=_('Help + Extras')?>
        <i class="fa fa-question-circle"></i>
    </button>

</div> <!-- workbench_right -->

<div id="workbench_left">
    <table class="maindisplay" summary="<?=_('Team display')?>">
        <caption>
            <img id="palma_logo"
                 src="theme/<?=CONFIG_THEME?>/palma-logo-49x18.png"
                 alt="PalMA"> <?=_('Team display')?>
        <?php
              if (isset($_SESSION['monitor'])) {
                  echo('(' . $_SESSION['monitor'] . ')');
              }
        ?>
        </caption>
        <tbody id='maindisplay'>
            <tr><td><!-- filled by function showLayout() --></td></tr>
        </tbody>
    </table>
    <table class="minidisplaylist" summary="<?=_('Screen layout')?>">
        <caption><?=_('Screen layout')?></caption>
        <tr>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g1x1" onclick="miniDisplaySelect(this)"
                    title="<?=_('Choose screen layout')?>">
              <table><tr><td>1</td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g1x2" onclick="miniDisplaySelect(this)"
                    title="<?=_('Choose screen layout')?>">
              <table><tr><td>1</td></tr><tr><td>2</td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g2x1" onclick="miniDisplaySelect(this)"
                    title="<?=_('Choose screen layout')?>">
              <table><tr><td>1</td><td>2</td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g1a2" onclick="miniDisplaySelect(this)"
                    title="<?=_('Choose screen layout')?>">
              <table><tr><td rowspan="2">1</td><td>2</td></tr><tr><td>3</td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g2x2" onclick="miniDisplaySelect(this)"
                    title="<?=_('Choose screen layout')?>">
              <table><tr><td>1</td><td>2</td></tr><tr><td>3</td><td>4</td></tr></table>
            </button>
          </div></td>
        </tr>
    </table>
    <table>
  <tr>
  <td>
    <input type="text" value="<?=_('Enter URL')?>"
           id="url_field" maxlength="256" size="46"
           onkeydown="if (event.keyCode == 13) document.getElementById('url_button').click()"
           onfocus="clearURLField('<?=_('Enter URL')?>')">
    <button class="pure-button pure-button-primary pure-input-rounded"
            id="url_button"
            onClick="urlToNuc()" title="<?=_('Show this URL in a new browser window')?>">
         URL
            <i class="fa fa-folder-open"></i>
     </button>
  </tr>
  <tr>
    <td>
      <form action="upload.php"
            class="dropzone"
            id="palma-dropzone"
            title="<?=_('Drop documents here (or click) to load them up')?>">
          <div class="dz-default dz-message">
              <i class="fa fa-upload fa-1x"></i>
              <div>
                  <?=_('Drop documents here (or click)')?>
              </div>
          </div>
      </form>
<!--
      <div class="dz-preview dz-file-preview">
        <div class="dz-details">
          <div class="dz-filename"><span data-dz-name></span></div>
          <div class="dz-size" data-dz-size></div>
          <img data-dz-thumbnail src="" alt="">
        </div>
        <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
        <div class="dz-success-mark"><span>?</span></div>
        <div class="dz-error-mark"><span>?</span></div>
        <div class="dz-error-message"><span data-dz-errormessage></span></div>
      </div>
-->
    </td>
  </tr>
  </table>
</div> <!-- workbench_left -->
<div id="workbench_end"></div>

<div id="helpwindow">
    <div id="helpcontainer">
        <div id="helpclose" onclick="showHelp(false)">
            <i class="fa fa-times" title="<?=_('Close the help window')?>"></i>
        </div>
        <h3>
            <?=_('Help + Extras')?>
        </h3>
        <div>
            <p><?=_('With PalMA, you can share your documents and your desktop
            with your learning group.')?></p>
            <p><?=_('Team members can join the group at any time. All they need
            is URL and PIN.')?>
            URL: <?=$_SESSION['starturl']?>,
            PIN: <?=$_SESSION['pin']?>.</p>
            <p><?=_('The PalMA team monitor shows up to 4 contributions
            simultaneously.')?></p>
            <p><?=_('Just upload PDF files, images, OpenDocument or
            MS Office files or enter a URL &ndash; PalMA will show them.')?></p>
            <p><?=_('Share the desktop of your notebook with others. PalMA uses
            VNC for screen sharing. Download the VNC software once for your
            <a href="http://www.bib.uni-mannheim.de/fileadmin/pdf/ub/LearningCenter/palma-kurzanleitung.pdf"
            onclick="window.open(this.href); return false;">Windows PC</a>
            (preconfigured UltraVNC) or
            <a href="http://www.bib.uni-mannheim.de/fileadmin/pdf/ub/LearningCenter/palma-anleitung-mac.pdf"
            onclick="window.open(this.href); return false;">MacBook</a>
            (RealVNC).')?></p>
            <p><?=_('Linux users can share their X display like this:')?><br>
            <code>x11vnc -connect <?=$_SERVER['HTTP_HOST']?></code></p>
        </div>
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('<?=$winvnc?>'); return false;">
        <?=_('Share desktop (free UltraVNC / Windows only)')?>
        <i class="fa fa-download"></i>
    </button>
    <br>
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('http://realvnc.com/download/vnc/'); return false;">
        <?=_('Share desktop (non free RealVNC)')?>
        <i class="fa fa-download"></i>
    </button>

<!--
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('vncviewer.php'); return false;">
        <?=_('Show team display')?>
    </button>
-->
    <!-- TODO test code, remove for production. -->
<!--
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('phpinfo.php'); return false;">
        Test
    </button>
-->
    </div>
</div>

</body> <!-- workbench -->

</html>
