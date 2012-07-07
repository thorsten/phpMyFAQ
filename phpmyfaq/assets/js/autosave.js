$(document).ready(function() {
	/* XXX autosave feature should be controllable through admin */
	/* XXX interval time should be configurable through admin */

	$(window).unload(function() {
		//if (tinyMCE.activeEditor.isDirty()) {
			var chk = confirm('Do you want to save the article before navigating away?');

			if (chk) {
				pmf_auto_save();
			}
		//}
	});

	setInterval('pmf_auto_save();', 10000);
});

function pmf_auto_save()
{
	var ed = tinyMCE.activeEditor;
	if (ed.isDirty()) {
		var content = ed.getContent();
		/* XXX need ajax here */
		console.log('autosaved');
		ed.isNotDirty = true;
	}
}

