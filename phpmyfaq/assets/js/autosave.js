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
		var formData = {};
		formData.revision_id = $('#revision_id').attr('value');
		formData.record_id = $('#record_id').attr('value');
		formData.csrf = $('#csrf').attr('value');
		formData.openQuestionId = $('#openQuestionId').attr('value');
		formData.question = $('#question').attr('value');
		formData.answer = ed.getContent();
		formData.keywords = $('#keywords').attr('value');
		formData.tags = $('#tags').attr('value');
		formData.author = $('#author').attr('value');
		formData.email = $('#email').attr('value');
		formData.lang = $('#lang').attr('value');
		formData.solution_id = $('#solution_id').attr('value');

		$.ajax({
			url: pmf_auto_save_action(),
			type: 'POST',
			data: formData,
			success: function(msg) {
				$('#saving_data_indicator').html('Item was autosaved at rev ' + msg);
				ed.isNotDirty = true;
			}
		});
	}
}

function pmf_auto_save_action()
{
	var act;
	var fa = $('#faqEditor').attr('action');

	act = '?action=ajax&ajax=autosave&' + fa.substr(1).replace(/action=/, 'do=');

	return act;
}

