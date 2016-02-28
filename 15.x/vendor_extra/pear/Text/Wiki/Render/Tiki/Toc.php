<?php

// $Id: Toc.php 191168 2005-07-21 20:56:15Z justinpatrin $

class Text_Wiki_Render_Tiki_Toc extends Text_Wiki_Render {

	/**
	*
	* Renders a token into text matching the requested format.
	*
	* @access public
	*
	* @param array $options The "options" portion of the token (second
	* element).
	*
	* @return string The text rendered from the token options.
	*
	*/

	function token($options)
	{
		return "\n{maketoc}\n";
	}
}