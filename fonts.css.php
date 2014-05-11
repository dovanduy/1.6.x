<?php
include_once('ressources/class.templates.inc');

$sock=new sockets();
$font_family=$sock->GET_INFO("InterfaceFonts");
if($font_family==null){$font_family="'Lucida Grande',Arial, Helvetica, sans-serif";}

header("Content-type: text/css");

$Green="#005447";
$ButtonOver="#057D6A";
$ButtonGradientStart="#047F6C";
$StrongGreen="#044036";
echo "
body{
	font-family:$font_family;
}



h3{
	font-size:14px;
}



.form-horizontal .control-label {
    float: none;
    width: auto;
    padding-top: 0;
    text-align: left;
}
.form-horizontal .controls {
    margin-left: 0;
}
.form-horizontal .control-list {
    padding-top: 0;
}
.form-horizontal .form-actions {
    padding-right: 10px;
    padding-left: 10px;
}

.controls select,
input[type=\"file\"] {
  height: 30px;
  *margin-top: 4px;
  line-height: 30px;	
	
	
}
.controls select{
  background-color: #ffffff;
  border: 1px solid #cccccc;	
	
}

.controls > .radio:first-child,
.controls > .checkbox:first-child {
  padding-top: 5px;
}

.controls > .radio:first-child,
.controls > .checkbox:first-child {
  padding-top: 5px;
}
.form-horizontal .control-group:after {
  clear: both;
}

.form-horizontal .control-label {
  float: left;
  width: 240px;
  padding-top: 5px;
  text-align: right;
  font-size:14px;
}

.form-horizontal .controls {
  *display: inline-block;
  *padding-left: 20px;
  margin-left: 250px;
  *margin-left: 0;
}

.form-horizontal .controls:first-child {
  *padding-left: 180px;
}

.form-horizontal button, input, select, textarea {
  margin: 0;
  font-size: 100%;
  vertical-align: middle;
}

.form-horizontal button,input {
  *overflow: visible;
  line-height: normal;
}

.form-horizontal label,select,button,input[type=\"button\"],input[type=\"reset\"],input[type=\"submit\"], input[type=\"radio\"], input[type=\"checkbox\"] {
  cursor: pointer;
}

.form-horizontal input, textarea, .uneditable-input {
    width: 250px;
}
.form-horizontal textarea {
    height: auto;
}
.form-horizontal input[type=\"checkbox\"], input[type=\"radio\"] {
    border: 1px solid #ccc;
  }


.form-horizontal textarea, input[type=\"text\"], input[type=\"password\"], input[type=\"datetime\"], input[type=\"datetime-local\"], input[type=\"date\"], input[type=\"month\"], input[type=\"time\"], input[type=\"week\"], input[type=\"number\"], input[type=\"email\"], input[type=\"url\"], input[type=\"search\"], input[type=\"tel\"], input[type=\"color\"], .uneditable-input {
    background-color: #FFFFFF;
    border: 1px solid #CCCCCC;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
    transition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;
}
.form-horizontal textarea:focus, input[type=\"text\"]:focus, input[type=\"password\"]:focus, input[type=\"datetime\"]:focus, input[type=\"datetime-local\"]:focus, input[type=\"date\"]:focus, input[type=\"month\"]:focus, input[type=\"time\"]:focus, input[type=\"week\"]:focus, input[type=\"number\"]:focus, input[type=\"email\"]:focus, input[type=\"url\"]:focus, input[type=\"search\"]:focus, input[type=\"tel\"]:focus, input[type=\"color\"]:focus, .uneditable-input:focus {
    border-color: rgba(82, 168, 236, 0.8);
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6);
    outline: 0 none;
}

.form-horizontal textarea {
  overflow: auto;
  vertical-align: top;
}

.form-horizontal h1,h2,h3,h4,h5,h6 {
  margin: 10px 0;
  font-family: inherit;
  font-weight: bold;
  line-height: 20px;
  color: inherit;
  text-rendering: optimizelegibility;
}

.form-horizontal h1,h2,h3,h4,h5,h6 :first-letter{
  text-transform:capitalize;
}

.form-horizontal legend {
  display: block;
  width: 100%;
  padding: 0;
  margin-bottom: 20px;
  font-size: 21px;
  line-height: 40px;
  color: #333333;
  border: 0;
  border-bottom: 1px solid #e5e5e5;
}

.form-horizontal label,input,button,select,textarea {
  font-size: 14px;
  font-weight: normal;
  line-height: 20px;
}

.form-horizontal input,button,select,textarea {
  font-family: $font_family;
}

label {
  display: block;
  margin-bottom: 5px;
}

.form-horizontal select,textarea,
input[type=\"text\"],
input[type=\"password\"],
input[type=\"datetime\"],
input[type=\"datetime-local\"],
input[type=\"date\"],
input[type=\"month\"],
input[type=\"time\"],
input[type=\"week\"],
input[type=\"number\"],
input[type=\"email\"],
input[type=\"url\"],
input[type=\"search\"],
input[type=\"tel\"],
input[type=\"color\"],
.uneditable-input {
  display: inline-block;
  height: auto;
  padding: 4px 6px;
  margin-bottom: 10px;
  font-size: 14px;
  line-height: 20px;
  color: #555555;
  vertical-align: middle;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
/* behavior:url(/css/border-radius.htc); */
}

.form-horizontal textarea,
input[type=\"text\"],
input[type=\"password\"],
input[type=\"datetime\"],
input[type=\"datetime-local\"],
input[type=\"date\"],
input[type=\"month\"],
input[type=\"time\"],
input[type=\"week\"],
input[type=\"number\"],
input[type=\"email\"],
input[type=\"url\"],
input[type=\"search\"],
input[type=\"tel\"],
input[type=\"color\"],
.uneditable-input {
  background-color: #ffffff;
  border: 1px solid #cccccc;
  -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
     -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
          box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  -webkit-transition: border linear 0.2s, box-shadow linear 0.2s;
     -moz-transition: border linear 0.2s, box-shadow linear 0.2s;
       -o-transition: border linear 0.2s, box-shadow linear 0.2s;
          transition: border linear 0.2s, box-shadow linear 0.2s;
}

.form-horizontal textarea:focus,
input[type=\"text\"]:focus,
input[type=\"password\"]:focus,
input[type=\"datetime\"]:focus,
input[type=\"datetime-local\"]:focus,
input[type=\"date\"]:focus,
input[type=\"month\"]:focus,
input[type=\"time\"]:focus,
input[type=\"week\"]:focus,
input[type=\"number\"]:focus,
input[type=\"email\"]:focus,
input[type=\"url\"]:focus,
input[type=\"search\"]:focus,
input[type=\"tel\"]:focus,
input[type=\"color\"]:focus,
.uneditable-input:focus {
  border-color: rgba(82, 168, 236, 0.8);
  outline: 0;
  outline: thin dotted \9;
  /* IE6-9 */

  -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(82, 168, 236, 0.6);
     -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(82, 168, 236, 0.6);
          box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(82, 168, 236, 0.6);
}
.form-horizontal h3{
	font-size:18px;
}


.form-horizontal input[type=\"radio\"],
input[type=\"checkbox\"] {
  margin: 4px 0 0;
  margin-top: 1px \9;
  *margin-top: 0;
  line-height: normal;
}

.form-horizontal input[type=\"file\"],
input[type=\"image\"],
input[type=\"submit\"],
input[type=\"reset\"],
input[type=\"button\"],
input[type=\"radio\"],
input[type=\"checkbox\"] {
  width: auto;
}

.form-horizontal select,input[type=\"file\"] {
  height: 30px;
  /* In IE7, the height of the select element cannot be changed by height, only font-size */

  *margin-top: 4px;
  /* For IE7, add top margin to align select with labels */

  line-height: 30px;
}

.controls select,
input[type=\"file\"] {
  height: 30px;
  *margin-top: 4px;
  line-height: 30px;	
	
	
}
.controls select{
  background-color: #ffffff;
  border: 1px solid #cccccc;	
	
}

.form-horizontal select:focus,input[type=\"file\"]:focus,input[type=\"radio\"]:focus, input[type=\"checkbox\"]:focus {
  outline: thin dotted #333;
  outline: 5px auto -webkit-focus-ring-color;
  outline-offset: -2px;
}

.uneditable-input,
.uneditable-textarea {
  color: #999999;
  cursor: not-allowed;
  background-color: #fcfcfc;
  border-color: #cccccc;
  -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.025);
     -moz-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.025);
          box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.025);
}
input[disabled],select[disabled],textarea[disabled],input[readonly],select[readonly],
textarea[readonly] {
  cursor: not-allowed;
  background-color: #eeeeee;
}

.form-horizontal input[type=\"radio\"][disabled],input[type=\"checkbox\"][disabled],input[type=\"radio\"][readonly],
input[type=\"checkbox\"][readonly] {
  background-color: transparent;
}

.sDiv2 > select {
    margin-top: -10px;
    font-size:12px !important;
}

.sDiv2 > input[type=\"text\"] {
font-size:12px !important;
}
.tooltip {
  position: absolute;
  z-index: 1030;
  display: block;
  font-size: 11px;
  line-height: 1.1;
  opacity: 0;
  filter: alpha(opacity=0);
  visibility: visible;
}

.tooltip.in {
  opacity: 0.8;
  filter: alpha(opacity=80);
}

.tooltip.top {
  padding: 5px 0;
  margin-top: -3px;
}

.tooltip.right {
  padding: 0 5px;
  margin-left: 3px;
}

.tooltip.bottom {
  padding: 5px 0;
  margin-top: 3px;
}

.tooltip.left {
  padding: 0 5px;
  margin-left: -3px;
}

.tooltip-inner {
  max-width: 500px;
  padding: 8px;
  color: #ffffff;
  text-align: left;
  text-decoration: none;
  background-color: #000000;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
/* behavior:url(/css/border-radius.htc); */
}

.tooltip-arrow {
  position: absolute;
  width: 0;
  height: 0;
  border-color: transparent;
  border-style: solid;
}

.tooltip.top .tooltip-arrow {
  bottom: 0;
  left: 50%;
  margin-left: -5px;
  border-top-color: #000000;
  border-width: 5px 5px 0;
}

.tooltip.right .tooltip-arrow {
  top: 50%;
  left: 0;
  margin-top: -5px;
  border-right-color: #000000;
  border-width: 5px 5px 5px 0;
}

.tooltip.left .tooltip-arrow {
  top: 50%;
  right: 0;
  margin-top: -5px;
  border-left-color: #000000;
  border-width: 5px 0 5px 5px;
}

.tooltip.bottom .tooltip-arrow {
  top: 0;
  left: 50%;
  margin-left: -5px;
  border-bottom-color: #000000;
  border-width: 0 5px 5px;
}

.popover {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1010;
  display: none;
  max-width: 276px;
  padding: 1px;
  text-align: left;
  white-space: normal;
  background-color: #ffffff;
  border: 1px solid #ccc;
  border: 1px solid rgba(0, 0, 0, 0.2);
  -webkit-border-radius: 6px;
     -moz-border-radius: 6px;
          border-radius: 6px;
  -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
     -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
          box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  -webkit-background-clip: padding-box;
     -moz-background-clip: padding;
          background-clip: padding-box;
/* behavior:url(/css/border-radius.htc); */
}

.popover.top {
  margin-top: -10px;
}

.popover.right {
  margin-left: 10px;
}

.popover.bottom {
  margin-top: 10px;
}

.popover.left {
  margin-left: -10px;
}

.popover-title {
  padding: 8px 14px;
  margin: 0;
  font-size: 14px;
  font-weight: normal;
  line-height: 18px;
  background-color: #f7f7f7;
  border-bottom: 1px solid #ebebeb;
  -webkit-border-radius: 5px 5px 0 0;
     -moz-border-radius: 5px 5px 0 0;
          border-radius: 5px 5px 0 0;
/* behavior:url(/css/border-radius.htc); */
}

.popover-title:empty {
  display: none;
}

.popover-content {
  padding: 9px 14px;
}

.popover .arrow,
.popover .arrow:after {
  position: absolute;
  display: block;
  width: 0;
  height: 0;
  border-color: transparent;
  border-style: solid;
}

.popover .arrow {
  border-width: 11px;
}

.popover .arrow:after {
  border-width: 10px;
  content: \"\";
}

.popover.top .arrow {
  bottom: -11px;
  left: 50%;
  margin-left: -11px;
  border-top-color: #999;
  border-top-color: rgba(0, 0, 0, 0.25);
  border-bottom-width: 0;
}

.popover.top .arrow:after {
  bottom: 1px;
  margin-left: -10px;
  border-top-color: #ffffff;
  border-bottom-width: 0;
}

.popover.right .arrow {
  top: 50%;
  left: -11px;
  margin-top: -11px;
  border-right-color: #999;
  border-right-color: rgba(0, 0, 0, 0.25);
  border-left-width: 0;
}

.popover.right .arrow:after {
  bottom: -10px;
  left: 1px;
  border-right-color: #ffffff;
  border-left-width: 0;
}

.popover.bottom .arrow {
  top: -11px;
  left: 50%;
  margin-left: -11px;
  border-bottom-color: #999;
  border-bottom-color: rgba(0, 0, 0, 0.25);
  border-top-width: 0;
}

.popover.bottom .arrow:after {
  top: 1px;
  margin-left: -10px;
  border-bottom-color: #ffffff;
  border-top-width: 0;
}

.popover.left .arrow {
  top: 50%;
  right: -11px;
  margin-top: -11px;
  border-left-color: #999;
  border-left-color: rgba(0, 0, 0, 0.25);
  border-right-width: 0;
}

.popover.left .arrow:after {
  right: 1px;
  bottom: -10px;
  border-left-color: #ffffff;
  border-right-width: 0;
}

.btn.disabled,
.btn[disabled] {
  cursor: default;
  background-image: none;
  opacity: 0.65;
  filter: alpha(opacity=65);
  -webkit-box-shadow: none;
     -moz-box-shadow: none;
          box-shadow: none;
}

.btn-large {
  padding: 11px 19px;
  font-size: 17.5px;
  -webkit-border-radius: 6px;
     -moz-border-radius: 6px;
          border-radius: 6px;
}

.btn-large [class^=\"icon-\"],
.btn-large [class*=\" icon-\"] {
  margin-top: 4px;
}

button.btn.btn-large,
input[type=\"submit\"].btn.btn-large {
  *padding-top: 7px;
  *padding-bottom: 7px;
}

button.btn.btn-large,
input[type=\"submit\"].btn.btn-large {
  *padding-top: 7px;
  *padding-bottom: 7px;
}

.btn-primary.active,
.btn-warning.active,
.btn-danger.active,
.btn-success.active,
.btn-info.active,
.btn-inverse.active {
  color: rgba(255, 255, 255, 0.75);
}

.btn-primary {
  color: #ffffff;
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  background-color: $Green;
  *background-color: $ButtonOver;
  background-image: -moz-linear-gradient(top, $ButtonGradientStart, $ButtonOver);
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from($ButtonGradientStart), to($ButtonOver));
  background-image: -webkit-linear-gradient(top, $ButtonGradientStart, $ButtonOver);
  background-image: -o-linear-gradient(top, $ButtonGradientStart, $ButtonOver);
  background-image: linear-gradient(to bottom, $ButtonGradientStart, $ButtonOver);
  background-repeat: repeat-x;
  border-color: $ButtonOver $ButtonOver $StrongGreen;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='$ButtonGradientStart', endColorstr='$ButtonOver', GradientType=0);
  filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
}

.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active,
.btn-primary.active,
.btn-primary.disabled,
.btn-primary[disabled] {
  color: #ffffff;
  background-color: $ButtonOver;
  *background-color: $Green;
}

.btn-primary:active,
.btn-primary.active {
  background-color: $StrongGreen \9;
}

.blockUI.blockOverlay {
    background-color: 005447;
    opacity: 0.6;
}

.blockUI.blockMsg.blockPage {
height:290px;
-webkit-border-radius: 5px 5px 0 0;
-moz-border-radius: 5px 5px 0 0;
border-radius: 5px 5px 0 0;
opacity: 0.6;
/* behavior:url(/css/border-radius.htc); */	
}

.blockUI h1 {
    font-size: 55px;
    background:none;
    padding-top:95px;
    background-image: none;
}
  
.Button2014-lg {
    border-radius: 6px 6px 6px 6px;
    font-size: 18px;
    line-height: 1.33;
    padding: 10px 16px;
}
.Button2014-success {
    background-color: #5CB85C;
    border-color: #4CAE4C;
    color: #FFFFFF;
}
.Button2014 {
    -moz-user-select: none;
    border: 1px solid transparent;
    border-radius: 4px 4px 4px 4px;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857;
    margin-bottom: 0;
    padding: 6px 22px;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

a.Button2014, a.Button2014:link, a.Button2014:visited, a.Button2014:hover{
	color: #FFFFFF;
	text-decoration:none;	
}

.Button2014-success {
    background-color: #5CB85C !important;
    border-color: #4CAE4C !important;
    color: #FFFFFF !important;
}
.Button2014-success:hover, .Button2014-success:focus, .Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
    background-color: #47A447 !important;
    border-color: #398439 !important;
    color: #FFFFFF !important;
}
.Button2014-success:active, .Button2014-success.active, .open .dropdown-toggle.Button2014-success {
    background-image: none;
}
.Button2014-success.disabled, .Button2014-success[disabled], fieldset[disabled] .Button2014-success, .Button2014-success.disabled:hover, .Button2014-success[disabled]:hover, fieldset[disabled] .Button2014-success:hover, .Button2014-success.disabled:focus, .Button2014-success[disabled]:focus, fieldset[disabled] .Button2014-success:focus, .Button2014-success.disabled:active, .Button2014-success[disabled]:active, fieldset[disabled] .Button2014-success:active, .Button2014-success.disabled.active, .Button2014-success.active[disabled], fieldset[disabled] .Button2014-success.active {
    background-color: #5CB85C !important;
    border-color: #4CAE4C !important;
}

.field {
    clear: both;
    margin-bottom: 10px;
    text-align: right;
}


";