<?php

function js_event_register_ctrl_click_2_click_id(?string $key = null, ?string $id = null) : array
{
	static $registration = [];

	if ($key !== null) {
		if ($id !== null)
			$registration[$key] = $id;
		else
			unset($registration[$key]); }

	return $registration;	
}

function js_event_setup_scriptH()
{
	$registration_jsonH = json_encode(js_event_register_ctrl_click_2_click_id());
echo <<<EOS
	<script>
		(function() {
			var registration = $registration_jsonH;

			document.body.addEventListener('keydown', function(Ev) {
				if (event.ctrlKey || event.metaKey) {
					if (event.key in registration) {
						var El = document.getElementById(registration[event.key]);
						El.click();
						Ev.preventDefault(); } }
			}, true);

			for (var n in registration) {
				var El = document.getElementById(registration[n]);
				El.innerHTML += ' <kbd>[^' + n.toUpperCase() + ']</kbd>'
			}
		})();
	</script>
EOS;
}
