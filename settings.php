<?php
session_start();
error_reporting(E_ALL ^ (E_NOTICE));
include 'includes/jsonformat.php';
$password_sha1="";
if (file_exists("debug"))
{
	$password_sha1=" ";
	$_SESSION['loggedin'] = true;
}
if (empty($password_sha1))
{
	echo "Please set a password in this file.";
	exit;
}

if (!isset($_SESSION['loggedin']))
{
	if (!isset($_POST['pass']))
	{
		echo '<form name="pw" action="settings.php" method="post">'."\n";
		echo 'Password: <input type="password" name="pass">'."\n";
		echo '</form>'."\n";
		exit;
	}
	elseif (sha1($_POST['pass']) !== $password_sha1)
	{
		echo "Incorrect Password";
		echo '<form name="password" action="settings.php" method="post">'."\n";
		echo 'Password: <input type="text" name="pass">'."\n";
		echo '</form>'."\n";
		exit;
	}
	elseif (sha1($_POST['pass']) == $password_sha1)
	{
		$_SESSION['loggedin'] = true;
	}
}

if (!file_exists("config.json"))
{
	$setting = array("removeapk" => array(), "bootanim" => array(), "mod" => array(), "theme" => array(), "kernel" => array(), "enabled" => array(), "general" => array(), "mount" => array());
	file_put_contents("config.json", indent(json_encode($setting)));
}

echo '<script src="includes/formadd.js" type="text/javascript"></script>'."\n";
$settings = json_decode(file_get_contents("config.json"), true);
echo "<a href='settings.php'>Settings Index</a><br><br>\n";
switch ($_GET['section']) {
	case "removeapk":
		echo '<form name="removeapk" action="settings.php?section=removeapk" method="post">'."\n";
		if ($_POST)
		{
			$settings['removeapk'] = array();
			foreach ($_POST as $value)
			{
				$settings['removeapk'] = array_merge($settings['removeapk'], array($value['file'] => $value['name']));
			}
			file_put_contents("config.json", indent(json_encode($settings)));
		}
		echo "<div id='apk'>\n";
		foreach ($settings['removeapk'] as $file => $name)
		{
			echo "<div>APK Name: <input type='text' name='".$file."[file]' value='".$file."'/><br>\n";
			echo "Display Name: <input type='text' name='".$file."[name]' value='".$name."'/>\n";
			echo '<a onclick="this.parentNode.parentNode.removeChild(this.parentNode);" style="cursor:pointer;color:blue;">Remove Field</a><br>'."\n";
			echo "<br></div>\n";
		}
		echo "</div>\n";
		echo "<br>\n";
		echo '<input type="button" value="Add another text input" onClick=\'addApkInput("apk");\'>'."\n";
		echo '<input type="submit" value="Submit" />'."\n";
		echo "</form>\n";
		break;
	
	case "bootanim":
		echo '<form name="bootanim" action="settings.php?section=bootanim" method="post">'."\n";
		if ($_POST)
		{
			$settings['bootanim'] = array();
			foreach ($_POST as $key => $config)
			{
				$settings['bootanim'] = array_merge($settings['bootanim'], array(base64_decode($key) => array('name' => $config['name'], 'link' => $config['link'])));
			}
			file_put_contents("config.json", indent(json_encode($settings)));
			echo "<pre>\n";
			echo "</pre>\n";
		}
		$files = str_replace("files/bootanim/", "", glob("files/bootanim/*.zip"));
		foreach ($files as $file)
		{
			echo "<h3>".$file."</h3>\n";
			echo "Name: <input type='text' name='".base64_encode($file)."[name]' value='".$settings['bootanim'][$file]['name']."'/><br>\n";
			echo "Link (to forum post): <input type='text' name='".base64_encode($file)."[link]' value='".$settings['bootanim'][$file]['link']."'/><br>\n";
			echo "<br>\n";
			
		}
		echo '<input type="submit" value="Submit" />'."\n";
		echo "</form>\n";
		break;
	
	case "mod":
		echo '<form name="mod" action="settings.php?section=mod" method="post">'."\n";
		if ($_POST)
		{
			$settings['mod'] = array();
			foreach ($_POST as $key => $config)
			{
				file_put_contents("files/mod/".base64_decode($key)."/before-script", str_replace("\r", "", $config['beforescript']));
				file_put_contents("files/mod/".base64_decode($key)."/after-script", str_replace("\r", "", $config['afterscript']));
				$settings['mod'] = array_merge($settings['mod'], array(base64_decode($key) => array('name' => $config['name'], 'link' => $config['link'], 'removeodex' => $config['removeodex'])));
			}
			$jsettings = json_encode($settings);
			$jsettings = str_replace('"removeodex":"on"', '"removeodex":true', $jsettings);
			file_put_contents("config.json", indent($jsettings));
		}
		$folders = str_replace("files/mod/", "", array_filter(glob("files/mod/*"), 'is_dir'));
		foreach ($folders as $folder)
		{
			echo "<h3>".$folder."</h3>\n";
			echo "Name: <input type='text' name='".base64_encode($folder)."[name]' value='".$settings['mod'][$folder]['name']."'/><br>\n";
			echo "Link (to forum post): <input type='text' name='".base64_encode($folder)."[link]' value='".$settings['mod'][$folder]['link']."'/><br>\n";
			if ($settings['mod'][$folder]['removeodex'])
			{
				echo "Remove Odex: <input type='checkbox' name='".base64_encode($folder)."[removeodex]' checked/><br>\n";
			} else {
				echo "Remove Odex: <input type='checkbox' name='".base64_encode($folder)."[removeodex]' /><br>\n";
			}
			echo "<br>\n";
			echo "Before Script: <br>\n";
			echo "<textarea name='".base64_encode($folder)."[beforescript]' rows='10' cols='60'>\n";
			if (file_exists("files/mod/".$folder."/before-script"))
			{
				echo file_get_contents("files/mod/".$folder."/before-script");
			}
			echo "</textarea><br>\n";
			echo "After Script:<br>\n";
			echo "<textarea name='".base64_encode($folder)."[afterscript]' rows='10' cols='60'>\n";
			if (file_exists("files/mod/".$folder."/after-script"))
			{
				echo file_get_contents("files/mod/".$folder."/after-script");
			}
			echo "</textarea>\n";
			
		}
		echo '<input type="submit" value="Submit" />'."\n";
		echo "</form>\n";
		break;
	
	case "theme":
		echo '<form name="theme" action="settings.php?section=theme" method="post">'."\n";
		if ($_POST)
		{
			$settings['theme'] = array();
			foreach ($_POST as $key => $config)
			{
				file_put_contents("files/theme/".base64_decode($key)."/before-script", str_replace("\r", "", $config['beforescript']));
				file_put_contents("files/theme/".base64_decode($key)."/after-script", str_replace("\r", "", $config['afterscript']));
				$settings['theme'] = array_merge($settings['theme'], array(base64_decode($key) => array('name' => $config['name'], 'link' => $config['link'], 'removeodex' => $config['removeodex'])));
			}
			$jsettings = json_encode($settings);
			$jsettings = str_replace('"removeodex":"on"', '"removeodex":true', $jsettings);
			file_put_contents("config.json", indent($jsettings));
		}
		$folders = str_replace("files/theme/", "", array_filter(glob("files/theme/*"), 'is_dir'));
		foreach ($folders as $folder)
		{
			echo "<h3>".$folder."</h3>\n";
			echo "Name: <input type='text' name='".base64_encode($folder)."[name]' value='".$settings['theme'][$folder]['name']."'/><br>\n";
			echo "Link (to forum post): <input type='text' name='".base64_encode($folder)."[link]' value='".$settings['theme'][$folder]['link']."'/><br>\n";
			if ($settings['theme'][$folder]['removeodex'])
			{
				echo "Remove Odex: <input type='checkbox' name='".base64_encode($folder)."[removeodex]' checked/><br>\n";
			} else {
				echo "Remove Odex: <input type='checkbox' name='".base64_encode($folder)."[removeodex]' /><br>\n";
			}
			echo "<br>\n";
			echo "Before Script:<br>\n";
			echo "<textarea name='".base64_encode($folder)."[beforescript]' rows='10' cols='60'>\n";
			if (file_exists("files/theme/".$folder."/before-script"))
			{
				echo file_get_contents("files/theme/".$folder."/before-script");
			}
			echo "</textarea><br>\n";
			echo "After Script:<br>\n";
			echo "<textarea name='".base64_encode($folder)."[afterscript]' rows='10' cols='60'>\n";
			if (file_exists("files/theme/".$folder."/after-script"))
			{
				echo file_get_contents("files/theme/".$folder."/after-script");
			}
			echo "</textarea>\n";
			
		}
		echo '<input type="submit" value="Submit" />'."\n";
		break;
		
	case "kernel":
		echo '<form name="kernel" action="settings.php?section=kernel" method="post">'."\n";
		if ($_POST)
		{
			$settings['kernel'] = array();
			foreach ($_POST as $key => $config)
			{
				$settings['kernel'] = array_merge($settings['kernel'], array(base64_decode($key) => array('name' => $config['name'], 'link' => $config['link'])));
			}
			file_put_contents("config.json", indent(json_encode($settings)));
			echo "<pre>\n";
			echo "</pre>\n";
		}
		$files = str_replace("files/kernel/", "", glob("files/kernel/*.img"));
		foreach ($files as $file)

		{
			echo "<h3>".$file."</h3>\n";
			echo "Name: <input type='text' name='".base64_encode($file)."[name]' value='".$settings['kernel'][$file]['name']."'/><br>\n";
			echo "Link (to forum post): <input type='text' name='".base64_encode($file)."[link]' value='".$settings['kernel'][$file]['link']."'/><br>\n";
			echo "<br>\n";
			
		}
		echo '<input type="submit" value="Submit" />'."\n";
		break;
		
	case "general":
		echo '<form name="general" action="settings.php?section=general" method="post">'."\n";
		if ($_POST)
		{
			$settings['general'] = array();
			$settings['general'] = $_POST['general'];
			$jsettings = json_encode($settings);
			$jsettings = str_replace(':"on"', ':true', $jsettings);
			file_put_contents("config.json", indent($jsettings));
			echo "<pre>\n";
			echo "</pre>\n";
		}
		echo "Title: <input type='text' name='general[title]' value='".$settings['general']['title']."' /><br><br>\n";
		echo "Build Fingerprint: <input type='text' name='general[fingerprint]' value='".$settings['general']['fingerprint']."' /><br><br>\n";
		if ($settings['general']['baserom'])
		{
			echo "Use a Base Rom: <input type='checkbox' name='general[baserom]' checked/><br>\n";
		} else {
			echo "Use a Base Rom: <input type='checkbox' name='general[baserom]'/><br>\n";
		}
		echo '<input type="submit" value="Submit" />'."\n";
		echo "</form>\n";
		break;
		
	case "mount":
		echo '<form name="mount" action="settings.php?section=mount" method="post">'."\n";
		if ($_POST)
		{
			$settings['mount'] = array();
			foreach ($_POST as $value)
			{
				$settings['mount'] = array_merge($settings['mount'], array($value['mpoint'] => array('fstype' => $value['fstype'], 'parttype' => $value['parttype'], 'device' => $value['device'] )));
			}
			
			file_put_contents("config.json", indent(json_encode($settings)));
		}
		echo "<div id='mounts'>\n";
		foreach ($settings['mount'] as $mpoint => $config)
		{
			echo "Filesystem Type: <input type='text' name='".$mpoint."[fstype]' value='".$config['fstype']."'/><br>\n";
			echo "Partition Type(usually mtd or emmc): <input type='text' name='".$mpoint."[parttype]' value='".$config['parttype']."'/><br>\n";
			echo "Device Path(/dev): <input type='text' name='".$mpoint."[device]' value='".$config['device']."'/><br>\n";
			echo "<div>Mount Point(folder): <input type='text' name='".$mpoint."[mpoint]' value='".$mpoint."'/>\n";
			echo '<a onclick="this.parentNode.parentNode.removeChild(this.parentNode);" style="cursor:pointer;color:blue;">Remove Field</a><br>'."\n";
			echo "<br></div>\n";
		}
		echo "</div>\n";
		echo "<br>\n";
		echo '<input type="button" value="Add another text input" onClick=\'addMountInput("mounts");\'>'."\n";
		echo '<input type="submit" value="Submit" />'."\n";
		echo "</form>\n";
		break;
	
	
	default:
		if ($_POST and !isset($_POST['pass']))
		{
			$settings['enabled'] = array();
			$settings['enabled'] = $_POST['enabled'];
			$jsettings = json_encode($settings);
			$jsettings = str_replace(':"on"', ':true', $jsettings);
			file_put_contents("config.json", indent($jsettings));
		}
		echo '<form name="enabled" action="settings.php" method="post">'."\n";
		echo "Use checkboxes to enable or disable sections<br><br>\n";
		echo "<a href='settings.php?section=general'>General Settings</a><br>\n";
		echo "<a href='settings.php?section=mount'>Mounts</a><br>\n";
		
		if ($settings['enabled']['removeapk'])
		{
			echo "<input type='checkbox' name='enabled[removeapk]' checked/>\n";
		} else {
			echo "<input type='checkbox' name='enabled[removeapk]' />\n";
		}
		echo "<a href='settings.php?section=removeapk'>Remove Bloatware</a><br>\n";
		
		if ($settings['enabled']['bootanim'])
		{
			echo "<input type='checkbox' name='enabled[bootanim]' checked/>\n";
		} else {
			echo "<input type='checkbox' name='enabled[bootanim]' />\n";
		}
		echo "<a href='settings.php?section=bootanim'>Boot Animations</a><br>\n";
		
		if ($settings['enabled']['mod'])
		{
			echo "<input type='checkbox' name='enabled[mod]' checked/>\n";
		} else {
			echo "<input type='checkbox' name='enabled[mod]' />\n";
		}
		echo "<a href='settings.php?section=mod'>Mods</a><br>\n";
		
		if ($settings['enabled']['theme'])
		{
			echo "<input type='checkbox' name='enabled[theme]' checked/>\n";
		} else {
			echo "<input type='checkbox' name='enabled[theme]' />\n";
		}
		echo "<a href='settings.php?section=theme'>Themes</a><br>\n";
		
		if ($settings['enabled']['kernel'])
		{
			echo "<input type='checkbox' name='enabled[kernel]' checked/>\n";
		} else {
			echo "<input type='checkbox' name='enabled[kernel]' />\n";
		}
		echo "<a href='settings.php?section=kernel'>Kernels/Boot Images</a><br>\n";
		echo '<input type="submit" value="Submit" />'."\n";
		
}
?>
<?php
/*
echo "<pre>\n";
echo "<br>------------------------<br>\n";
print_r($_POST);

print_r($settings);
*/
?>
