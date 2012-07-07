/**
 * Autosave functionality javascript part
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   JavaScript
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-07-07
 */

$(document).ready(function() {
	/* XXX autosave feature should be controllable through admin */
	/* XXX interval time should be configurable through admin */

	$(window).unload(function() {
		//if (tinyMCE.activeEditor.isDirty()) {
			var chk = confirm('Do you want to save the article before navigating away?');

			if (chk) {
				pmfAutosave();
			}
		//}
	});

	setInterval('pmfAutosave();', pmfAutosaveInterval*1000);
});

/**
 * Post autosave data via AJAX.
 *
 * @return void
 */
function pmfAutosave() {
	var ed = tinyMCE.activeEditor;
	if (ed.isDirty()) {
		var formData = {};
		formData.revision_id = $('#revision_id').attr('value');
		formData.record_id = $('#record_id').attr('value');
		formData.csrf = $('[name="csrf"]').attr('value');
		formData.openQuestionId = $('#openQuestionId').attr('value');
		formData.question = $('#question').attr('value');
		formData.answer = ed.getContent();
		formData.keywords = $('#keywords').attr('value');
		formData.tags = $('#tags').attr('value');
		formData.author = $('#author').attr('value');
		formData.email = $('#email').attr('value');
		formData.lang = $('#lang').attr('value');
		formData.solution_id = $('#solution_id').attr('value');
		formData.active = $('input:checked:[name="active"]').attr('value');
		formData.sticky = $('#sticky').attr('value');
		formData.comment = $('#comment').attr('value');
		formData.grouppermission = $('[name="grouppermission"]').attr('value');
		formData.userpermission = $('[name="userpermission"]').attr('value');
		formData.restricted_users = $('[name="restricted_users"]').attr('value');
		formData.dateActualize = $('#dateActualize').attr('value');
		formData.dateKeep = $('#dateKeep').attr('value');
		formData.dateCustomize = $('#dateCustomize').attr('value');
		formData.date = $('#date').attr('value');

		$.ajax({
			url: pmfAutosaveAction(),
			type: 'POST',
			data: formData,
			success: function(r) {
				var resp = $.parseJSON(r);

				$('#saving_data_indicator').html(resp.msg);
				
				ed.isNotDirty = true;

				$('#record_id').attr('value', resp.record_id);
				$('#revision_id').attr('value', resp.revision_id);
				/* XXX update more places on the page according to the new saved data */
			}
		});
	}
}

/**
 * Produce AJAX autosave action.
 *
 * @return string
 */
function pmfAutosaveAction() {
	var act;
	var fa = $('#faqEditor').attr('action');

	act = '?action=ajax&ajax=autosave&' + fa.substr(1).replace(/action=/, 'do=');

	return act;
}

function getYesNoVal(selector) {
	
}

