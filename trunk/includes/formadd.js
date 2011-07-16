var counter = 1;
function addApkInput(divName){
	var newdiv = document.createElement('div');
	newdiv.innerHTML += "APK Name: <input type='text' name='new" + counter + "[file]'><br>"
	newdiv.innerHTML += "Display Name: <input type='text' name='new" + counter +"[name]'>"
	newdiv.innerHTML += "<a onclick='this.parentNode.parentNode.removeChild(this.parentNode);' style='cursor:pointer;color:blue;'>Remove Field</a><br><br>";
	document.getElementById(divName).appendChild(newdiv);
	counter++;
}
function addMountInput(divName){
	var newdiv = document.createElement('div');
	newdiv.innerHTML += "Filesystem Type: <input type='text' name='new" + counter +"[fstype]'><br>"
	newdiv.innerHTML += "Partition Type(usually mtd or emmc): <input type='text' name='new" + counter +"[parttype]'><br>"
	newdiv.innerHTML += "Device Path(/dev): <input type='text' name='new" + counter +"[device]'><br>"
	newdiv.innerHTML += "Mount Point(folder): <input type='text' name='new" + counter + "[mpoint]'>"
	newdiv.innerHTML += "<a onclick='this.parentNode.parentNode.removeChild(this.parentNode);' style='cursor:pointer;color:blue;'>Remove Field</a><br><br>";
	document.getElementById(divName).appendChild(newdiv);
	counter++;
}
