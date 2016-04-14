<?php
//@fromfull => should be moved into a plugin
/* ------------------------------------------------------------------------- *
 *  Basic Shortcodes
/* ------------------------------------------------------------------------- */

/*  Columns / Grid
/* ------------------------------------ */
	function hu_column_shortcode($atts,$content=NULL) {
		extract( shortcode_atts( array(
			'size'	=> 'one-third',
			'last'	=> false
		), $atts) );

		$lastclass=$last?' last':'';
		$output='<div class="grid '.strip_tags($size).$lastclass.'">'.do_shortcode($content).'</div>';
		if($last)
			$output.='<div class="clear"></div>';
		return $output;
	}
	add_shortcode('column','hu_column_shortcode');

/*  Hr
/* ------------------------------------ */
	function hu_hr_shortcode($atts,$content=NULL) {
		$output = '<div class="hr"></div>';
		return $output;
	}
	add_shortcode('hr','hu_hr_shortcode');

/*  Highlight
/* ------------------------------------ */
	function hu_highlight_shortcode($atts,$content=NULL) {
		$output = '<span class="highlight">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('highlight','hu_highlight_shortcode');

/*  Dropcap
/* ------------------------------------ */
	function hu_dropcap_shortcode($atts,$content=NULL) {
		$output = '<span class="dropcap">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('dropcap','hu_dropcap_shortcode');

/*  Pullquote Left
/* ------------------------------------ */
	function hu_pullquote_left_shortcode($atts,$content=NULL) {
		$output = '<span class="pullquote-left">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('pullquote-left','hu_pullquote_left_shortcode');

/*  Pullquote Right
/* ------------------------------------ */
	function hu_pullquote_right_shortcode($atts,$content=NULL) {
		$output = '<span class="pullquote-right">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('pullquote-right','hu_pullquote_right_shortcode');