<?php

	function url_video_embedify($link){
		if (preg_match('/https:\/\/www.youtube.com\/watch\?v=(\w+)/', $link, $matches)) {
			$video_id = $matches[1];
			$iframe_src = 'https://www.youtube-nocookie.com/embed/' . $video_id;
				
		}
		if ($iframe_src){
			$embed = '<div class="embed-responsive embed-responsive-16by9">';
			$embed .= '<iframe class="embed-responsive-item" src="' . $iframe_src . '" frameborder="0" allowfullscreen></iframe>';
			$embed .= '</div>';
			return $embed;
		}
		return '';
	}

	########################################################################

	# Adapted from https://bitbucket.org/kwi/urllinker

	function url_linker($text){

		$regex = url_linker_regex();
		$valid_tlds = url_linker_tlds();

		$html = '';
		$position = 0;
		$match = array();

		while (preg_match($regex, $text, $match, PREG_OFFSET_CAPTURE, $position)){
			list($url, $url_pos) = $match[0];

			// Add the text leading up to the URL.
			$html .= htmlspecialchars(substr($text, $position, $url_pos - $position));

			$scheme       = $match[1][0];
			$username     = $match[2][0];
			$password     = $match[3][0];
			$domain       = $match[4][0];
			$after_domain = $match[5][0]; // everything following the domain
			$port         = $match[6][0];
			$path         = $match[7][0];

			// Check that the TLD is valid or that $domain is an IP address.
			$tld = strtolower(strrchr($domain, '.'));

			if (preg_match('{^\.[0-9]{1,3}$}', $tld) ||
			    $valid_tlds[$tld]){
				// Do not permit implicit scheme if a password is specified, as
				// this causes too many errors (e.g. "my email:foo@example.org").
				if (! $scheme && $password){

					$html .= htmlspecialchars($username);

					// Continue text parsing at the ':' following the "username".
					$position = $url_pos + strlen($username);

					continue;
				}

				if (! $scheme && $username &&
				    ! $password && ! $after_domain){
					// Looks like an email address.
					$complete_url = "mailto:$url";
					$link_text = $url;
				} else {
					// Prepend http:// if no scheme is specified
					$complete_url = $scheme ? $url : "http://$url";
					$link_text = "$domain$port$path";
				}

				// Add the hyperlink.
				$html .= url_linker_link($complete_url, $link_text);
			} else {
				// Not a valid URL.
				$html .= htmlspecialchars($url);
			}

			// Continue text parsing from after the URL.
			$position = $url_pos + strlen($url);
		}

		// Add the remainder of the text.
		$html .= htmlentities(substr($text, $position));

		return $html;
	}

	########################################################################

	function url_linker_link($url, $text){
		$url = htmlspecialchars($url);
		if (strlen($text) > 32){
			$text = substr($text, 0, 32) . '...';
		}
		$text = htmlspecialchars($text);
		return '<a href="' . $url . '">' . $text . '</a>';
	}

	########################################################################

	function url_linker_regex(){
		$scheme        = 'https?://';
		$domain        = '(?:[-a-zA-Z0-9\x7f-\xff]{1,63}\.)+[a-zA-Z\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{1,62}';
		$ip            = '(?:[1-9][0-9]{0,2}\.|0\.){3}(?:[1-9][0-9]{0,2}|0)';
		$port          = '(:[0-9]{1,5})?';
		$path          = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
		$query         = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
		$fragment      = '(#[!$-/0-9?:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
		$username      = '[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64}';
		$password      = $rexUsername; // allow the same characters as in the username
		$url           = "($scheme)?(?:($username)(:$password)?@)?($domain|$ip)($port$path$query$fragment)";
		$trailing_punc = "[)'?.!,;:]"; // valid URL characters which are not part of the URL if they appear at the very end
		$non_url_chars = "[^-_#$+.!*%'(),;/?:@=&a-zA-Z0-9\x7f-\xff]"; // characters that should never appear in a URL
		$regex         = "{\\b$url(?=$trailing_punc*($non_url_chars|$))}i";

		return $regex;
	}

	########################################################################

	function url_linker_tlds(){

		if ($GLOBALS['cfg']['url_linker_tlds']){
			return $GLOBALS['cfg']['url_linker_tlds'];
		}

		# http://data.iana.org/TLD/tlds-alpha-by-domain.txt
		$tld_list = '.ac .academy .accountants .actor .ad .ae .aero .af .ag .agency .ai .airforce .al .am .an .ao .aq .ar .archi .army .arpa .as .asia .associates .at .attorney .au .audio .autos .aw .ax .axa .az .ba .bar .bargains .bayern .bb .bd .be .beer .berlin .best .bf .bg .bh .bi .bid .bike .bio .biz .bj .black .blackfriday .blue .bm .bn .bo .boutique .br .bs .bt .build .builders .buzz .bv .bw .by .bz .ca .cab .camera .camp .capital .cards .care .career .careers .cash .cat .catering .cc .cd .center .ceo .cf .cg .ch .cheap .christmas .church .ci .citic .ck .cl .claims .cleaning .clinic .clothing .club .cm .cn .co .codes .coffee .college .cologne .com .community .company .computer .condos .construction .consulting .contractors .cooking .cool .coop .country .cr .credit .creditcard .cruises .cu .cv .cw .cx .cy .cz .dance .dating .de .degree .democrat .dental .dentist .desi .diamonds .digital .directory .discount .dj .dk .dm .dnp .do .domains .dz .ec .edu .education .ee .eg .email .engineer .engineering .enterprises .equipment .er .es .estate .et .eu .eus .events .exchange .expert .exposed .fail .farm .feedback .fi .finance .financial .fish .fishing .fitness .fj .fk .flights .florist .fm .fo .foo .foundation .fr .frogans .fund .furniture .futbol .ga .gal .gallery .gb .gd .ge .gf .gg .gh .gi .gift .gives .gl .glass .globo .gm .gmo .gn .gop .gov .gp .gq .gr .graphics .gratis .gripe .gs .gt .gu .guide .guitars .guru .gw .gy .hamburg .haus .hiphop .hiv .hk .hm .hn .holdings .holiday .homes .horse .host .house .hr .ht .hu .id .ie .il .im .immobilien .in .industries .info .ink .institute .insure .int .international .investments .io .iq .ir .is .it .je .jetzt .jm .jo .jobs .jp .juegos .kaufen .ke .kg .kh .ki .kim .kitchen .kiwi .km .kn .koeln .kp .kr .kred .kw .ky .kz .la .land .lawyer .lb .lc .lease .li .life .lighting .limited .limo .link .lk .loans .london .lr .ls .lt .lu .luxe .luxury .lv .ly .ma .maison .management .mango .market .marketing .mc .md .me .media .meet .menu .mg .mh .miami .mil .mk .ml .mm .mn .mo .mobi .moda .moe .monash .mortgage .moscow .motorcycles .mp .mq .mr .ms .mt .mu .museum .mv .mw .mx .my .mz .na .nagoya .name .navy .nc .ne .net .neustar .nf .ng .nhk .ni .ninja .nl .no .np .nr .nu .nyc .nz .okinawa .om .onl .org .pa .paris .partners .parts .pe .pf .pg .ph .photo .photography .photos .pics .pictures .pink .pk .pl .plumbing .pm .pn .post .pr .press .pro .productions .properties .ps .pt .pub .pw .py .qa .qpon .quebec .re .recipes .red .rehab .reise .reisen .ren .rentals .repair .report .republican .rest .reviews .rich .rio .ro .rocks .rodeo .rs .ru .ruhr .rw .ryukyu .sa .saarland .sb .sc .schule .sd .se .services .sexy .sg .sh .shiksha .shoes .si .singles .sj .sk .sl .sm .sn .so .social .software .sohu .solar .solutions .soy .space .sr .st .su .supplies .supply .support .surgery .sv .sx .sy .systems .sz .tattoo .tax .tc .td .technology .tel .tf .tg .th .tienda .tips .tirol .tj .tk .tl .tm .tn .to .today .tokyo .tools .town .toys .tp .tr .trade .training .travel .tt .tv .tw .tz .ua .ug .uk .university .uno .us .uy .uz .va .vacations .vc .ve .vegas .ventures .versicherung .vet .vg .vi .viajes .villas .vision .vn .vodka .vote .voting .voto .voyage .vu .wang .watch .webcam .website .wed .wf .wien .wiki .works .ws .wtc .wtf .xn--3bst00m .xn--3ds443g .xn--3e0b707e .xn--45brj9c .xn--4gbrim .xn--55qw42g .xn--55qx5d .xn--6frz82g .xn--6qq986b3xl .xn--80adxhks .xn--80ao21a .xn--80asehdb .xn--80aswg .xn--90a3ac .xn--c1avg .xn--cg4bki .xn--clchc0ea0b2g2a9gcd .xn--czr694b .xn--czru2d .xn--d1acj3b .xn--fiq228c5hs .xn--fiq64b .xn--fiqs8s .xn--fiqz9s .xn--fpcrj9c3d .xn--fzc2c9e2c .xn--gecrj9c .xn--h2brj9c .xn--i1b6b1a6a2e .xn--io0a7i .xn--j1amh .xn--j6w193g .xn--kprw13d .xn--kpry57d .xn--l1acc .xn--lgbbat1ad8j .xn--mgb9awbf .xn--mgba3a4f16a .xn--mgbaam7a8h .xn--mgbab2bd .xn--mgbayh7gpa .xn--mgbbh1a71e .xn--mgbc0a9azcg .xn--mgberp4a5d4ar .xn--mgbx4cd0ab .xn--ngbc5azd .xn--nqv7f .xn--nqv7fs00ema .xn--o3cw4h .xn--ogbpf8fl .xn--p1ai .xn--pgbs0dh .xn--q9jyb4c .xn--rhqv96g .xn--s9brj9c .xn--ses554g .xn--unup4y .xn--wgbh1c .xn--wgbl6a .xn--xkc2dl3a5ee0h .xn--xkc2al3hye2a .xn--yfro4i67o .xn--ygbi2ammx .xn--zfr164b .xxx .xyz .yachts .ye .yokohama .yt .za .zm .zw .zone';

		$tlds = array_fill_keys(explode(' ', $tld_list), true);
		$GLOBALS['cfg']['url_linker_tlds'] = $tlds;
		return $tlds;
	}

	# the end
