<?php
// OneFileCMS - github.com/Self-Evident/OneFileCMS

$version = '3.1.9';

/*******************************************************************************
Copyright © 2009-2012 https://github.com/rocktronica
Copyright © 2012-     https://github.com/Self-Evident  David W. Gay

This software is copyright under terms of the "MIT" license:

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*******************************************************************************/




// CONFIGURABLE INFO ***********************************************************
$USERNAME = 'username';

$PASSWORD = 'password'; //If using $HASHWORD, you may leave this value empty.
$USE_HASH = 0 ; // If = 0, use $PASSWORD. If = 1, use $HASHWORD.
$HASHWORD = 'ff20c771cd8b39d848aa3bb631e880ece7682f98164d5446699cee1b6486fdb3'; //default hash for "password"

$config_title     = "OneFileCMS";

$MAX_IMG_W   = 810;   // Max width to display images. (page container = 810)
$MAX_IMG_H   = 1000;  // Max height.  I don't know, it just looks reasonable.

$MAX_EDIT_SIZE = 150000;  // Edit gets flaky with large files in some browsers.  Trial and error your's.
$MAX_VIEW_SIZE = 1000000; // If file > $MAX_EDIT_SIZE, don't even view in OneFileCMS.
                          // The max view size is completely arbitrary. It was 2am and seemed like a good idea at the time.

$config_style_sheet = ""; // If empty use embed styles, elsif use custom CSS file (exemple : style.css)
$config_favicon   = "/favicon.ico";
$config_excluded  = ""; //files to exclude from directory listings- CaSe sEnsaTive!

$config_etypes = "html,htm,xhtml,php,css,js,txt,text,cfg,conf,ini,csv,svg"; //Editable file types.
$config_itypes = "jpg,jpeg,gif,png,bmp,ico"; //image types to display on edit page.
$config_ftypes = "bin,jpg,jpeg,gif,png,bmp,ico,svg,txt,cvs,css,php,ini,cfg,conf,asp,js ,htm,html"; // _ftype & _fclass must have same
$config_fclass = "bin,img,img,img,img,img,img,svg,txt,txt,css,php,txt,cfg,cfg ,txt,txt,htm,htm";  // number of values. bin is default.

$EX = '<b>( ! )</b>'; //"EXclaimation point" icon Used in $message's
// END CONFIGURABLE INFO *******************************************************




//******************************************************************************
//Some global system values

if (!defined('PHP_VERSION_ID')) {            //PHP_VERSION_ID only available since 5.2.7
    $phpversion = explode('.', PHP_VERSION); //PHP_VERSION, however, should be available even in older versions.
    define('PHP_VERSION_ID', ($phpversion[0] * 10000 + $phpversion[1] * 100 + $phpversion[2]));
}

$ONESCRIPT = URLencode_path($_SERVER["SCRIPT_NAME"]);
$DOC_ROOT  = $_SERVER["DOCUMENT_ROOT"].'/';
$WEB_ROOT  = URLencode_path(basename($DOC_ROOT)).'/';
$WEBSITE   = $_SERVER["HTTP_HOST"] . URLencode_path(dirname($_SERVER['PHP_SELF'])) . '/';

$valid_pages = array("hash", "login","logout","index","edit","upload","uploaded","newfile","copy","rename","delete","newfolder","renamefolder","deletefolder" );

$INVALID_CHARS = '< > ? * : " | / \\'; //Illegal characters for file/folder names. (Space deliminated)
$INVALID_CHARS_array = explode(' ', $INVALID_CHARS);

//Make arrays out of a few $config_variables for actual use later.
//Also, remove spaces and make lowercase.
$etypes   = explode(',', strtolower(str_replace(' ', '', $config_etypes))); //editable file types
$itypes   = explode(',', strtolower(str_replace(' ', '', $config_itypes))); //images types to display
$ftypes   = explode(',', strtolower(str_replace(' ', '', $config_ftypes))); //file types with icons
$fclasses = explode(',', strtolower(str_replace(' ', '', $config_fclass))); //for file types with icons
$excluded_list = (explode(",", $config_excluded));
//******************************************************************************




function Session_Startup() {//**************************************************
	global $USERNAME, $PASSWORD, $HASHWORD, $USE_HASH, $SALT, $message , $page, $VALID_POST;

	session_start();

	undo_magic_quotes();

	if ($USE_HASH){ $PASS = $HASHWORD; }else{ $PASS = $PASSWORD; }

	if ( isset($_POST["username"]) || isset($_POST["password"]) ) {
		$_SESSION['username'] = $_POST["username"];

		if ($USE_HASH) { $_SESSION['password'] = hashit($_POST["password"]); }
		else           { $_SESSION['password'] =        $_POST["password"];  }

		if (($_SESSION['username'] != $USERNAME) || ($_SESSION['password'] != $PASS))
			{ $message .= $EX.' <b>INVALID LOGIN ATTEMPT</b>'; }
	}

	if (($_SESSION['username'] == $USERNAME) && ( $_SESSION['password'] == $PASS ))
		 { $_SESSION['valid'] = "1"; $page = "index"; }
	else { $_SESSION['valid'] = "0"; $page = "login"; unset($_GET["p"]); session_destroy() ;}

	$VALID_POST = ($_SESSION['valid'] == "1" && $_POST["sessionid"] == session_id());

	chdir($_SERVER["DOCUMENT_ROOT"]); //Allow OneFileCMS.php to be started from any dir on the site.
}//End Session_Startup() *******************************************************




function hashit($key){ //*******************************************************
	//This is the super-secret stuff - don't tell anyone!!
	//If you change anything here, redo the hash for your password.
	$hash = hash('sha256', trim($key).$salt); // trim off leading & trailing spaces.
	$salt = 'somerandomesalt';
	for ( $x=0; $x < 1000; $x++ ) { $hash = hash('sha256', $hash.$salt); }
	return $hash;
}//end hashit() ****************************************************************




function undo_magic_quotes(){ //************************************************

	function strip_array($var) {
		if (is_array($var)) {return array_map("strip_array", $var); }
		else                {return stripslashes($var); }
	} //Note: stripslashes also handles cases when magic_quotes_sybase is on.

	if (get_magic_quotes_gpc()) {
		$_GET     = strip_array($_GET);
		$_POST    = strip_array($_POST);
		$_COOKIE  = strip_array($_COOKIE);
	}
}//end undo_magic_quotes() *****************************************************




function Get_GET() { //*** Get main parameters *********************************
	// i=some/path/,  f=somefile.xyz,  p=somepage
	global $ipath, $filename, $page, $param1, $param2, $param3, $message, $EX;

	if (isset($_GET["i"])) { $ipath = Check_path($_GET["i"]); }else{ $ipath = ""; }

	if (isset($_GET["f"])) {
		$filename = $ipath.$_GET["f"];
		if ( !is_file($filename) && $_SESSION['valid'] )//Don't set $message for login page.
			{ $message .= $EX.' <b>File does not exist:</b> '.htmlentities($filename).'<br>'; }
		if ( !is_file($filename) ) { $filename = ""; $page = "index"; }
	}else{ $filename = ""; }

	if (isset($_GET["p"])) { $page = $_GET["p"]; } // default $page set in session startup

	$param1 = '?i='.URLencode_path($ipath);
	if ($filename == "") { $param2 = ""; }else{ $param2 = '&amp;f='.rawurlencode(basename($filename)); }
	if ($page == ""    ) { $param3 = ""; }else{ $param3 = '&amp;p='.$page; }
}//end Get_GET()****************************************************************




function URLencode_path($path){ // don't encode the forward slashes ************
	$TS = '';  // Trailing Slash/
	if (substr($path, -1) == '/' ) { $TS = '/'; } //start with a $TS?
	$path_array = explode('/',$path);
	$path = "";
	foreach ($path_array as $level) { $path .= rawurlencode($level).'/'; }
	$path = rtrim($path,'/').$TS;  //end with $TS only if started with one
	return $path;
}//end URLencode_path($path) ***************************************************




function Check_path($path) { // returns first valid path in some/supplied/path/
	global $message, $EX;
	$invalidpath = $path; //used for message if supplied $path doesn't exist.
	$path = str_replace('\\','/',$path);   //Make sure all forward slashes.
	$path = trim($path,"/ ."); // trim slashes, dots, and spaces

	//Remove any '.' and '..' parts of the path.  Causes issues in <h2>www \ current \ path \</h2>
	$pathparts = explode( '/', $path);
	$len       = count($pathparts);
	$path      = "";  //Cleaned path.
	foreach ($pathparts as $value) { //(More reliable than str_replace(entire_string).)
		if ( !(($value == '.') && (!value == '..')) ) { $path .= $value.'/'; }
	}

	$path = trim($path,"/"); // Remove -for now- final trailing slash.

	if (strlen($path) < 1) { return ""; } //If at site root
	else {
		if (!is_dir($path) && (strlen($message) < 1))
			{ $message .= $EX.' <b>Directory does not exist: '.htmlentities($invalidpath).'</b><br>'; }

		while ( (strlen($path) > 0) && (!is_dir($path)) ) {
			$path = dirname($path);
		}

		$path = $path.'/';
		if ($path == './') { $path = ""; } // ./ means path not found, so clear for root.
	}

	return $path;
}//end Check_path() ************************************************************




function is_empty($path){ //****************************************************
	$empty = false;
	$dh = opendir($path);
	for($i = 3; $i; $i--) { $empty = (readdir($dh) === FALSE); }
	closedir($dh);
	return $empty;
}//end is_emtpy() //************************************************************




function ordinalize($destination,$filename, &$msg) { //*************************
//if file_exists(file.txt), ordinalize filename until it doesn't
//ie: file.txt.001,  file.txt.002, file.txt.003  etc...
	global $EX;

	$ordinal   = 0;
	$savefile = $destination.$filename;

	if (file_exists($savefile)) {

		$msg .= $EX.' A file with that name already exists in the target directory.<br>';

		while (file_exists($savefile)) {
			$ordinal = sprintf("%03d", ++$ordinal); //  001, 002, 003, etc...
			$savefile = $destination.$filename.'.'.$ordinal;
		}
		$msg .= 'Saving as: "<b>'.htmlentities(basename($savefile)).'</b>"';
	}
	return $savefile;
}//end ordinalize() filename ***************************************************




function Current_Path_Header(){ //**********************************************
 	// Current path. ie: webroot/current/path/
	// Each level is a link to that level.

	global $ONESCRIPT, $ipath, $WEB_ROOT;

	echo '<h2>';
		//Root folder of web site.
		echo '<a href="'.$ONESCRIPT.'" class="path"> '.htmlentities(trim($WEB_ROOT, '/')).'</a>/';

		if ($ipath != "" ) { //if not at root, show the rest
			$path_levels  = explode("/",trim($ipath,'/') );
			$levels = count($path_levels); //If levels=3, indexes = 0, 1, 2  etc...
			$current_path = "";

			for ($x=0; $x < $levels; $x++) {
				$current_path .= $path_levels[$x].'/';
				echo '<a href="'.$ONESCRIPT.'?i='.URLencode_path($current_path).'" class="path">';
				echo htmlentities($path_levels[$x]).'</a>/';
			}
		}//end if (not at root)
	echo '</h2>';
}//end Current_Path_Header() //*************************************************




function message_box() { //*****************************************************
	global $ONESCRIPT, $param1, $param2, $param3, $message, $page;

	if (isset($message)) {
?>
		<div id="message"><p>
		<span id="Xbox"><!-- [X] to dismiss message box -->
			<a id="dismiss" href='<?php echo $ONESCRIPT.$param1.$param2.$param3; ?>'
 			onclick='document.getElementById("message").innerHTML = " ";return false;'>
			[X]</a>
		</span>
		<?php echo $message.PHP_EOL ;?>
		</p></div>
		<script>document.getElementById("dismiss").focus();</script>
<?php
	}else {
		echo '<div id="message"></div>'; // Needed on Edit page to keep js feedback from failing
	} //end isset($message)

	// Used on Edit Page to preserve vertical spacing, so edit area doesn't jump as much.
	if ($page == "edit") {echo '<style>#message { min-height: 1.86em; }</style>';}
}//end message_box()  **********************************************************




function Upload_New_Rename_Delete_Links() { //**********************************
	global $ONESCRIPT, $ipath, $param1;
	echo '<p class="front_links">';
	echo '<a href="'.$ONESCRIPT.$param1.'&amp;p=upload">'   ; svg_icon_upload()    ; echo 'Upload File</a>';
	echo '<a href="'.$ONESCRIPT.$param1.'&amp;p=newfile">'  ; svg_icon_file_new()  ; echo 'New File</a>'   ;
	echo '<a href="'.$ONESCRIPT.$param1.'&amp;p=newfolder">'; svg_icon_folder_new(); echo 'New Folder</a>' ;
	if ($ipath !== "") { //if at root, don't show Rename & Delete links
		echo '<a href="'.$ONESCRIPT.$param1.'&amp;p=renamefolder">'; svg_icon_folder_ren();echo 'Rename/Move Folder</a>';
		echo '<a href="'.$ONESCRIPT.$param1.'&amp;p=deletefolder">'; svg_icon_folder_del();   echo 'Delete Folder</a>';
	}
	echo '</p>';
}//end Upload_New_Rename_Delete_Links()  ***************************************














function Cancel_Submit_Buttons($submit_label, $focus) { //**********************
	//$submit_label = Rename, Copy, Delete, etc...
	//$focus is ID of element to receive focus(). (element may be outside this function)
	global $ONESCRIPT, $ipath, $param1, $param2, $filename, $page;

	// [Cancel] returns to either the index, or edit page.
	if ($filename == "") {$params = "";}else{ $params .= $param2.'&amp;p=edit'; }
?>
	<p></p>
	<input type="button" class="button" id="cancel" name="cancel" value="Cancel"
		onclick="parent.location = '<?php echo $ONESCRIPT.$param1.$params ?>'">
	<input type="submit" class="button" value="<?php echo $submit_label;?>" style="margin-left: 1.3em;">

<?php
	if ($focus != ""){ echo '<script>document.getElementById("'.$focus.'").focus();</script>'; }

}// End Cancel_Submit_Buttons() //**********************************************




function show_image(){ //*******************************************************
	global $filename, $MAX_IMG_W, $MAX_IMG_H;

	$IMG = $filename;
	$img_info = getimagesize($IMG);

	$W=0; $H=1;
	$SCALE = 1; $TOOWIDE = 0; $TOOHIGH = 0;
	if ($img_info[$W] > $MAX_IMG_W) { $TOOWIDE = ( $MAX_IMG_W/$img_info[$W] );}
	if ($img_info[$H] > $MAX_IMG_H) { $TOOHIGH = ( $MAX_IMG_H/$img_info[$H] );}

	if ($TOOHIGH || $TOOWIDE) {
		if     (!$TOOWIDE)           {$SCALE = $TOOHIGH;}
		elseif (!$TOOHIGH)           {$SCALE = $TOOWIDE;}
		elseif ($TOOHIGH > $TOOWIDE) {$SCALE = $TOOWIDE;} //ex:if (.90 > .50)
		else                         {$SCALE = $TOOHIGH;}
	}

	echo '<p class="file_meta">';
	echo 'Image shown at ~'. round($SCALE*100) .'% of full size.<br>('.$img_info[3].')</p>';
	echo '<div style="clear:both;"></div>'.PHP_EOL;
	echo '<a href="/' .URLencode_path($IMG). '" target="_blank">'.PHP_EOL;
	echo '<img src="/'.URLencode_path($IMG).'"  height="'.$img_info[$H]*$SCALE.'"></a>'.PHP_EOL;
}// end show_image() ***********************************************************




function show_favicon(){ //*****************************************************
	global $config_favicon, $DOC_ROOT;
	if (file_exists($DOC_ROOT.$config_favicon)) {
		echo '<img src="'.URLencode_path($config_favicon).'" alt="">';
	}
}// end show_favicon() *********************************************************




function Init_Macros(){ //*** ($varibale="some reusable chunk of code")*********

global 	$ONESCRIPT, $param1, $param2, $INPUT_SESSIONID, $FORM_COMMON,
		$SVG_icon_circle_plus, $SVG_icon_circle_x, $SVG_icon_pencil, $SVG_icon_img_0;

$INPUT_SESSIONID = '<input type="hidden" name="sessionid" value="'.session_id().'">'.PHP_EOL;
$FORM_COMMON = '<form method="post" action="'.$ONESCRIPT.$param1.$param2.'">'.$INPUT_SESSIONID;

$SVG_icon_circle_plus = '<circle cx="5" cy="5" r="5" stroke="black" stroke-width="0" fill="#080"/>
	  <line x1="2" y1="5" x2="8" y2="5" stroke="white" stroke-width="1.5" />
	  <line x1="5" y1="2" x2="5" y2="8" stroke="white" stroke-width="1.5" />';

$SVG_icon_circle_x = '<circle cx="5" cy="5" r="5" stroke="black" stroke-width="0" fill="#D00"/>
	<line x1="2.5" y1="2.5" x2="7.5" y2="7.5" stroke="white" stroke-width="1.5"/>
	<line x1="7.5" y1="2.5" x2="2.5" y2="7.5" stroke="white" stroke-width="1.5"/>';

$SVG_icon_pencil = '<polygon points="2,0 9,7 7,9 0,2" stroke-width="1" stroke="darkgoldenrod" fill="rgb(246,222,100)"/>
	  <path  d="M0,2   L0,0  L2,0"      stroke="tan" fill="tan" stroke-width="1" />
	  <path  d="M0,1.5   L0,0  L1.5,0"      stroke="black" fill="transparent" stroke-width="1" />
	  <line x1="7.3" y1="10"  x2="10" y2="7.3"  stroke="silver" stroke-width="1"/>
	  <line x1="8.1" y1="10.8"  x2="10.8" y2="8.1"  stroke="red" stroke-width="1"/>';

$SVG_icon_img_0 = '<rect x="0"    y="0"   width="14" height="16" fill="#FF8" stroke="#44F" stroke-width="2" />
	<rect x="2"    y="2"   width="5"  height="5"  fill="#F66" stroke-width="0" />
	<rect x="7.5"  y="6"   width="5"  height="5"  fill="#6F6" stroke-width="0" />
	<rect x="2"    y="10"  width="5"  height="5"  fill="#66F" stroke-width="0" />';
}//end Init_Macros() ***********************************************************




function svg_icon_bin(){ //*****************************************************
$zero = '<rect x="0"  y="0"  width="3" height="6" fill="transparent" stroke="#555" stroke-width="1" />';
$one  = '<line x1="0" y1="-.5"   x2="0" y2="6.5"  stroke="#555" stroke-width="1"/>';
?><svg class="icon" xmlns="http://www.w3.org/2000/svg" version="1.1" width="14" height="16">
	<g transform="translate( 0.5,0.5)"><?php echo $one  ?></g>
	<g transform="translate( 3.5,0.5)"><?php echo $zero ?></g>
	<g transform="translate( 9.5,0.5)"><?php echo $one  ?></g>
	<g transform="translate(12.5,0.5)"><?php echo $one  ?></g>
	<g transform="translate( 0.5,9.5)"><?php echo $zero ?></g>
	<g transform="translate( 6.5,9.5)"><?php echo $one  ?></g>
	<g transform="translate( 9.5,9.5)"><?php echo $zero ?></g>
</svg><?php
} //end svg_icon_bin() *********************************************************



function svg_icon_img(){ //*****************************************************
global $SVG_icon_img_0;
?><svg class="icon icon_file" xmlns="http://www.w3.org/2000/svg" version="1.1" width="14" height="16">
	<?php echo $SVG_icon_img_0 ?>
</svg><?php
} //end svg_icon_img() *********************************************************



function svg_icon_svg(){ //*****************************************************
global $SVG_icon_img_0;
?><svg class="icon icon_file" xmlns="http://www.w3.org/2000/svg" version="1.1" width="14" height="16">
	<?php echo $SVG_icon_img_0 ?>
	<line x1="3" y1="3.5"  x2="11" y2="3.5"  stroke="#444" stroke-width=".6"/>
	<line x1="3" y1="6.5"  x2="11" y2="6.5"  stroke="#444" stroke-width=".6"/>
	<line x1="3" y1="9.5"  x2="11" y2="9.5"  stroke="#444" stroke-width=".6"/>
	<line x1="3" y1="12.5" x2="11" y2="12.5" stroke="#444" stroke-width=".6"/>
</svg><?php
} //end svg_icon_img() *********************************************************



function svg_icon_txt_0($border, $lines, $fill, $extra){ //*********************
?><svg class="icon icon_file" xmlns="http://www.w3.org/2000/svg" version="1.1" width="14" height="16">
	<rect x = "0" y = "0" width = "14" height = "16"
		fill="<?php echo $fill ?>" stroke="<?php echo $border ?>" stroke-width="2" />
	<line x1="3" y1="3.5"  x2="11" y2="3.5"  stroke="<?php echo $lines ?>" stroke-width=".6"/>
	<line x1="3" y1="6.5"  x2="11" y2="6.5"  stroke="<?php echo $lines ?>" stroke-width=".6"/>
	<line x1="3" y1="9.5"  x2="11" y2="9.5"  stroke="<?php echo $lines ?>" stroke-width=".6"/>
	<line x1="3" y1="12.5" x2="11" y2="12.5" stroke="<?php echo $lines ?>" stroke-width=".6"/>
	<?php echo $extra ?>
</svg><?php
} //end svg_icon_txt() *********************************************************



function svg_icon_txt(){ svg_icon_txt_0('#333', '#000', '#FFF', ''); } //*******

function svg_icon_htm(){ svg_icon_txt_0('#444', '#222', '#FABEAA', ''); } //**** rgb(250,190,170)

function svg_icon_php(){ svg_icon_txt_0('#333', '#111', '#C3C3FF', ''); } //**** rgb(195,195,225)

function svg_icon_css(){ svg_icon_txt_0('#333', '#111', '#FFE1A5', ''); } //**** rgb(255,225,165)

function svg_icon_cfg(){ svg_icon_txt_0('#444', '#111', '#DDD', ''); } //*******



function svg_icon_upload(){ //**************************************************
	$extra = '<g transform="scale(1.1) translate(1.75,4)">
		<polygon points="6,0  12,6  8,6  8,11  4,11  4,6  0,6"
		stroke-width="1" stroke="white" fill="green" /></g>';
	svg_icon_txt_0('#333', 'black', 'white', $extra);
} //end svg_icon_upload() ******************************************************



function svg_icon_file_new(){ //************************************************
	global $SVG_icon_circle_plus;
	$extra = '<g transform="translate(4,6)">'.$SVG_icon_circle_plus.'</g>';
	svg_icon_txt_0('#444', 'black', 'white', $extra);
} //end svg_icon_file_new() ****************************************************



function svg_icon_file_del(){ //************************************************
global $SVG_icon_circle_x;
	$extra = '<g transform="translate(4,6)">'.$SVG_icon_circle_x.'</g>';
	svg_icon_txt_0('#444', 'black', 'white', $extra);
} //end svg_icon_file_del() ****************************************************



function svg_icon_folder_0($extra){ //******************************************
?><svg class="icon icon_fldr" xmlns="http://www.w3.org/2000/svg" version="1.1" width="18" height="14">
	<path  d="M0.5, 1  L8,1  L9,2  L9,3  L16.5,3  L17,3.5  L17,13.5  L.5,13.5  L.5,.5"
			fill="#FBE47b" stroke="#F0CD28" stroke-width="1" />
	<path  d="M1.5, 8  L7, 8  L8.5,6.3  L16,6.3  L7.5, 6.3   L6.5,7.5  L1.5,7.5"
			fill="transparent" stroke="white" stroke-width="1" />
	<path  d="M1.5,13  L1.5,2  L7.5,2  L8.5,3  L8.5,4  L15.5,4 L16,4.5  L16,13"
			fill="transparent" stroke="white" stroke-width="1" />
	<?php echo $extra ?>
</svg><?php
} //end svg_icon_folder_0() ****************************************************



function svg_icon_folder(){ svg_icon_folder_0(''); } //*************************



function svg_icon_folder_new(){ //**********************************************
	global $SVG_icon_circle_plus;
	$extra = '<g transform="translate(7.5,4)">'.$SVG_icon_circle_plus.'</g>';
	svg_icon_folder_0($extra);
} //end svg_icon_folder_new() **************************************************



function svg_icon_folder_ren(){ //**********************************************
	global $SVG_icon_pencil;
	$extra = '<g transform="translate(6,3)">'.$SVG_icon_pencil.'</g>';
	svg_icon_folder_0($extra);
} //end svg_icon_folder_ren() **************************************************



function svg_icon_folder_del(){ //**********************************************
	global $SVG_icon_circle_x;
	$extra = '<g transform="translate(7.5,4)">'.$SVG_icon_circle_x.'</g>';
	svg_icon_folder_0($extra);
} //end svg_icon_folder_del() **************************************************




function show_icon($type){ //***************************************************
	if     ($type == 'bin') { svg_icon_bin(); }
	elseif ($type == 'img') { svg_icon_img(); }
	elseif ($type == 'svg') { svg_icon_svg(); }
	elseif ($type == 'txt') { svg_icon_txt(); }
	elseif ($type == 'htm') { svg_icon_htm(); }
	elseif ($type == 'php') { svg_icon_php(); }
	elseif ($type == 'css') { svg_icon_css(); }
	elseif ($type == 'cfg') { svg_icon_cfg(); }
	else                    { svg_icon_bin(); } //default
}//end show_icon() *************************************************************




function Hash_Page() { //******************************************************
	global $DOC_ROOT, $ONESCRIPT, $param1, $message, $INPUT_SESSIONID, $config_title;
	$params = '?i='.dirname($ONESCRIPT).'&amp;f='.basename($ONESCRIPT).'&amp;p=edit';
?>
	<style>#message {font-family: courier; min-height: 3.1em;}
	li {margin-left: 2em}</style>

	<h2>Generate a Password Hash</h2>

	<form id="hash" name="hash" method="post" action="<?php echo $ONESCRIPT.$param1.'&amp;p=hash'; ?>">
		<?php echo $INPUT_SESSIONID; ?>
		Password to hash:
		<input type="text" name="whattohash" id="whattohash" value="<?php echo htmlspecialchars($_POST["whattohash"]) ?>">
		<?php Cancel_Submit_Buttons('Generate hash', 'whattohash') ?>
 		<a class="button edit_onefile" href="<?php echo $ONESCRIPT.$params; ?>" >Edit &nbsp;<?php echo $config_title ?></a>
	</form>

	<div class="info">
 	<p>There are two ways to change your OneFileCMS password:<br>
	<p>
	1) Simply use the $PASSWORD config variable to store your desired password, and set $USE_HASH = 0 (zero).<br>
	2) Or, use $HASHWORD to store the hash of your password, and set $USE_HASH = 1.<br>

	<p>Keep in mind that due to a number of widely varied considerations, this is largely an academic excersize.<br>
	In other words, take the idea that this adds much of an improvement to security with a grain of cryptographic salt...*<br>
	<p>Anyway, to use the $HASHWORD password option:
	<ol><li>Type your desired password in the input field above and hit Enter.<br>
			The hash will be displayed in a yellow message box above that.
		<li>Copy and paste the new hash to the $HASHWORD variable in the config section.<br>
			'Make sure the hash ends up in quotes.'<br>
			Make sure to copy ALL of, and ONLY, the hash (no spaces etc). A double-click should select it...
		<li>Make sure $USE_HASH is set to 1 (or true).
		<li>When ready, logout and login.
	</ol>
	<p>You can use OneFileCMS to edit itself.  However, be sure to have a backup ready for the inevitable tupo...
	<p>*Note: While still largely academic, you can improve security a bit more by changing the default salt and/or method used by OneFileCMS to hash the password (and keep 'em secret, of course).<br>
	PS: Everything I know about security - you just read...
	</div>
<?php
} //end Hash_Page() ************************************************************




function Hash_Page_response() { //**********************************************
	global $SALT, $message;
	$_POST['whattohash'] = trim($_POST['whattohash']); // trim leading & trailing spaces.
	$message .= 'Password: '.$_POST['whattohash'].'<br>';
	$message .= 'Hash &nbsp; &nbsp;: '.hashit($_POST["whattohash"]);
} //end Hash_Page_response() ***************************************************




function Login_Page() { //******************************************************
	global $ONESCRIPT, $message;
?>
	<h2>Log In</h2>
	<form method="post" action="<?php echo $ONESCRIPT; ?>">
		<p>
			<label for="username">Username:</label>
			<input type="text" name="username" id="username" class="login_input" >
		</p>
		<p>
			<label for="password">Password:</label>
			<input type="password" name="password" id="password" class="login_input">
		</p>

		<input type="submit" class="button" value="Enter">
	</form>
	<script>document.getElementById('username').focus();</script>
<?php
} //end Login_Page() ***********************************************************




function list_files() { // ...in a vertical table ******************************
//called from Index Page

	global $ONESCRIPT, $ipath, $param1, $ftypes, $fclasses, $excluded_list;

	$files = scandir('./'.$ipath);
	natcasesort($files);

	echo '<table class="index_T">';
	foreach ($files as $file) {

		$excluded = FALSE;
		if (in_array(basename($file), $excluded_list)) { $excluded = TRUE; };

		if (!is_dir($ipath.$file) && !$excluded) {

			//Determine file type & set cooresponding icon type.
			$ext = end( explode(".", strtolower($file)) );
			$type = $fclasses[array_search($ext, $ftypes)];
?>
			<tr>
				<td>
					<?php echo '<a href="'.$ONESCRIPT.$param1.'&amp;f='.rawurlencode($file).'&amp;p=edit" >'; ?>
					<?php show_icon($type); ?>
					<?php echo  htmlentities($file), '</a>'; ?>
				</td>
				<td class="meta_T meta_size">&nbsp;
					<?php echo number_format(filesize($ipath.$file)); ?> B
				</td>
				<td class="meta_T meta_time"> &nbsp;
					<script>FileTimeStamp(<?php echo filemtime($ipath.$file); ?>);</script>
				</td>
			</tr>
<?php
		}//end if !is_dir...
	}//end foreach file
	echo '</table>';
}//end list_files() ************************************************************




function Index_Page(){ //*******************************************************
	global $ONESCRIPT, $ipath;

	//<!--==== List folders/sub-directores ====-->
	echo '<p class="index_folders">';
		$folders = glob($ipath."*",GLOB_ONLYDIR);
		natcasesort($folders);
		foreach ($folders as $folder) {
			echo '<a href="'.$ONESCRIPT.'?i='.URLencode_path($folder).'/">'.PHP_EOL;
			svg_icon_folder();
			echo htmlentities(basename($folder)).' /</a>';
		}
	echo '</p>';

	Upload_New_Rename_Delete_Links();

	list_files();

	Upload_New_Rename_Delete_Links();

}//end Index_Page()*************************************************************




function Edit_Page_Buttons($text_editable, $too_large_to_edit) { //*************
	global $ONESCRIPT, $param1, $param2;
	$ACTION = "parent.location = '".$ONESCRIPT.$param1.$param2.'&amp;p=';
?>
	<p class="buttons_right">
	<?php if ($text_editable && !$too_large_to_edit) { //Show save & reset only if editable file ?>
		<input type="submit" class="button" value="Save"                  onclick="submitted = true;" id="save_file">
		<input type="button" class="button" value="Reset - loose changes" onclick="Reset_File()"      id="reset">
		<script>
			//Set disabled here instead of via input attribute in case js is disabled.
			//If js is disabled, user would be unable to save changes.
			document.getElementById('save_file').disabled = "disabled";
			document.getElementById('reset').disabled     = "disabled";
		</script>
	<?php } ?>
	<input type="button" class="button" value="Rename/Move" onclick="<?php echo $ACTION.'rename' ?>'">
	<input type="button" class="button" value="Copy"        onclick="<?php echo $ACTION.'copy' ?>'  ">
	<input type="button" class="button" value="Delete"      onclick="<?php echo $ACTION.'delete' ?>'">
	<input type="button" class="button close" value="Close" onclick="parent.location = '<?php echo $ONESCRIPT.$param1 ?>'">
	</p>
<?php
}//end Edit_Page_Buttons()******************************************************




//******************************************************************************
function Edit_Page_form($ext, $text_editable, $too_large_to_edit, $too_large_to_edit_message){
	global $ONESCRIPT, $param1, $param2, $param3, $filename, $itypes, $INPUT_SESSIONID, $EX, $message;
	clearstatcache ();
?>
	<form id="edit_form" name="edit_form" method="post" action="<?php echo $ONESCRIPT.$param1.$param2.$param3 ?>">
		<?php echo $INPUT_SESSIONID; ?>
<?php
		if ( !in_array( strtolower($ext), $itypes) ) { //If non-image, show textarea

			if (!$text_editable) { // If non-text file, disable textarea
				echo '<p class="edit_disabled">Non-text or unkown file type. Edit disabled.<br><br></p>';

			}elseif ( $too_large_to_edit ) {
 				echo '<p class="edit_disabled">'.$too_large_to_edit_message.'</p>';

			}else{
				if (PHP_VERSION_ID  < 50400) {  // 5.4.0
					$filecontent = htmlspecialchars(file_get_contents($filename));
				}else{
					$filecontent = htmlspecialchars(file_get_contents($filename),ENT_SUBSTITUTE);
				}
				$bad_chars = ($filecontent == "" && filesize($filename) > 0);

				if ($bad_chars){ //did specialchars return an empty string?
					echo '<pre class="edit_disabled">'.$EX.' File contains an invalid character. Edit and view disabled.<br>';
					echo ' htmlspecialchars() returned and empty string from what <i>may be</i> an otherwise valid file.<br>';
					echo ' This behavior can be inconsistant from version to version of php.<br></pre>';
				}else{
					echo '<input type="hidden" name="filename" id="filename" value="'.htmlspecialchars($filename).'">';
					echo '<textarea id="file_content" name="content" cols="70" rows="25"
						onkeyup="Check_for_changes(event);">'.$filecontent.'</textarea>'.PHP_EOL;
				}
			} //end if !editable /else...
		} //end if non-image, show textarea

		Edit_Page_Buttons($text_editable, $too_large_to_edit);

	if ($text_editable && !$too_large_to_edit && !$bad_chars) {
		Edit_Page_scripts();
		echo '<div id="edit_note">NOTE: On some browsers, such as Chrome, if you click the browser [Back] then browser [Forward] (or vice versa), the file state may not be accurate.  To correct, click the browser\'s [Reload].</div>';
	}
?>
	</form>
<?php
}//end Edit_Page_form() ********************************************************




function Edit_Page() { //*******************************************************
	global $ONESCRIPT, $param1, $filename, $filecontent, $etypes, $itypes, $MAX_EDIT_SIZE, $MAX_VIEW_SIZE;

	//Determine if text editable file type
	$ext = end( explode(".", strtolower($filename) ) );
	if (in_array($ext, $etypes)) { $text_editable = TRUE;  }
	else                         { $text_editable = FALSE; }

	$too_large_to_edit = (filesize($filename) > $MAX_EDIT_SIZE);
	$too_large_to_view = (filesize($filename) > $MAX_VIEW_SIZE);

	if ($too_large_to_edit){$header2 = "Viewing: ";}
	else                   {$header2 = "Editing: ";}

	$too_large_to_edit_message =
'<b>Edit disabled. Filesize &gt; '.number_format($MAX_EDIT_SIZE).' bytes.</b><br>
Some browsers (on my PC) bog down or become unstable while editing a large file in an HTML &lt;textarea&gt;.<br>
Adjust $MAX_EDIT_SIZE in the configuration section of OneFileCMS as needed.<br>
A simple trial and error test can determine a practical limit for a given browser/computer.';
	$too_large_to_view_message =
'<b>View disabled. Filesize &gt; '.number_format($MAX_VIEW_SIZE).' bytes.</b><br>
Click the the file name above to view normally in a browser window.<br>
Adjust $MAX_VIEW_SIZE in the configuration section of OneFileCMS as needed.<br>
(The default value for $MAX_VIEW_SIZE is completely arbitrary, and may be adjusted as desired to suit individual perceptions of neccessity.)';

	echo '<h2 id="edit_header">'.$header2;
	echo '<a class="filename" href="/'.URLencode_path($filename).'" target="_blank">'.htmlentities(basename($filename)).'</a>';
	echo '</h2>'.PHP_EOL;
?>
	<p class="file_meta">
	<span class="meta_size">Filesize: <?php echo number_format(filesize($filename)); ?> bytes</span> &nbsp;
	<span class="meta_time">Updated: <script>FileTimeStamp(<?php echo filemtime($filename); ?>, 1);</script></span><br>
	</p>

	<input type="button" id="close1" class="button close" value="Close" onclick="parent.location = '<?php echo $ONESCRIPT.$param1 ?>'">
	<script>document.getElementById('close1').focus();</script>
	<div style="clear:both;"></div>
<?php
	Edit_Page_form($ext, $text_editable, $too_large_to_edit, $too_large_to_edit_message);

	if ( in_array( $ext, $itypes) ) { show_image(); }

	echo '<div style="clear:both;"></div>';

	if ( $text_editable && $too_large_to_edit && !$too_large_to_view ) {
		$filecontent = htmlspecialchars(file_get_contents($filename), ENT_COMPAT,'UTF-8');
		echo '<pre class="edit_disabled view_file">'.$filecontent.'</pre>';
	}elseif ( $text_editable && $too_large_to_view ){
		echo '<p class="edit_disabled">'.$too_large_to_view_message.'</p>';
	}

}//End Edit_Page ***************************************************************




function Edit_Page_response(){ //***If on Edit page, and [Save] clicked ********
	global $filename, $message, $EX;
	$filename = $_POST["filename"];
	$content  = $_POST["content"];

	$bytes = file_put_contents($filename, $content);

	if ($bytes !== false) {
		$message = '<b>File saved: '.$bytes.' bytes written.</b>';
	}else{
		$message = $EX.' <b>There was an error saving file.</b>';
	}
}//end Edit_Page_response() ****************************************************




function Upload_Page() { //*****************************************************
	global $ONESCRIPT, $ipath, $param1, $INPUT_SESSIONID;

	//Determine $MAX_FILE_SIZE to upload
	$upload_max_filesize = ini_get('upload_max_filesize'); //This should be < post_max_size,
	$post_max_size       = ini_get('post_max_size');       //but, just in case, check both...

	function shorthand_to_int($SHORTHAND){ //*******************
		$KMG = strtoupper(substr($SHORTHAND, -1));
		if     ($KMG == "K") { return $SHORTHAND * 1024; }
		elseif ($KMG == "M") { return $SHORTHAND * 1048576; }
		elseif ($KMG == "G") { return $SHORTHAND * 1073741824; }
		else                 { return $SHORTHAND; }
	}//end function shorthand_to_int() *************************

	$UMF = shorthand_to_int($upload_max_filesize);
	$PMS = shorthand_to_int($post_max_size);

	if ($UMF <= $PMS){ $MAX_FILE_SIZE = $UMF; $max_msg = $upload_max_filesize.' &nbsp; per upload_max_filesize in php.ini.'; }
	else             { $MAX_FILE_SIZE = $PMS; $max_msg = $post_max_size.' &nbsp; per post_max_size in php.ini'; }
?>
	<h2>Upload File</h2>
	<p>Note: Maximum upload file size is: <?php echo $max_msg; ?></p>
	<form enctype="multipart/form-data" action="<?php echo $ONESCRIPT.$param1; ?>&amp;p=uploaded" method="post">
		<?php echo $INPUT_SESSIONID; ?>
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_FILE_SIZE ?>">
		<input type="hidden" name="upload_destination" value="<?php echo htmlspecialchars($ipath); ?>" >
		<input type="file"   name="upload_file" id="upload_file" size="100">
		<?php Cancel_Submit_Buttons("Upload","cancel"); ?>
	</form>
<?php
} //end Upload_Page() **********************************************************




function Upload_File_response() { //********************************************
	global $filename, $message, $EX, $page;
	$filename    = $_FILES['upload_file']['name'];
	$destination = Check_path($_POST["upload_destination"]);
	$page  = "index";
	$MAXUP1 = ini_get('upload_max_filesize');
	$MAXUP2 = number_format ($_POST['MAX_FILE_SIZE']).' bytes';
	$ERROR = $_FILES['upload_file']['error'];

	if     ( $ERROR == 1 ){ $ERRMSG = 'Error 1: File too large.  upload_max_filesize = '.$MAXUP1.' (From php.ini)';}
	elseif ( $ERROR == 2 ){ $ERRMSG = 'Error 2: File too large.  $MAX_FILE_SIZE = '.$MAXUP2.' (From OneFileCMS)';}
	elseif ( $ERROR == 3 ){ $ERRMSG = 'Error 3: The uploaded file was only partially uploaded.'; }
	elseif ( $ERROR == 4 ){ $ERRMSG = 'Error 4: No file was uploaded. '; }
	elseif ( $ERROR == 5 ){ $ERRMSG = 'Error 5. '; }
	elseif ( $ERROR == 6 ){ $ERRMSG = 'Error 6: Missing a temporary folder.'; }
	elseif ( $ERROR == 7 ){ $ERRMSG = 'Error 7: Failed to write file to disk.'; }
	elseif ( $ERROR == 8 ){ $ERRMSG = 'Error 8: A PHP extension stopped the file upload.'; }
	else                  { $ERRMSG = ''; }

	if (($filename == "")){
		$message .= $EX.' <b>No file selected for upload... </b>';
	}elseif (($destination != "") && !is_dir($destination)) {
		$message .= $EX.' Destination folder does not exist: <br><b>';
		$message .= htmlentities($WEB_ROOT.$destination).'</b><br><b>Upload cancelled.</b>';
	}else{
		$message .= 'Uploading: "<b>'.htmlentities($filename).'</b>"...';
		$savefile = ordinalize($destination, $filename, $savefile_msg);
		if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $savefile)) {
			$message .= '<br>Upload successful! '.$savefile_msg;
		} else{
			$message .= '<br>'.$EX.' <b> Upload failed: </b>'.$ERRMSG.'';
		}
	}
}//end Upload_File_response() **************************************************




function New_File_Page() { //***************************************************
	global $FORM_COMMON, $INVALID_CHARS;
?>
	<h2>New File</h2>
	<?php echo $FORM_COMMON ?>
		<p>File will be created in the current folder. &nbsp;
		Some invalid characters are: <span class="mono"><?php echo htmlentities($INVALID_CHARS) ?></span></p>
		<input type="text" name="new_file" id="new_file" value="">
		<?php Cancel_Submit_Buttons("Create","new_file"); ?>
	</form>
<?php
}//end New_File_Page()**********************************************************




function New_File_response() { //***********************************************
	global $ipath, $param2, $filename, $page, $message, $EX, $INVALID_CHARS, $INVALID_CHARS_array;

	$new_name = trim($_POST["new_file"],'/ '); //Trim spaces and slashes.
	$filename = $ipath.$new_name;
	$page = "index"; // return to index if new file fails

	$invalid = false;
	foreach ($INVALID_CHARS_array as $bad_char) {
		if (strpos($new_name, $bad_char) !== false) { $invalid = true; }
	}

	if ($invalid){
		$message .= $EX.' <b>New file not created:</b> '.htmlentities($new_name).'<br>'.
			'<b> &nbsp; &nbsp; &nbsp; Name contains invalid character(s): '.
			'<span class="mono">'.htmlentities($INVALID_CHARS).'</span></b>';
	}elseif ($new_name == ""){
		$message .= $EX.' <b>New file not created -</b> no name given';
	}elseif (file_exists($filename)) {
		$message .= $EX.' <b>File already exists: ';
		$message .= htmlentities($new_name).'</b>';
	}elseif ($handle = fopen($filename, 'w')) {
		fclose($handle);
		$message .= '<b>Created file:</b> '.htmlentities($new_name);
		$page     = "edit";
		$param2   = '&amp;f='.rawurlencode(basename($filename));// for Edit_Page() buttons
	}else{
		$message .= $EX.' <b>Error - new file not created:<br>';
		$message .= htmlentities($new_name);
	}
}//end New_File_response() *****************************************************




function Copy_Ren_Move_Page($action, $title, $name_id, $isfile) { //******
	//$action = 'Copy' or 'Rename'. $isfile = 1 if acting on a file, not a folder
	global $WEB_ROOT, $ipath, $filename, $FORM_COMMON;
	if ($isfile) { $old_name = $filename; }else{ $old_name = $ipath; }
	if ($isfile) { $new_name = $filename; }else{ $new_name = $ipath; }
	if ($action == "Copy" ) { $new_name = ordinalize($ipath, basename($filename), $msg); }
?>
	<h2><?php echo $action.' '.$title ?></h2>
	<p>To move a file or folder, change the path/to/folder/or_file. The new location must already exist.</p>
	<?php echo $FORM_COMMON ?>
		<p>
			<label>Old name:</label>
			<span class="web_root"><?php echo htmlentities($WEB_ROOT); ?></span><input type="text"
				name="old_name" value="<?php echo htmlspecialchars($old_name); ?>" readonly="readonly">
		</p>
		<p>
			<label>New name:</label>
			<span class="web_root"><?php echo htmlentities($WEB_ROOT); ?></span><input type="text"
				name="<?php echo $name_id ?>" id="<?php echo $name_id ?>"
				value="<?php echo htmlspecialchars($new_name); ?>">
		</p>
		<?php Cancel_Submit_Buttons($action, $name_id); ?>
	</form>
<?php
} //end Copy_Ren_Move_Page() ***************************************************




//******************************************************************************
function Copy_Ren_Move_response($old_name, $new_name, $action, $msg1, $msg2, $isfile){
	//$action = 'copy' or 'rename'. $isfile = 1 if acting on a file, not a folder
	global $WEB_ROOT, $ipath, $param1, $param2, $message, $EX, $page, $filename;

	$old_name = trim($old_name,'/ ');
	$new_name = trim($new_name,'/ ');
	$new_location = dirname($new_name);
	$filename = $old_name; //default if error
	if ($isfile) { $page = "edit"; }else{ $page = "index"; }

	if ( !is_dir($new_location) ){
		$message .= $EX.' <b>'.$msg1.' Error - new parent location does not exist:</b><br>';
		$message .= htmlentities($WEB_ROOT.$new_location).'/<br>';
	}elseif ( !file_exists($filename) ){
		$message .= $EX.' <b>'.$msg1.' Error - source file does not exist:</b><br>';
		$message .= htmlentities($filename);
	}elseif (file_exists($new_name)) {
		$message .= $EX.' <b>'.$msg1.' Error - target filename already exists:<br>';
		$message .= htmlentities($WEB_ROOT.$new_name).'</b>';
	}elseif ($action($old_name, $new_name)) {
		$message .= '<b>'.htmlentities($WEB_ROOT.$old_name).'</b><br>';
		$message .= ' --- '.$msg2.' to ---<br>';
		$message .= '<b>'.htmlentities($WEB_ROOT.$new_name).'</b>';
		$filename = $new_name; //so edit page knows what to edit
		if ($isfile) { $ipath = Check_path(dirname($filename)); } //if changed,
		else         { $ipath = Check_path($filename); }          //return to new dir.
		$param1   = '?i='.URLencode_path($ipath);
		$param2   = '&amp;f='.rawurlencode(basename($filename));
		$param3   = '&amp;p=edit';
	}else{
		$message .= '<b>'.htmlentities($WEB_ROOT.$old_name).'</b><br>';
		$message .= $EX.' <b>Error during '.$msg1.' from the above to the following:</b><br>';
		$message .= '<b>'.htmlentities($WEB_ROOT.$new_name).'</b>';
	}
}//end Copy_Ren_Move_response() ************************************************




function Delete_File_Page() { //************************************************
	global $filename, $FORM_COMMON;
?>
	<h2>Delete File</h2>
	<?php echo $FORM_COMMON ?>
		<input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($filename); ?>" >
		<span class="verify"><?php echo htmlentities(basename($filename)); ?></span>
		<p class="sure"><b>Are you sure?</b></p>
		<?php Cancel_Submit_Buttons("DELETE", "cancel"); ?>
	</form>
<?php
} //end Delete_File_Page() *****************************************************




function Delete_File_response(){ //*********************************************
	global $filename, $message, $EX, $page;

	$page = "index"; //Return to index
	$filename = $_POST["delete_file"];

	if (unlink($filename)) {
		$message .= '<b>Deleted file:</b> '.htmlentities(basename($filename));
	}else{
		$message .= $EX.' <b>Error deleting "'.htmlentities($filename).'"</b>.';
		$page = "edit";
	}
}//end Delete_File_response() **************************************************




function New_Folder_Page() { //*************************************************
	global $FORM_COMMON, $INVALID_CHARS;
?>
	<h2>New Folder</h2>
	<?php echo $FORM_COMMON ?>
		<p>Folder will be created in the current folder. &nbsp;
		Some invalid characters are: <span class="mono"><?php echo htmlentities($INVALID_CHARS) ?></span></p>
		<input type="text" name="new_folder" id="new_folder" value="">
		<?php Cancel_Submit_Buttons("Create","new_folder"); ?>
	</form>
<?php
} //end New_Folder_Page() ******************************************************




function New_Folder_response(){ //**********************************************
	global $ipath, $param1, $page, $message, $EX, $INVALID_CHARS, $INVALID_CHARS_array;

	$new_name = trim($_POST["new_folder"],'/ '); //Trim spaces, and make sure only has a single trailing slash.

	$invalid = false;
	foreach ($INVALID_CHARS_array as $bad_char) {
		if (strpos($new_name, $bad_char) !== false) { $invalid = true; }
	}
	$page = "index"; //Return to index

	$new_ipath = $ipath.$new_name.'/';

	if ($invalid){
		$message .= $EX.' <b>New folder not created:</b> '.htmlentities($new_name).'<br>'.
			'<b> &nbsp; &nbsp; &nbsp; Name contains invalid character(s): '.
			'<span class="mono">'.htmlentities($INVALID_CHARS).'</span></b>';
	}elseif ($new_name == ""){
		$message .= $EX.' <b>New folder not created - no name given.</b>';
	}elseif (is_dir($new_ipath)) {
		$message .= $EX.' <b>Folder already exists: ';
		$message .= htmlentities($new_ipath).'</b>';
	}elseif (mkdir($new_ipath)) {

		$message .= '<b>Created folder:</b> '.htmlentities($new_name);
		$ipath    = $new_ipath;  //return to new folder
		$param1   = '?i='.URLencode_path($ipath);
	}else{
		$message .= $EX.' <b>Error - new folder not created: </b><br>';
		$message .= htmlentities($new_name);
	}
}//end New_Folder_response *****************************************************




function Delete_Folder_Page(){ //***********************************************
	global $WEB_ROOT, $ipath, $FORM_COMMON;
?>
	<h2>Delete Folder</h2>
	<?php echo $FORM_COMMON ?>
		<input type="hidden" name="delete_folder" value="<?php echo htmlspecialchars($ipath); ?>" >
		<span class="web_root"><?php echo htmlentities($WEB_ROOT.Check_path(dirname($ipath))); ?></span><span
		class="verify"><?php echo htmlentities(basename($ipath)); ?></span> /
		<p class="sure"><b>Are you sure?</b></p>
		<?php Cancel_Submit_Buttons("DELETE", "cancel"); ?>
	</form>
<?php
} //end Delete_Folder_Page() //*************************************************




function Delete_Folder_response() { //******************************************
	global $ipath, $param1, $page, $message, $EX;
	$page = "index"; //Return to index
	$foldername = trim($_POST["delete_folder"], '/');

	if ( !is_empty($ipath) ) {
		$message .= $EX.' <b>Folder not empty. &nbsp; Folders must be empty before they can be deleted.</b>';
		$page = "index";
	}elseif (@rmdir($foldername)) {
		$message .= '<b>Deleted folder:</b> '.htmlentities(basename($foldername));
		$ipath  = Check_path($foldername); //Return to parent dir.
		$param1 = '?i='.URLencode_path($ipath);
	}else {
		$message .= $EX.' <b>"'.htmlentities($foldername).'/"</b> an error occurred during delete.';
	}
}//end Delete_Folder_response() ************************************************




function Page_Title(){ //***<title>Page_Title()</title>*************************
	global $page;

	if     ($page == "login")        { return "Log In";         }
	elseif ($page == "hash")         { return "Hash";           }
	elseif ($page == "edit")         { return "Edit/View File"; }
	elseif ($page == "upload")       { return "Upload File";    }
	elseif ($page == "newfile")      { return "New File";       }
	elseif ($page == "copy" )        { return "Copy";           }
	elseif ($page == "rename")       { return "Rename File";    }
	elseif ($page == "delete")       { return "Delete";         }
	elseif ($page == "newfolder")    { return "New Folder";     }
	elseif ($page == "renamefolder") { return "Rename Folder";  }
	elseif ($page == "deletefolder") { return "Delete Folder";  }
	else                             { return $_SERVER['SERVER_NAME']; }
}//end Page_Title() ************************************************************




function Load_Selected_Page(){ //***********************************************
	global $ONESCRIPT, $page;

	if     ($page == "login")        { Login_Page();         }
	elseif ($page == "hash")         { Hash_Page();          }
	elseif ($page == "edit")         { Edit_Page();          }
	elseif ($page == "upload")       { Upload_Page();        }
	elseif ($page == "newfile")      { New_File_Page();      }
	elseif ($page == "copy")         { Copy_Ren_Move_Page('Copy', 'File', 'copy_file', 1); }
	elseif ($page == "rename")       { Copy_Ren_Move_Page('Rename', 'File', 'rename_file', 1); }
	elseif ($page == "delete")       { Delete_File_Page();   }
	elseif ($page == "newfolder")    { New_Folder_Page();    }
	elseif ($page == "renamefolder") { Copy_Ren_Move_Page('Rename', 'Folder', 'rename_folder', 0); }
	elseif ($page == "deletefolder") { Delete_Folder_Page(); }
	else                             { Index_Page();         } //default
}//end Load_Selected_Page() ****************************************************




//******************************************************************************
function Time_Stamp_scripts() {  ?>

<script>//Dispaly file's timestamp in user's local time

function pad(num){ if ( num < 10 ){ num = "0" + num; }; return num; }


function FileTimeStamp(php_filemtime, show_offset){

	//php's filemtime returns seconds, javascript's date() uses milliseconds.
	var FileMTime = php_filemtime * 1000;

	var TIMESTAMP  = new Date(FileMTime);
	var YEAR  = TIMESTAMP.getFullYear();
	var	MONTH = pad(TIMESTAMP.getMonth() + 1);
	var DATE  = pad(TIMESTAMP.getDate());
	var HOURS = TIMESTAMP.getHours();
	var MINS  = pad(TIMESTAMP.getMinutes());
	var SECS  = pad(TIMESTAMP.getSeconds());

	if( HOURS < 12){ AMPM = "am"; }
	else           { AMPM = "pm"; HOURS = HOURS - 12; }
	HOURS = pad(HOURS);

	var GMT_offset = -(TIMESTAMP.getTimezoneOffset()); //Yes, I know - seems wrong, but it's works.

	if (GMT_offset < 0) { NEG=-1; SIGN="-"; } else { NEG=1; SIGN="+"; }

	var offset_HOURS = Math.floor(NEG*GMT_offset/60);
	var offset_MINS  = pad( NEG * GMT_offset % 60 );
	var offset_FULL  = "UTC " + SIGN + offset_HOURS + ":" + offset_MINS;

	if (show_offset){ var DATETIME = YEAR+"-"+MONTH+"-"+DATE+" &nbsp;"+HOURS+":"+MINS+":"+SECS+" "+AMPM+" ("+offset_FULL+")"; }
	else            { var DATETIME = YEAR+"-"+MONTH+"-"+DATE+" &nbsp;"+HOURS+":"+MINS+":"+SECS+" "+AMPM; }

	document.write( DATETIME );

}//end FileTimeStamp(php_filemtime)
</script>
<?php }//end Time_Stamp_scripts() ******************************************




function Edit_Page_scripts() { //********************************************
?>
	<!--======== Provide feedback re: unsaved changes ========-->
	<script>

	var File_textarea    = document.getElementById('file_content');
	var Save_File_button = document.getElementById('save_file');
	var Reset_button     = document.getElementById('reset');

	var start_value = File_textarea.value;
	var submitted   = false
	var changed     = false;



	// The following events only apply when the element is active.
	// [Save] is disabled unless there are changes to the open file.
	Save_File_button.onfocus = function() {Save_File_button.style.backgroundColor = "rgb(255,250,150)";}
	Save_File_button.onblur  = function() {Save_File_button.style.backgroundColor ="#Fee";}
	Save_File_button.onmouseover = function() {Save_File_button.style.backgroundColor = "rgb(255,250,150)";}
	Save_File_button.onmouseout  = function() {Save_File_button.style.backgroundColor = "#Fee";}



	function Reset_file_status_indicators() {
		changed = false;
		File_textarea.style.backgroundColor = "#eFe";  //light green
		Save_File_button.style.backgroundColor = "";
		Save_File_button.style.borderColor = "";
		Save_File_button.style.borderWidth = "1px";
		Save_File_button.disabled = "disabled";
		Save_File_button.value = "Save";
		Reset_button.disabled = "disabled";
		//File_textarea.focus();
	}


	window.onbeforeunload = function() {
		if ( changed && !submitted) {
			//FF4+ Ingores the supplied msg below & only uses a system msg for the prompt.
			return "               Unsaved changes will be lost!";
		}
	}


	window.onunload = function() {
		//without this, a browser back then forward would reload file with local/
		// unsaved changes, but with a green b/g as tho that's the file's contents.
		if (!submitted) {
			File_textarea.value = start_value;
			Reset_file_status_indicators();
		}
	}


	//With selStart & selEnd == 0, moves cursor to start of text field.
	function setSelRange(inputEl, selStart, selEnd) {
		if (inputEl.setSelectionRange) {
			inputEl.focus();
			inputEl.setSelectionRange(selStart, selEnd);
		} else if (inputEl.createTextRange) {
			var range = inputEl.createTextRange();
			range.collapse(true);
			range.moveEnd('character', selEnd);
			range.moveStart('character', selStart);
			range.select();
		}
	}


	function Check_for_changes(event){
		var keycode=event.keyCode? event.keyCode : event.charCode;
		changed = (File_textarea.value != start_value);
		if (changed){
			document.getElementById('message').innerHTML = " "; // Must have a space, or it won't clear the msg.
			File_textarea.style.backgroundColor = "#Fee";  //light red
			Save_File_button.style.backgroundColor ="#Fee";
			Save_File_button.style.borderColor = "#F44";   //less light red
			Save_File_button.style.borderWidth = "1px";
			Save_File_button.disabled = "";
			Reset_button.disabled = "";
			Save_File_button.value = "SAVE CHANGES!";
		}else{
			Reset_file_status_indicators()
		}
	}


	//Reset textarea value to when page was loaded.
	//Used by [Reset] button, and when page unloads (browser back, etc).
	//Needed becuase if the page is reloaded (ctl-r, or browser back/forward, etc.),
	//the text stays changed, but "changed" gets set to false, which looses warning.
	function Reset_File() {
		if (changed) {
			if ( !(confirm("Reset file and loose unsaved changes?")) ) { return; }
		}
		File_textarea.value = start_value;
		Reset_file_status_indicators();
		setSelRange(File_textarea, 0, 0) //MOve cursor to start of textarea.
	}

	Reset_file_status_indicators()
	</script>

<?php }//End Edit_Page_scripts() ********************************************




function style_sheet(){ //****************************************************?>
<style>
/* --- reset --- */
* { border : 0; outline: 0; margin : 0; padding: 0;
font-family: inherit; font-weight: inherit; font-style : inherit;
font-size  : 100%; vertical-align: baseline; }


/* --- general formatting --- */

body { font-size: 1em; background: #DDD; font-family: sans-serif; }

p, table { margin-bottom: .5em; margin-top: .5em;}

div { position: relative; }

h1,h2,h3,h4,h5,h6 { font-weight: bold; }
h2, h3 { font-size: 1.2em; margin: .5em 1em .5em 0; } /*TRBL*/

i, em     { font-style : italic; }
b, strong { font-weight: bold;   }

:focus { outline:0; }

table { border-collapse:separate; border-spacing:0; }
th,td { text-align:left; font-weight:400; }

a       { border: 1px solid transparent; color: rgb(100,45,0); text-decoration: none; }
a:hover { border: 1px solid #807568; background-color: rgb(255,250,150); }
a:focus { border: 1px solid #807568; background-color: rgb(255,250,150); }

form p { margin-bottom: .3em; }

label { display: inline-block; width : 6em; font-size : 1em; }

svg { margin: 0; padding: 0; }

pre { /*Used when trouble shooting around test output*/
	background: white;
	border: 1px solid #807568;
	padding: .5em;
	margin: 0;
	}


/* --- layout --- */

.container {
	border : 0px solid #807568;
	width  : 810px;
	margin : 0em auto;
	}


.header {
	border-bottom : 1px solid #807568;
	padding: 04px 0px 01px 0px;
	margin : 0 0 .5em 0;
	}


#logo {
	font-family: 'Trebuchet MS', sans-serif;
	font-size:2.2em;
	font-weight: bold;
	color: black;
	padding: .1em;
	}

.filename {
	border: 1px solid #807568;
	padding: .1em .2em .1em .2em;
	font-weight: 700;
	font-family: courier;
	background-color: #EEE;
	}

#message { margin-bottom: .5em;}

#message p {
	margin: 0;
	padding: 4px 0px 4px .5em;
	border: 1px solid #807568;
	Xfont-family: courier;
	font-size: 1em;
	line-height: 1.2em;
	background: #fff000;
	}

#message #Xbox { float: right; }

#message #dismiss { padding: 5px 4px 5px 4px; border-right: none; } /*TRBL*/ /*font-family: Courier; font-size: 1.2em;*/


/* --- INDEX directory listing, table format --- */
table.index_T {
	min-width: 30em;
	font-size: .95em;
	border-style: outset;
	border-width: 1px;
	border-color: #807568;
	border-collapse: collapse;
	margin-bottom: .7em;
	background-color: #FdFdFd;
	}

table.index_T  tr:hover { border: 1px solid #807568; }

table.index_T td {
	border-width  : 1px;
	border-color  : silver;
	border-style  : inset;
	vertical-align: middle;
	}

.index_T a {
	height : 1em;
	display: block;
	padding: .2em 1em .3em .3em;
	color  : rgb(100,45,0);
	border : none;
	overflow   : hidden;
	}

.index_T a:hover { background-color: rgb(255,250,150); }
.index_T a:focus { background-color: rgb(255,250,150); }



/* File size & date */

.meta_size { min-width:  6em; }

.meta_time { min-width: 15em; }

.meta_T {
	padding-right : .5em;
	text-align    : right;
	font-family   : courier;
    font-size     : .9em;
	color         : #333;
	}


.index_folders { min-height: 1.7em;  margin-bottom: .2em; }

.index_folders a {
	Xborder       : 1px solid #807568;
	display      : inline-block;
	line-height  : 1em;
	font-size    : 1em;
	margin-right : .6em;
	margin-bottom: .1em;
	padding      : 3px .4em 3px 5px; /*TRBL*/
	}

.index_folders a:hover { background-color: rgb(255,250,150); }
.index_folders a:focus { background-color: rgb(255,250,150); }



/*  [Upload File] [New File] [New Folder] etc... */

.front_links a {
	display: inline-block;
	border : 1px solid #807568;
	height      : 1em;
	font-size   : 1em;
	margin-right: 1em;
	padding     : 3px 5px 5px 4px; /*TRBL*/
	background-color: #EEE;
	}

.front_links a .icon_fldr {margin : 1.5px 5px 0 0; }
.front_links a .icon_file {margin : 1.0px 5px 0 0; }

.front_links a:hover  { background-color: rgb(255,250,150); }
.front_links a:focus  { background-color: rgb(255,250,150); }


input[type="text"] {
	border: 1px solid #807568;
	padding: 2px;
	width: 40em;
	font: 1em "Courier New", Courier, monospace;
	}

textarea {
	border: 1px solid #999;
	font  : .95em "Courier New", Courier, monospace;
	margin: 0 0 0 0; /*T R B L*/
	width : 99.5%;
	height: 30em;
	}

textarea:focus { border: 1px solid #Faa; }

.edit_disabled {
	border : 1px solid #807568;
	width  : 99%;
	padding: .2em;
	margin : 0;
	background-color: #FFF000;
	line-height: 1.4em;
	}

.view_file {font-family: courier; font-size: .9em; background-color: #F8F8F8;}


input:focus { background-color: rgb(255,250,150); }

input:hover { background-color: rgb(255,250,150); }

input[readonly]       { color: #333; background-color: #EEE; }
input[disabled]       { color: #555; background-color: #EEE; }
input[disabled]:hover { background-color: rgb(236,233,216);  }
input[disabled]:hover { background-color: rgb(236,233,216);  }


.buttons_right         { float: right; }
.buttons_right .button { margin-left: 7px; }

.button {
	border : 1px solid #807568;
	padding: 4px 10px;
	cursor : pointer;
	color      : black;
	font-size  : .9em;
	font-family: sans-serif;
	background-color: #EEE;  /*#d4d4d4*/
	}

.button[disabled]  { color: #777; background-color: #EEE; }


/* --- header --- */

.nav {
	float     : right;
	display   : inline-block;
	margin-top: 1.6em;
	font-size : 1em;
	}

.nav a {
	border: 1px solid transparent;
	font-weight : bold;
	padding     : .0em;
	padding-top   : .2em;
	padding-left  : .6em;
	padding-right : .6em;
	padding-bottom: .1em;
	}

.nav a:hover { border: 1px solid #807568; }
.nav a:focus { border: 1px solid #807568; }


/* --- edit --- */

#edit_header  {margin: 0;}

#edit_form    {margin: 0;}

#file_content {height: 24em;}

.file_meta	  {float: left;  margin-top: .5em; font-size: 1em; color: #333; font-family: courier;}

.close        {float: right; margin-bottom: .5em;}

#edit_note    {font-size: .8em; color: #444 ;margin-top: 1em; clear:both;}



/* --- log in --- */

.login_page {
	margin  : 5em auto;
	border  : 1px solid #807568;
	padding : 1em 1em 0 1em;
	width   : 360px;
	}

.login_page .nav { margin-top: .5em; }

.login_input {
	border  : 1px solid #807568;
	padding : 2px 0px 2px 2px;
	width   : 356px;
	font    : 1em "Courier New";
	}

.login_page input[type="text"]{ width   : 354px; }


/* --- --- --- */
hr {
	line-height  : 0;
	Xfont-size    : 1px;
	display : block;
	position: relative;
	padding : 0;
	margin  : 1em auto;
	width   : 100%;
	clear   : both;
	border  : none;
	border-top   : 1px solid #807568;
	Xborder-bottom: 1px solid #eee;
	overflow: visible;
	}

.web_root { font:1.2em Courier; }

.verify {
	border: 1px solid #F44;
	color: #333;
	background-color: #FFE7E7;
	padding: .1em .2em;
	font: 1.2em Courier;
	}

.sure { margin: .7em 0em .5em 0; }

.icon {float: left; margin: 0 .3em 0 0;}

.mono {font-family: courier;}

#upload_file {
	border: 1px solid #807568;
	padding: 2px;
	width: 50em;
	Xfont: 1em "Courier New", Courier, monospace;
	}

#admin {padding: .3em;}

.info {margin-top: .7em; background: #f9f9f9; padding: .2em .5em;}

.path {padding: 3px 5px 3px 5px} /*TRBL*/

.edit_onefile {padding: 5px; float: right;}
</style>
<?php }//end style_sheet() *****************************************************




//******************************************************************************
//******************************************************************************
//Begin logic to determine page action


if( PHP_VERSION_ID < 50000 ) { exit("OneFileCMS requires PHP5 to operate. Tested on versions 5.2.17, 5.3.3 & 5.4"); }

Session_Startup(); //***********************************************************

Get_GET();         //***********************************************************

Init_Macros();     //***********************************************************




if ($VALID_POST) { //***********************************************************
	if     (isset($_FILES['upload_file']['name'])) { Upload_File_response(); }
	elseif (isset($_POST["whattohash"]   )) { Hash_Page_response(); }
	elseif (isset($_POST["filename"]     )) { Edit_Page_response(); }
	elseif (isset($_POST["new_file"]     )) { New_File_response();  }
	elseif (isset($_POST["copy_file"]    )) { Copy_Ren_Move_response($_POST[ "old_name"], $_POST["copy_file"], 'copy', 'Copy', 'Copied', 1); }
	elseif (isset($_POST["rename_file"]  )) { Copy_Ren_Move_response($_POST[ "old_name"], $_POST["rename_file"], 'rename', 'Rename/Move', 'Renamed/Moved', 1); }
	elseif (isset($_POST["delete_file"]  )) { Delete_File_response(); }
	elseif (isset($_POST["new_folder"]   )) { New_Folder_response();  }
	elseif (isset($_POST["rename_folder"])) { Copy_Ren_Move_response($_POST[ "old_name"], $_POST["rename_folder"], 'rename', 'Rename/Move', 'Renamed/Moved', 0); }
	elseif (isset($_POST["delete_folder"])) { Delete_Folder_response(); }
}//end if ($VALID_POST) ********************************************************




//*** Verify valid $page and/or $filename **************************************

if     (!in_array(strtolower($page), $valid_pages))   { $page = "index"; }

        //Don't load login screen if already in a valid session.
elseif ( ($page == "login") && ($_SESSION['valid']) ) { $page = "index"; }

		//Don't load edit page if $filename doesn't exist.
elseif ( ($page == "edit")  && !is_file($filename) )  { $page = "index"; }

elseif ($page == "logout") { $page = "login"; $_SESSION['valid'] = "0";	session_destroy();
		$message .= 'You have successfully logged out.'; }

		//Don't load delete page if folder not empty.
elseif ( ($page == "deletefolder") && !is_empty($ipath) ) {
	   $message .= $EX.' <b>Folder not empty. &nbsp; Folders must be empty before they can be deleted.</b>';
	   $page = "index";}

		//if size of $_POST > post_max_size, PHP only returns empty $_POST & $_FILE arrays.
elseif ($page == "uploaded" && !$VALID_POST){
	   $message .= $EX.'<b> Upload Error.  Total POST data (mostly filesize) exceeded post_max_size = '.ini_get('post_max_size').' (from php.ini).</b>';
	   $page = "index";}

elseif ( ($page == "edit") && ($filename == trim($ONESCRIPT, '/')) ) {
	   if ( $message == "" ){ $BR = ""; }else{ $BR = '<br>';}
	   $message .= '<style>#message p {background: red; color: white;}</style>';
	   $message .= $BR.$EX.' <b>CAUTION '.$EX.' You are editing the active copy of OneFileCMS - BACK IT UP &amp; BE CAREFUL !!</b>'; }

//******************************************************************************




//******************************************************************************
//******************************************************************************
?><!DOCTYPE html>

<html>
<head>

<title><?php echo $config_title.' - '.Page_Title() ?></title>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex">
<?php
// Embed or custom styles
if (!isset($config_style_sheet) || $config_style_sheet == null) {
	style_sheet();
} else {
	echo '<link href="';
	echo $config_style_sheet;
	echo '" type="text/css" rel="stylesheet">';
}
?>

<?php if ( ($page == "index") || ($page == "edit") ) { Time_Stamp_scripts(); } ?>

</head>
<body>

<?php if ($page == "login"){ echo '<div class="login_page">'; }
      else                 { echo '<div class="container" >'; }
?>

<div class="header">
	<a href="<?php echo $ONESCRIPT.'" id="logo">'.$config_title; ?></a>
	<?php echo $version.' (on&nbsp;php&nbsp'.phpversion().')'; ?>

	<div class="nav">
		<a href="<?php echo htmlentities(dirname($_SERVER['PHP_SELF'])) ?>" target="_blank"><?php show_favicon() ?>&nbsp;
		<b><?php echo htmlentities($WEBSITE) ?></b>  &nbsp;- &nbsp;
		Visit Site</a>
		<?php if ($page != "login") { ?>
		| <a href="<?php echo $ONESCRIPT ?>?p=logout">Log Out</a>
		<?php } ?>
	</div><div style="clear:both;"></div>
</div><!-- end header -->

<?php if ( $page != "login"  &&  $page != "hash" ){ Current_Path_Header(); } ?>

<?php message_box() ?>

<?php Load_Selected_Page() ?>

<hr>

<?php if ( ($page != "hash") && ($_SESSION['valid']) ){
		echo '<a id="admin" href="'.$ONESCRIPT.$param1.'&amp;p=hash">Admin</a>'; }
?>

</div><!-- end container/login_page -->
</body>
</html>
