<?php
error_reporting(E_ALL ^ (E_NOTICE));
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
/*function shadd($var)
{
	global $shscript;
	if (is_array($var))
	{
		$shscript = array_merge($shscript, $var);
	} else {
		$shscript[] = $var."\n";
	}
}*/
echo "<h2>".$settings['general']['title']."</h2>";
echo "<form name='input' action='kitchen.php' method='get'>\n";
//var_dump($settings['removeapk']);
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
		if (isset($settings['mod'][$folder]['link']))
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
/*scriptadd('mount("ext3", "system", "/system");');
scriptadd('ui_print("Mounting /system");');
scriptadd('mount("ext3", "userdata", "/data");');
scriptadd('ui_print("Mounting /data");');*/
foreach ($settings['mount'] as $mpoint => $config)
{
	scriptadd('mount("'.$config['fstype'].'", "'.$config['parttype'].'", "'.$config['device'].'", "'.$mpoint.'");');
}
scriptadd('assert(file_getprop("/system/build.prop", "ro.build.fingerprint") == "'.$settings['general']['fingerprint'].'");');
/*if (!count($shscript))
{
	scriptadd('package_extract_file("script.sh", "/data/local/tmp/script.sh");');
	scriptadd('set_perm(0, 0, 0755, "/data/local/tmp/script.sh");');
	scriptadd('run_program("/data/local/tmp/script.sh");');
}*/
scriptadd('show_progress(1.0, 0);');

//Base Rom

$dir = "files/baserom";
//echo $dir."<br>";
scriptadd('ui_print("Installing Base Rom");');
//$script = explode("\r\n", $settings['mod'][$folder]['script']);
//scriptadd($script); 
$it = new RecursiveDirectoryIterator($dir);
$iterator = new RecursiveIteratorIterator($it);

foreach(new RecursiveIteratorIterator($it) as $file) {
	//echo 'set_progress('.$numm.');'."\n";
	$file = str_replace($dir, "", $file);
	$file = str_replace("/.", "ikodslghjkd", $file);
	//echo $file."<br>";
	if ($file == "/custom-script")
	{
		scriptadd(file($dir.$file, FILE_SKIP_EMPTY_LINES));
	} 
	elseif (!strstr($file, "ikodslghjkd") and !strstr($file, "ikodslghjkd"))
	{
		scriptadd('package_extract_file("'.substr($file, 1).'", "'.$file.'");');
		zipadd(array($dir.$file => substr($file, 1)));
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

//Boot Animations
if ($settings['enabled']['bootanim'])
{
	if (isset($_GET['bootanim']) and is_array($settings['bootanim'][$bootanim]))
	{
		$bootanim = $_GET['bootanim'];
		scriptadd('ui_print("Installing '.$settings['bootanim'][$bootanim]['name'].' Boot Animation");');
		scriptadd('package_extract_file("system/media/bootanimation.zip", "/system/media/bootanimation.zip");');
		zipadd(array("files/bootanim/".$_GET['bootanim'] => "system/media/bootanimation.zip"));
	}
}

//Mods
if ($settings['enabled']['mod'])
{
	if (isset($_GET['mod']))
	{
		foreach($_GET['mod'] as $folder => $value)
		{
			//$settings['mod'][$name]
			if (!is_array($settings['mod'][$folder]))
			{
				continue;
			}
			$dir = "files/mod/".$folder;
			//echo $dir."<br>";
			scriptadd('ui_print("Installing '.$settings['mod'][$folder]['name'].' Mod");');
			$it = new RecursiveDirectoryIterator($dir);
			$iterator = new RecursiveIteratorIterator($it);
			/*$numfiles = iterator_count($iterator);
			$num = bcdiv(1, $numfiles, 6);
			$numm = $num;*/
			foreach(new RecursiveIteratorIterator($it) as $file) {
				//echo 'set_progress('.$numm.');'."\n";
				$file = str_replace($dir, "", $file);
				$file = str_replace("/.", "ikodslghjkd", $file);
				//echo $file."<br>";
				if ($file == "/custom-script")
				{
					scriptadd(file($dir.$file, FILE_SKIP_EMPTY_LINES));
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
			//$numm = bcadd($numm, $num, 6);
			}
		}
	}
}

//Themes
if ($settings['enabled']['theme'])
{
	$folder = $_GET['theme'];
	if (isset($_GET['theme']) and is_array($settings['bootanim'][$bootanim]))
	{
		//$settings['theme'][$name]
		$dir = "files/theme/".$folder;
		//echo $dir."<br>";
		scriptadd('ui_print("Installing '.$settings['theme'][$folder]['name'].' Theme");');
		$it = new RecursiveDirectoryIterator($dir);
		$iterator = new RecursiveIteratorIterator($it);
		/*$numfiles = iterator_count($iterator);
		$num = bcdiv(1, $numfiles, 6);
		$numm = $num;*/
		foreach(new RecursiveIteratorIterator($it) as $file) {
			//echo 'set_progress('.$numm.');'."\n";
			$file = str_replace($dir, "", $file);
			$file = str_replace("/.", "ikodslghjkd", $file);
			if ($file == "/custom-script")
			{
				scriptadd(file($dir.$file, FILE_SKIP_EMPTY_LINES));
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
			//$numm = bcadd($numm, $num, 6);
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
		scriptadd('assert(package_extract_file("boot.img", "/tmp/boot.img"),');
		scriptadd('write_raw_image("/tmp/boot.img", "boot"),');
		scriptadd('delete("/tmp/boot.img"));');
		zipadd(array("files/kernel/".$_GET['kernel'] => "boot.img"));
	}
}

//Unmount Partitions
scriptadd('unmount("/data");');
scriptadd('unmount("/system");');

//Create Zip

include('includes/functions.lib.php');
include('includes/zipcreate.cls.php');
$filename = "zip/".date('m-d-y-h:i:s')."-".substr(sha1(rand().implode($_POST).time()), -6) .".zip";
$zip = new ZipCreate();
foreach ($filelist as $realpath => $zippath)
{
	if ($fp = fopen($realpath, 'rb'))
	{
		$contents = fread($fp, filesize($realpath));
		fclose($fp);
		//echo filemtime($realpath)."<br>";
		$zip->add_file($contents, $zippath, filemtime($realpath));
	}
}
if ($fp = fopen("files/update-binary", 'rb'))
{
	$contents = fread($fp, filesize($realpath));
	fclose($fp);
	$zip->add_file($contents, "META-INF/com/google/android/update-binary", filemtime($realpath));
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
	array_splice($updatescript, $numarray ,0 ,"set_progress('".$numm."');\n");
	$numm = bcadd($numm, $num, 6);
}

$zip->add_file(implode($updatescript), "META-INF/com/google/android/updater-script", time());
//$zip->add_file(implode($shscript), "script.sh", time());
//echo "numfiles: " . $zip->numFiles . "\n";
//echo "status:" . $zip->status . "\n";
//$zip->close();
if ($fp = fopen($filename, 'wb'))
{
	fwrite($fp, $zip->build_zip());
	fclose($fp);
}
echo "<a href='".$filename."'>Download</a>";
?>
<?php
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
echo "Sh Script<br>";
print_r($shscript);
echo "</pre>";
?>
