if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('en_US', {
		'VALIDATOR.FIELDREQUIRED': 'Please fill out "%s", it is required.',
		'HASMANYFILEFIELD.UPLOADING': 'Uploading... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Are you sure you want to delete this record?',
		'LOADING': 'loading...',
		'UNIQUEFIELD.SUGGESTED': "Changed value to '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'You will need to enter a new value for this field',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'This field cannot be left empty',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "The character '%s' cannot be used in this field",
		'UPDATEURL.CONFIRM': 'Would you like me to change the URL to:\n\n%s/\n\nClick Ok to change the URL, click Cancel to leave it as:\n\n%s',
		'FILEIFRAMEFIELD.DELETEFILE': 'Delete File',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Un-Attach File',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Delete Image',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Are you sure you want to delete this file?',
		'LeftAndMain.IncompatBrowserWarning': 'Your browser is not compatible with the CMS interface. Please use Internet Explorer 7+, Google Chrome 10+ or Mozilla Firefox 3.5+.',
		'GRIDFIELD.ERRORINTRANSACTION': 'An error occured while fetching data from the server\n Please try again later.',
		'UploadField.ConfirmDelete': 'Are you sure you want to remove this file from the server filesystem?',
		'UploadField.PHP_MAXFILESIZE': 'File exceeds upload_max_filesize (php.ini directive)',
		'UploadField.HTML_MAXFILESIZE': 'File exceeds MAX_FILE_SIZE (HTML form directive)',
		'UploadField.ONLYPARTIALUPLOADED': 'File was only partially uploaded',
		'UploadField.NOFILEUPLOADED': 'No File was uploaded',
		'UploadField.NOTMPFOLDER': 'Missing a temporary folder',
		'UploadField.WRITEFAILED': 'Failed to write file to disk',
		'UploadField.STOPEDBYEXTENSION': 'File upload stopped by extension',
		'UploadField.TOOLARGE': 'Filesize is too large',
		'UploadField.TOOSMALL': 'Filesize is too small',
		'UploadField.INVALIDEXTENSION': 'Extension is not allowed',
		'UploadField.MAXNUMBEROFFILESSIMPLE': 'Max number of files exceeded',
		'UploadField.UPLOADEDBYTES': 'Uploaded bytes exceed file size',
		'UploadField.EMPTYRESULT': 'Empty file upload result',
		'UploadField.LOADING': 'Loading ...'
	});
}
