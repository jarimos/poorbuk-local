<?php

class PeepSoShare
{
	/*
	 * $share_links = array(
	 *		<SHARING_SERVICE> => array(
	 *			'icon' => <URL OF THE ICON TO USE>,
	 *			'url'  => <Share URL, need to have --peepso-url--, this will be replaced by the actual URL
	 *						to be shared>
	 *		);
	 * );
	 */
	private $share_links = array();

	private static $_instance = NULL;

	// list of allowed template tags
	public $template_tags = array(
		'show_links',		// display social sharing links
	);

	private function __construct()
	{
	}

	/*
	 * return singleton instance
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}

	/*
	 * Returns the social sharing links as an array
	 * @return array The sharing links
	 */
	public function get_links()
	{
		$this->share_links = array(
			'facebook' => array(
				'label' => 'Facebook',
				'icon' => 'facebook',
				'url'  => 'http://www.facebook.com/sharer.php?u=--peepso-url--'
			),
			'delicious' => array(
				'label' => 'del.icio.us',
				'icon' => 'delicious',
				'url'  => 'https://delicious.com/save?url=--peepso-url--'
			),
			'digg' => array(
				'label' => 'Digg',
				'icon' => 'digg',
				'url'  => 'http://digg.com/submit?phase=2&url=--peepso-url--'
			),
			'stumbleupon' => array(
				'label' => 'StumbleUpon',
				'icon' => 'stumbleupon',
				'url'  => 'https://www.stumbleupon.com/submit?url=--peepso-url--'
			),
			'blinklist' => array(
				'label' => 'Blinklist',
				'icon' => 'blinklist',
				'url'  => 'http://blinklist.com/blink?u=--peepso-url--'
			),
			'google_plus' => array(
				'label' => 'Google+',
				'icon' => 'googleplus',
				'url'  => 'https://plus.google.com/share?url=--peepso-url--'
			),
			'diigo' => array(
				'label' => 'Diigo',
				'icon' => 'diigo',
				'url'  => 'https://www.diigo.com/post?url=--peepso-url--'
			),
			'myspace' => array(
				'label' => 'Myspace',
				'icon' => 'myspace',
				'url'  => 'https://myspace.com/post?l=2&u=--peepso-url--'
			),
			'twitter' => array(
				'label' => 'Twitter',
				'icon' => 'twitter',
				'url'  => 'https://twitter.com/share?url=--peepso-url--'
			),
			'blogmarks' => array(
				'label' => 'Blogmarks',
				'icon' => 'blogmarks',
				'url'  => 'http://blogmarks.net/my/new.php?mini=1&url=--peepso-url--'
			),
			'lifestream' => array(
				'label' => 'Lifestream',
				'icon' => 'lifestream',
				'url'  => 'http://lifestream.aol.com/share/?url=--peepso-url--'
			),
			'linkedin' => array(
				'label' => 'LinkedIn',
				'icon' => 'linkedin',
				'url'  => 'http://www.linkedin.com/shareArticle?mini=true&url=--peepso-url--&source=' . urlencode(get_bloginfo('name'))
			),
			'newsvine' => array(
				'label' => 'Newsvine',
				'icon' => 'newsvine',
				'url'  => 'http://www.newsvine.com/_tools/seed&save?popoff=0&u=--peepso-url--'
			),
			'google_bookmarks' => array(
				'label' => 'Google Bookmarks',
				'icon' => 'google',
				'url'  => 'http://www.google.com/bookmarks/mark?op=edit&bkmk=--peepso-url--'
			),
		);

		return apply_filters('peepso_share_links', $this->share_links);
	}

	/*
	 * Template callback for display share links
	 */
	public function show_links()
	{
		echo '<div class="ps-list ps-list--share">', PHP_EOL;
		foreach ($this->get_links() as $link) {
			echo	'<a class="ps-list__item" href="', $link['url'], '" target="_blank">', PHP_EOL;
			echo		'<span class="ps-share__icon ps-share__icon--', $link['icon'], '">', $link['label'], '</span>', PHP_EOL;
			echo	'</a>', PHP_EOL;
		} 
		echo '</div>', PHP_EOL;
	}
}

// EOF