<?php

echo '
	<form>
		<input type="hidden" name="set" value="post_wiki"/>
		<input type="hidden" name="slug" value="' .H($rcd['_url_slug']??$slug) .'"/>
		<input type="hidden" name="service" value="WikiPageEditor"/>
		<input type="hidden" name="selectionStart" value="' .H($selectionStart) .'"/>
		<input type="hidden" name="selectionEnd" value="' .H($selectionEnd) .'"/>
		<button name="form" id="xm" value="edit" class="strut-12">Edit <var>' .H($rcd['_url_slug']??$slug) .'</var></button>
	</form>' .
<<<'EOS'
	<script>
		if (window.location.hash === '#article-saved') {
			document.getElementById('xh').classList.add('saved-box');
			history.replaceState(null, null, ' '); }
		function handleCtrlEnterEdit(event) {
			if (event.ctrlKey || event.metaKey) {
				switch (event.key) {
				case 'e':
					event.preventDefault();
					document.getElementById('xm').click();
					break;
				default:
					return true; } }
		};
//		document.getElementsByTagName('html')[0].addEventListener('keydown', handleCtrlEnterEdit, false);
	</script>
EOS;

js_event_register_ctrl_click_2_click_id('e', 'xm');
