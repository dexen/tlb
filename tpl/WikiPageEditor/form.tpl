<?php

	echo '<form method="post" action="?set=post_wiki&amp;slug=', HU($slug) ,'&amp;service=WikiPageEditor&amp;form=edit" enctype="multipart/form-data" ' ?>

		onkeydown="
			if (event.key === 'Escape') {
				var TA = document.getElementById('xa');
				if (TA.selectionStart != TA.selectionEnd) {
					var next = TA.selectionStart-1;
					TA.value = TA.value.slice(0, TA.selectionStart-1) + TA.value.slice(TA.selectionEnd);
					TA.selectionEnd = TA.selectionStart = next; }
				else
					TA.selectionStart = document.getElementById('x1').value; }
		"

		onsubmit="
			document.getElementById('xa').classList.add('inputAwaitingSave');
			document.getElementById('xa').readOnly = true;
			document.getElementById('x1').value = document.getElementById('xa').selectionStart;
			document.getElementById('x9').value = document.getElementById('xa').selectionEnd;
			return true;
		"

	<?php
		echo '>';

		echo '<article>';
		echo '<h1><a href="?set=post_wiki"><img
			alt="TlbInstance at ' .H(tlb_address()) .'"
			src="visual-hash-png.php?size=32&amp;id=' .HU(tlb_address_id()) .'"
			srcset="visual-hash-png.php?size=64&amp;id=' .HU(tlb_address_id()) .' 2x,
				visual-hash-png.php?size=96&amp;id=' .HU(tlb_address_id()) .' 3x,
				visual-hash-png.php?size=128&amp;id=' .HU(tlb_address_id()) .' 4x"
			width="32" height="32"/></a> ' .H(wiki_slug_to_title($slug)) .'</h1>';

		echo '<input id="x1" type="hidden" name="meta[selectionStart]" value="' .H($_GET['selectionStart']??null) .'"/>';
		echo '<input id="x9" type="hidden" name="meta[selectionEnd]" value="' .H($_GET['selectionEnd']??null) .'"/>';

		echo '<textarea id="xa" name="data[body]" style="width: 100%" ';
		echo ' rows="', H($textarea_rows), '">', H($rcd['body']??null), '</textarea>';
		echo '</article>';

		echo '<input type="hidden" name="original[body]" value="' .H($rcd['body']??null) .'"/>';

		echo '<p style="text-align: left">';
			echo '<label>Compare local page: <input name="ctx[local_page]" value="' .H($ctx['local_page']??null) .'"/></label>';
		echo '</p>';

		echo '<p style="text-align: right">
			<button type="submit" id="xs" name="action" value="save-edit" class="strut-6 strut-right">Save &amp; keep editing</var></button>
		</p>';

		echo '<p>
			<button type="submit" id="xe" name="action" value="save-see" class="strut-12">Save <var>' .H($slug) .'</var></button>
		</p>';
	echo '</form>';
	echo <<<'EOS'
		<script>
			if (window.location.hash === '#article-saved-continue') {
				document.getElementById('xa').classList.add('inputCompletedSave');
				history.replaceState(null, null, ' '); }

			document.getElementById('xa').focus();
			document.getElementById('xa').setSelectionRange(-1, -1);
			if (String(document.getElementById('x1').value).length)
			document.getElementById('xa').setSelectionRange(
				document.getElementById('x1').value,
				document.getElementById('x9').value );

		</script>
EOS;

js_event_register_ctrl_click_2_click_id('e', 'xe');
js_event_register_ctrl_click_2_click_id('s', 'xs');
