<?php

namespace Friends;

class ExtractedPage {
	public $title;
	public $content;
	public $url;
	public $author;
	public $post_format = 'standard';

	/**
	 * ExtractedPage constructor.
	 *
	 * @param string $url
	 * @param string $title
	 * @param string $content
	 */
	public function __construct( $url, $title = '', $content = '' ) {
		$this->title = $title;
		$this->content = $content;
		$this->url = $url;
	}
}
