<?php

class Slinky
{
	protected $base;
	protected $data = [];
	protected $fragment;

	function __construct(string $base)
	{
		$this->base = $base;
	}

	function _urlScheme() : ?string
	{
		return null;
	}

	function _urlAuthority() : ?string
	{
		return null;
	}

	function _urlPathPrefix() : ?string
	{
		return '/';
	}

	function withBase(string $base) : self
	{
		$ret = clone $this;
		$ret->base = $base;
		return $ret;
	}

	function __toString()
	{
		$ret = '';

		if ($this->_urlScheme() !== null)
			$ret .= $this->_urlScheme() .':';

		if ($this->_urlAuthority() !== null)
			$ret .= '//' .$this->_urlAuthority();

		$ret .= $this->_urlPathPrefix() .rawurlencode($this->base);
		$q = http_build_query($this->data);
		if ($q !== '')
			$ret .= '?' .$q;
		if (($this->fragment !== null) && ($this->fragment !== ''))
			$ret .= '#' .rawurlencode($this->fragment);
		return $ret;
	}

	function withFragment(string $str) : self
	{
		$ret = clone $this;
		$ret->fragment = $str;
		return $ret;
	}

	function toHiddenInputsH() : string
	{
		if (($this->fragment !== null) && ($this->fragment !== ''))
			throw new LogicException('cannot put url fragment in hidden fields');

		$ret = '';
		foreach ($this->data as $k => $v)
			$ret .= '<input name="' .H($k) .'" value="' .H($v) .'" type="hidden"/>';
		return $ret;
	}

	function with(array $a) : self
	{
		$ret = clone $this;
		$ret->data = array_merge($ret->data, $a);
		return $ret;
	}

	function dataEqualsP(string $key, $value) : bool
	{
		if (!array_key_exists($key, $this->data))
			throw new LogicException(sprintf('no such data item: "%s"', $key));
		return $this->data[$key] === $value;
	}

		# a temporary redirect
		# action success, use GET to retrieve response
	function redirectSeeOther()
	{
		$http_see_other = 303;
		header('Location: ' .$this, $replace = true, $http_see_other);
		exit();
	}

		# use the same HTTP method on a different URL
	function temporaryRedirect()
	{
		$http_temporary_redirect = 307;
		header('Location: ' .$this, $replace = true, $http_temporary_redirect);
		exit();
	}
}
