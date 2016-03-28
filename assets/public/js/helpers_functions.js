/*
Convert string to Boolean
http://stackoverflow.com/a/21445227
 */
function stringToBool(val) {
	return (val + '').toLowerCase() === 'true';
}

/*
Clear the file input
http://stackoverflow.com/a/24608023/1414881
 */
function clearFileInput(f) {
	if (f.value) {
		try {
			f.value = ''; //for IE11, latest Chrome/Firefox/Opera...
		} catch (err) {}
		if (f.value) { //for IE5 ~ IE10
			var form = document.createElement('form'),
				parentNode = f.parentNode,
				ref = f.nextSibling;
			form.appendChild(f);
			form.reset();
			parentNode.insertBefore(f, ref);
		}
	}
}