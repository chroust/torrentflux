<script type="text/javascript" src="js/mootools-1.2-core.js"></script>
<script type="text/javascript" src="js/mootools-1.2-more.js"></script>
<script type="text/javascript" src="js/Swiff.Uploader.js"></script>
<script type="text/javascript" src="js/Fx.ProgressBar.js"></script>
<script type="text/javascript" src="js/FancyUpload2.js"></script>

	<script type="text/javascript">
		/* <![CDATA[ */

window.addEvent('load', function() {

	var swiffy = new FancyUpload2($('demo-status'), $('demo-list'), {
		'url': $('form-demo').action,
		'fieldName': 'photoupload',
		'path': 'js/Swiff.Uploader.swf',
		'onLoad': function() {
			$('demo-status').removeClass('hide');
			$('demo-fallback').destroy();
		}
	});

	/**
	 * Various interactions
	 */

	$('demo-browse-all').addEvent('click', function() {
		swiffy.browse();
		return false;
	});

	$('demo-browse-images').addEvent('click', function() {
		swiffy.browse({'Images (*.jpg, *.jpeg, *.gif, *.png)': '*.jpg; *.jpeg; *.gif; *.png'});
		return false;
	});

	$('demo-clear').addEvent('click', function() {
		swiffy.removeFile();
		return false;
	});

	$('demo-upload').addEvent('click', function() {
		swiffy.upload();
		return false;
	});

});
		/* ]]> */
	</script>
<form action="/project/fancyupload/2-0/showcase/photoqueue/script.php" method="post" enctype="multipart/form-data" id="form-demo">
	<fieldset id="demo-fallback">
		<legend>File Upload</legend>
		<p>
			Selected your photo to upload.<br />

			<strong>This form is just an example fallback for the unobtrusive behaviour of FancyUpload.</strong>
		</p>
		<label for="demo-photoupload">
			Upload Photos:
			<input type="file" name="photoupload" id="demo-photoupload" />
		</label>
	</fieldset>

	<div id="demo-status" class="hide">
		<p>
			<a href="#" id="demo-browse-all">Browse Files</a> |
			<a href="#" id="demo-browse-images">Browse Only Images</a> |
			<a href="#" id="demo-clear">Clear List</a> |
			<a href="#" id="demo-upload">Upload</a>
		</p>
		<div>
			<strong class="overall-title">Overall progress</strong><br />
			<img src="../../assets/progress-bar/bar.gif" class="progress overall-progress" />
		</div>
		<div>
			<strong class="current-title">File Progress</strong><br />
			<img src="../../assets/progress-bar/bar.gif" class="progress current-progress" />
		</div>
		<div class="current-text"></div>

	</div>

	<ul id="demo-list"></ul>

</form>
