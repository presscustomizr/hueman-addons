<?php
/* ------------------------------------------------------------------------- *
 *  Basic Shortcodes
/* ------------------------------------------------------------------------- */

/*  Columns / Grid
/* ------------------------------------ */
	function ha_column_shortcode($atts,$content=NULL) {
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
	add_shortcode('column','ha_column_shortcode');

/*  Hr
/* ------------------------------------ */
	function ha_hr_shortcode($atts,$content=NULL) {
		$output = '<div class="hr"></div>';
		return $output;
	}
	add_shortcode('hr','ha_hr_shortcode');

/*  Highlight
/* ------------------------------------ */
	function ha_highlight_shortcode($atts,$content=NULL) {
		$output = '<span class="highlight">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('highlight','ha_highlight_shortcode');

/*  Dropcap
/* ------------------------------------ */
	function ha_dropcap_shortcode($atts,$content=NULL) {
		$output = '<span class="dropcap">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('dropcap','ha_dropcap_shortcode');

/*  Pullquote Left
/* ------------------------------------ */
	function ha_pullquote_left_shortcode($atts,$content=NULL) {
		$output = '<span class="pullquote-left">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('pullquote-left','ha_pullquote_left_shortcode');

/*  Pullquote Right
/* ------------------------------------ */
	function ha_pullquote_right_shortcode($atts,$content=NULL) {
		$output = '<span class="pullquote-right">'.strip_tags($content).'</span>';
		return $output;
	}
	add_shortcode('pullquote-right','ha_pullquote_right_shortcode');