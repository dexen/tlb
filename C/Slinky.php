<?php

class Slinky extends dexen\mulib\Slinky
{
	function _urlPathPrefix() : ?string
	{
		return parse_url(tlb_address(), PHP_URL_PATH);
	}
}
 