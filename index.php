<?php
error_reporting(E_ALL ^ (E_NOTICE));
if (!file_exists("config.json"))
{
	echo "Please run through the settings.php file first";
	exit;
}
$settings = json_decode(file_get_contents("config.json"), true);
$updatescript = array();
$filelist = array();
$shscript = array();
function scriptadd($var)
{
	global $updatescript;
	if (is_array($var))
	{
		$updatescript = array_merge($updatescript, $var);
	} else {
		$updatescript[] = $var."\n";
	}
}
function zipadd($file)
{
	global $filelist;
	$filelist = array_merge($filelist, $file);
}
echo "<h2>".$settings['general']['title']."</h2>\n";
echo "<form name='input' action='index.php' method='get'>\n";
//Remove APK Form
if ($settings['enabled']['removeapk'])
{
	echo "<h3>Remove Bloatware</h3>\n";
	foreach($settings['removeapk'] as $file => $name)
	{
		echo "<input type='checkbox' name='remove[".$file."]'>".$name."</input><br>\n";
	}
}

//Boot Animations Form
if ($settings['enabled']['bootanim'])
{
	echo "<h3>Boot Animations (Previews may not be completely accurate)</h3>\n";
	foreach($settings['bootanim'] as $file => $config)
	{
		echo "<input type='radio' name='bootanim' value='$file'>".$config['name']."</input>\n";
		if (!empty($settings['bootanim'][$file]['link']))
		{
			echo "<a href='".$settings['bootanim'][$file]['link']."'>(Link)</a>";
		}
		if (file_exists("files/bootanim/".str_replace("zip", "gif", $file)))
		{
			echo "<br><img src='files/bootanim/".str_replace("zip", "gif", $file)."' />";
		}
		echo "<br>\n";
	}
}
//Mod Form
if ($settings['enabled']['mod'])
{
	echo "<h3>Mods</h3>";
	foreach($settings['mod'] as $folder => $config)
	{
		echo "<input type='checkbox' name='mod[".$folder."]'>".$config['name']."</input>\n";
		if (!empty($settings['mod'][$folder]['link']))
		{
			echo "<a href='".$settings['mod'][$folder]['link']."'>(Link)</a>";
		}
		echo "<br>\n";
	}
}
//Theme Form
if ($settings['enabled']['theme'])
{
	echo "<h3>Themes (Previews may not be completely accurate)</h3>";
	foreach($settings['theme'] as $folder => $config)
	{
		echo "<input type='radio' name='theme' value='$folder'>".$config['name']."</input>\n";
		if (!empty($settings['theme'][$folder]['link']))
		{
			echo "<a href='".$settings['theme'][$folder]['link']."'>(Link)</a>";
		}
		if (file_exists("files/theme/".$folder.".jpg"))
		{
			echo "<br><img src='files/theme/".$folder.".jpg' />";
		}
		elseif (file_exists("files/theme/".$folder.".png"))
		{
			echo "<br><img src='files/theme/".$folder.".png' />";
		}
		echo "<br>\n";
	}
}
//Kernel Form
if ($settings['enabled']['kernel'])
{
	echo "<h3>Kernels/Boot Images</h3>\n";
	foreach($settings['kernel'] as $file => $config)
	{
		echo "<input type='radio' name='kernel' value='$file'>".$config['name']."</input>\n";
		if (($settings['kernel'][$file]['link']))
		{
			echo "<a href='".$settings['kernel'][$file]['link']."'>(Link)</a>";
		}
		echo "<br>\n";
	}
}
echo "<input type='submit' value='Submit' />";
echo "</form>";
if (!$_GET)
{
	exit;
}

//Mount Partitions
foreach ($settings['mount'] as $mpoint => $config)
{
	scriptadd('mount("'.$config['fstype'].'", "'.$config['parttype'].'", "'.$config['device'].'", "'.$mpoint.'");');
	scriptadd('ui_print("Mounting '.$mpoint.'");');
}
scriptadd('assert(file_getprop("/system/build.prop", "ro.build.fingerprint") == "'.$settings['general']['fingerprint'].'");');
scriptadd('show_progress(1, 0);');

//Base Rom
if ($settings['general']['baserom'])
{
	$dir = "files/baserom";
	scriptadd('ui_print("Installing Base Rom");');
	if (file_exists("files/baserom/before-script"))
	{
		scriptadd(file("files/baserom/before-script", FILE_SKIP_EMPTY_LINES));
	}

	$it = new RecursiveDirectoryIterator($dir);
	
	foreach(new RecursiveIteratorIterator($it) as $file) {
		$file = str_replace($dir, "", $file);
		$file = str_replace("/.", "ikodslghjkd", $file);
		if ($file == "/before-script" or $file == "/after-script")
		{
		} 
		elseif (!strstr($file, "ikodslghjkd") and !strstr($file, "ikodslghjkd"))
		{
			scriptadd('package_extract_file("'.substr($file, 1).'", "'.$file.'");');
			zipadd(array($dir.$file => substr($file, 1)));
		}
	}
}
//Remove APKs
if ($settings['enabled']['removeapk'])
{
	if (isset($_GET['remove']))
	{
		foreach ($_GET['remove'] as $apk => $value)
		{
			scriptadd('delete("/data/app/'.$apk.'");');
			scriptadd('ui_print("Removing '.$settings['removeapk'][$apk].'");');
		}
	}
}

//Mods
if ($settings['enabled']['mod'])
{
	if (isset($_GET['mod']))
	{
		foreach($_GET['mod'] as $folder => $value)
		{
			if (!is_array($settings['mod'][$folder]))
			{
				continue;
			}
			$dir = "files/mod/".$folder;
			scriptadd('ui_print("Installing '.$settings['mod'][$folder]['name'].' Mod");');
			if (file_exists($dir."/before-script"))
			{
				scriptadd(file($dir."/before-script", FILE_SKIP_EMPTY_LINES));
			}
			$it = new RecursiveDirectoryIterator($dir);
			foreach(new RecursiveIteratorIterator($it) as $file) {
				$file = str_replace($dir, "", $file);
				$file = str_replace("/.", "ikodslghjkd", $file);
				if ($file == "/before-script" or $file == "/after-script")
				{
				} 
				elseif (!strstr($file, "ikodslghjkd") and !strstr($file, "ikodslghjkd"))
				{
					if ($settings['mod'][$folder]['removeodex'])
					{
						scriptadd('delete("'.str_replace(".apk", ".odex", $file).'");');
					}
				scriptadd('package_extract_file("'.substr($file, 1).'", "'.$file.'");');
				zipadd(array($dir.$file => substr($file, 1)));
				}
			}
		}
		if (file_exists($dir."/after-script"))
		{
			scriptadd(file($dir."/after-script", FILE_SKIP_EMPTY_LINES));
		}
	}
}

//Themes
if ($settings['enabled']['theme'])
{
	$folder = $_GET['theme'];
	if (isset($_GET['theme']) and is_array($settings['theme'][$folder]))
	{
		$dir = "files/theme/".$folder;
		scriptadd('ui_print("Installing '.$settings['theme'][$folder]['name'].' Theme");');
		if (file_exists($dir."/before-script"))
		{
			scriptadd(file($dir."/before-script", FILE_SKIP_EMPTY_LINES));
		}
		$it = new RecursiveDirectoryIterator($dir);
		foreach(new RecursiveIteratorIterator($it) as $file) {
			$file = str_replace($dir, "", $file);
			$file = str_replace("/.", "ikodslghjkd", $file);
			if ($file == "/before-script" or $file == "/after-script")
			{
			} 
			elseif (!strstr($file, "ikodslghjkd") and !strstr($file, "ikodslghjkd"))
			{
				if ($settings['theme']['removeodex'])
				{
					scriptadd('delete("'.str_replace(".apk", ".odex", $file).'");');
				}
				scriptadd('package_extract_file("'.substr($file, 1).'", "'.$file.'");');
				zipadd(array($dir.$file => substr($file, 1)));
			}
		}
		if (file_exists($dir."/after-script"))
		{
			scriptadd(file($dir."/after-script", FILE_SKIP_EMPTY_LINES));
		}
	}
}

//Boot Animations
if ($settings['enabled']['bootanim'])
{
	$bootanim = $_GET['bootanim'];
	if (isset($_GET['bootanim']) and is_array($settings['bootanim'][$bootanim]))
	{
		scriptadd('ui_print("Installing '.$settings['bootanim'][$bootanim]['name'].' Boot Animation");');
		scriptadd('package_extract_file("system/media/bootanimation.zip", "/system/media/bootanimation.zip");');
		zipadd(array("files/bootanim/".$_GET['bootanim'] => "system/media/bootanimation.zip"));
		if (is_dir("files/bootanim/".str_replace(".zip","", $_GET['bootanim'])."_audio"))
		{
			foreach(glob("files/bootanim/".str_replace(".zip","", $_GET['bootanim'])."_audio/*.ogg") as $file)
			{ 
				$filee = str_replace("files/bootanim/".str_replace(".zip", "", $_GET['bootanim'])."_audio/", "", $file);
				scriptadd('package_extract_file("system/media/audio/notifications/'.$filee.'", "/system/media/audio/notifications/'.$filee.'");');
				zipadd(array($file => "system/media/audio/notifications/".$filee));
			}
		}
	}
}

//Kernels
if ($settings['enabled']['kernel'])
{
	$kernel = $_GET['kernel'];
	if (isset($_GET['kernel']) and is_array($settings['kernel'][$kernel]))
	{
		scriptadd('ui_print("Flashing '.$settings['kernel'][$kernel]['name'].' Kernel/Boot Image");');
		scriptadd('package_extract_file("boot.img", "/tmp/boot.img");');
		scriptadd('run_program("/sbin/busybox", "dd", "if=/dev/zero", "of=/dev/block/mmcblk0p11");');
		scriptadd('run_program("/sbin/busybox", "dd", "if=/tmp/boot.img", "of=/dev/block/mmcblk0p11");');
		scriptadd('delete("/tmp/boot.img");');
		zipadd(array("files/kernel/".$_GET['kernel'] => "boot.img"));
		if (is_dir("files/kernel/".str_replace(".img","", $_GET['kernel'])."_ko"))
		{
			foreach(glob("files/kernel/".str_replace(".img","", $_GET['kernel'])."_ko/*.ko") as $file)
			{
				$filee = str_replace("files/kernel/".str_replace(".img","", $_GET['kernel'])."_ko/", "", $file);
				scriptadd('package_extract_file("system/lib/'.$filee.'", "/system/lib/'.$filee.'");');
				zipadd(array($file => "system/lib/".$filee));
			}
		}
	}
}

//Base Rom After Script
if ($settings['general']['baserom'])
{
	if (file_exists("files/baserom/after-script"))
	{
		scriptadd(file("files/baserom/after-script", FILE_SKIP_EMPTY_LINES));
	}
}
//Unmount Partitions
foreach ($settings['mount'] as $mpoint => $config)
{
	scriptadd('unmount("'.$mpoint.'");');
	scriptadd('ui_print("Unmounting '.$mpoint.'");');
}

//Create Zip

include('includes/functions.lib.php');
include('includes/zipcreate.cls.php');
if (!is_dir("zip/"))
{
	mkdir("zip/");
	chmod("zip/", 0777);
}
$filename = "zip/".date('m-d-y-his')."-".substr(sha1(rand().implode($_GET).time()), -6) .".zip";
$zip = new ZipCreate();
foreach ($filelist as $realpath => $zippath)
{
	if ($fp = fopen($realpath, 'rb'))
	{
		$contents = fread($fp, filesize($realpath));
		fclose($fp);
		$zip->add_file($contents, $zippath, filemtime($realpath));
	}
}
if ($fp = fopen("files/update-binary", 'rb'))
{
	$contents = fread($fp, filesize("files/update-binary"));
	fclose($fp);
	$zip->add_file($contents, "META-INF/com/google/android/update-binary", filemtime("files/update-binary"));
}
//Progress Bar Generation
$numlines = count($updatescript);
$num = bcdiv(1, $numlines / 5, 6);
$numm = $num;
$numarray = 0;
while ($numlines > 0)
{
	$numarray = $numarray + 5;
	$numlines = $numlines - 5;
	if ($numm >= 1)
	{
		$numm = 0.99;
	}
	array_splice($updatescript, $numarray ,0 ,"set_progress(\"".$numm."\");\n");
	$numm = bcadd($numm, $num, 6);
}

$zip->add_file(implode($updatescript), "META-INF/com/google/android/updater-script", time());
if ($fp = fopen($filename, 'wb'))
{
	fwrite($fp, $zip->build_zip());
	fclose($fp);
}
chmod($filename, 0777);
echo "<a id='download' name='download' href='".$filename."'>Download</a>";
?>
<?php
if(file_exists("debug"))
{
	if (file_exists($filename)) {
		echo "The file $filename exists";
	} else {
		echo "The file $filename does not exist";
	}
	echo "<pre>";
	print_r($_GET);
	echo "Update Script<br>";
	print_r($updatescript);
	echo "File List<br>";
	print_r($filelist);
	echo "</pre>";
}
?>
