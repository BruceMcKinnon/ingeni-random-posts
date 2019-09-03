<?php
class IngeniRandomPosts {


	public $name = 'Ingeni Random Posts';
	public $tag = 'ingeni_random_posts';


	public function __construct() {
		add_action('before_delete_post', array( &$this, 'delete_associated_media' ) );

	}
		
	private function is_local() {
		$local_install = false;
		if ( ($_SERVER['SERVER_NAME']=='localhost') ) {
			$local_install = true;
		}
		return $local_install;
	}




	private function fb_log($msg) {
		$upload_dir = wp_upload_dir();
		$logFile = $upload_dir['basedir'] . '/' . 'fb_log.txt';
		date_default_timezone_set('Australia/Sydney');

		// Now write out to the file
		$log_handle = fopen($logFile, "a");
		if ($log_handle !== false) {
			fwrite($log_handle, date("H:i:s").": ".$msg."\r\n");
			fclose($log_handle);
		}
	}


	private function localslashit($input) {
		if ($this->is_local() ) {
			if ( substr($input,strlen($input)-1,1) !== '\\' )  {
				$input .= '\\';
			}
			$input = str_replace('/','\\',$input);
			return $input;
		} else {
			return trailingslashit($input);
		}
	}

	function get_random_author() {
		try {
			$auth_id = 1;
			$authlist = array();
			
			$authors = get_users( 'role=author&fields=ID' );

			if ( !$authors || (count($authors) < 1) ) {
				$author_names = array('Ernest Hemmingway', 'Joan Didion', 'Ray Bradbury', 'Gillian Flynn', 'Jane Austen', 'Mark Twain');

				foreach($author_names as $name) {
					$user_name = str_replace(' ','',$name);
					$user_id = username_exists( $user_name );
					$user_email = $user_name .'@domain.local';
					if ( !$user_id and email_exists( $user_email ) == false ) {
						$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
						$user_id = wp_create_user( $name, $random_password, $user_email );
						// Now set as an author
						$user_obj = get_userdata( $user_id );
						$user_obj->set_role( 'author' );
					}
				}

				$authors = get_users( 'role=author&fields=ID' );
			}

			$this->fb_log('authors: '.print_r($authors,true));
			$total_authors = count($authors) - 1;
				
			return $authors[rand ( 0 , $total_authors )];
			
		} catch (Exception $e) {
			$this->fb_log('get_random_author: '.$e->getMessage);
			return 0;
		}
	}

	function get_random_category() {
		try {
			$category_ids = get_terms( 'category', 'fields=ids&hide_empty=0' );

			if ( !$category_ids || (count($category_ids) < 1)  ) {
				$cat_names = array('Politics', 'Sport', 'Business', 'World News', 'Australia' );

				foreach($cat_names as $cat) {
					wp_create_category( $cat );
				}

				$category_ids = get_terms( 'category', 'fields=ids&hide_empty=0' );
			}

			$total_cats = count($category_ids) - 1;
			
			$cat_ary = array($category_ids[rand ( 0 , $total_cats )]);
			return $cat_ary;
			
		} catch (Exception $e) {
			$this->fb_log('get_random_category: '.$e->getMessage);
			return 0;
		}
	}

	function get_random_tags() {
		try {
			$tags = array("Displacement", "Empowerment", "Everlasting love", "Racism", "Darkness", "Reality", "Beauty", "Faith", "Family", "Fate", "Fear", "Fulfillment", "Greed", "Growing up", "Passing judgment", "Heartbreak", "Betrayal", "Heroism", "Identity crisis", "Illusion", "Immortality", "Individuals", "Inner strength", "Injustice", "Isolation", "Ignorance", "Loneliness", "Loss of innocence", "Love and sacrifice", "Man against nature", "Manipulation", "Materialism", "Motherhood", "Nationalism", "Nature", "Necessity of work", "Oppression", "Optimism", "Overcoming", "Patriotism", "Corruption", "Tradition", "Wealth", "Power of words", "Pride", "Progress", "Discovery", "Power");
			$total_tags = count($tags) - 1;
			
			$tag_list = $tags[rand ( 0 , $total_tags )].',';
			$tag_list .= $tags[rand ( 0 , $total_tags )].',';
			$tag_list .= $tags[rand ( 0 , $total_tags )].',';
			$tag_list .= $tags[rand ( 0 , $total_tags )];

			return $tag_list;
			
		} catch (Exception $e) {
			$this->fb_log('get_random_tags: '.$e->getMessage);
			return '';
		}
	}

	//
	// Adapted from https://tommcfarlin.com/upload-files-wordpress-media-library/
	//
	// Copies a file from the a subdirectory of the root of the WordPress installation
	// into the uploads directory, attaches it to the given post ID, and adds it to
	// the Media Library.
	//
	function random_posts_add_file_to_media_library( $post_id, $filename, $source_folder, $title = 'Random Image' ) {
		global $wpdb;

		set_time_limit(30); // Force the max execution timer to restart
									
		// Locate the file in a subdirectory of the root of the installation
		$file = $this->localslashit( $source_folder ) . $filename;

		// If the file doesn't exist, then write to the error log and duck out
		if ( ! file_exists( $file ) || 0 === strlen( trim( $filename ) ) ) {
			$this->fb_log( 'random_posts_add_file_to_media_library: The file you are attempting to upload does not exist: ' . $file );
			return -1;
		}

		// Read the contents of the upload directory. We need the
		// path to copy the file and the URL for uploading the file.
		//
		$uploads = wp_upload_dir();
		$uploads_dir = $uploads['path'];
		$uploads_url = $uploads['url'];

		$attach_id = -1;
		try {
			if ( copy($file, $this->localslashit( $uploads_dir ).$filename) )  {

				// Check the type of file. We'll use this as the 'post_mime_type'.
				$filetype = wp_check_filetype( basename( $filename ), null );

				// Prepare an array of post data for the attachment.
				$attachment = array(
					'guid'           => trailingslashit($uploads['url']) . $filename, 
					'post_mime_type' => $filetype['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
			
				// Insert the attachment.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				
				// NOTE - The codex page for wp_insert_attachment() says param 2 must be the absolute path.
				// This is incorrect. You specify the relative path from the uploads folder.
				$attach_filename = trailingslashit($uploads['subdir']) . $filename; 		
				$attach_id = wp_insert_attachment( $attachment, $attach_filename, $post_id );
				
				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attach_id, $this->localslashit( $uploads_dir ).$filename );
				wp_update_attachment_metadata( $attach_id, $attach_data );	
			}
		
		} catch (Exception $e) {
			$this->fb_log('random_posts_add_file_to_media_library: '.$e->getMessage);
		}

		return $attach_id;
	}

	function attach_random_image($parent_post_id) {
		global $wpdb;
		try {
			$img_prefix = "gratisography0";
			$plugin_dir_path = dirname(__FILE__);
			
			$image_first = 1;
			$image_last = 20;
			
			$img_idx = rand ( $image_first , $image_last );
			
			if ($img_idx < 10) {
				$img_suffix = "0".$img_idx.".jpg";
			} else {
				$img_suffix = $img_idx.".jpg";
			}
			$upload_dir = wp_upload_dir();

			$image_src = trailingslashit($upload_dir['url']) . $img_prefix.$img_suffix ;

			$sql_query = $wpdb->prepare ( "SELECT id FROM wp_posts WHERE (post_mime_type='image/jpeg') AND (guid=%s)", $image_src );
			$images = $wpdb->get_results( $sql_query );
			
			$image_id = -1;
			
			// Now loop through the 
			if ($images ) {
				$image_id = $images[0]->id;
			}
		
			if ($image_id < 0) {
				//
				// If the image isn't already in the media  library, add it.
				//
				$image_id = $this->random_posts_add_file_to_media_library( $parent_post_id, $img_prefix.$img_suffix, $this->localslashit($plugin_dir_path).$this->localslashit('feature-images') ); 
			}
				
			// Image is in the media library, so just set it as the featured image
			$result = set_post_thumbnail( $parent_post_id, $image_id );
			if ($result) {
				$this->fb_log('thumbnail result: '.$result);
			}
			
		} catch (Exception $e) {
			$this->fb_log('attach_random_image: '.$e->getMessage);
			return '';
		}
	}

	function create_random_posts( $max_posts, &$err_msg ) {
		$idx = 0;
		try {
			global $wpdb;

			set_time_limit(30); // Force the max execution timer to restart
			
			$colours = array("White", "Silver", "Black", "Grey", "Blue", "Red", "Brown", "Green", "Apricot", "Bisque", "Chartreuse", "Ecru", "Fuchsia", "Heliotrope", "Jonquil", "Lavender", "Mauve", "Orchid", "Charcoal", "Peach",
			"Peridot", "Azure", "Sepia", "Teal", "Vermilion", "Tangerine", "Watermelon", "Mint", "Mulberry", "Mustard",
			"Jade", "Grape", "Gold", "Emerald", "Ebony");

			$animals = array("alligators", "ants", "bears", "bees", "birds", "camels", "cheetahs", "chickens", "chimps", "cows", "crocodiles", "deers", "dogs", "dolphins", "ducks", "eagles", "elephants", "fish", "frogs", "giraffes", "goats", "goldfish", "hamsters", "kangaroos", "kittens", "lions", "lobsters", "monkeys", "owls", "pandas", "pigs", "puppies", "rabbits", "rats", "scorpions", "seals", "sharks", "sheep", "snails", "snakes", "spiders", "squirrels", "tigers", "turtles", "wolves", "zebras");

			$verbs = array("walk", "run", "saunter", "gallop", "hike", "parade", "stroll", "skydive", "amble", "perambulate", "stagger", "strut", "stumble", "tramp", "tread", "waddle", "trudge", "wander",
			"jump", "skip", "hop", "rawl", "bend", "sway", "swing", "shake", "twist", "leap", "roll", "twirl", "kick", "tip-toe", "stamp", "grab", "punch", "pull", "push", "wiggle", "catch", "throw", "dig", "wave", "climb",
			"wink", "clap", "yawn", "blink", "shuffle", "creep", "march", "turn", "ride", "swim", "dive", "skate", 
			"dance", "jog", "stomp");

			$adverbs = array("absent-mindedly", "adventurously", "arrogantly", "anxiously", "calmly", "carefully", "carelessly", "cautiously", "bleakly", "blindly", "blissfully", "boastfully", "boldly", "bravely", "defiantly", "deliberately", "enthusiastically", "ferociously", "fervently", "fiercely", "playfully", "politely", "willfully", "wisely", "woefully", "victoriously", "violently", "vivaciously");
					
			$upload_dir = wp_upload_dir();
			
			$img_left_url = trailingslashit($upload_dir['url']) . 'gratisography-post-left.jpg';
			$img_left_class = "alignleft";
			$img_right_url = trailingslashit($upload_dir['url']) . 'gratisography-post-right.jpg';
			$img_right_class = "alignright";
			$img_left_id = $img_right_id = 0;
			$img_alt = "Photo courtesy of gratisography.com";
			
			$plugin_dir_path = dirname(__FILE__);

			$post_content = 
			'<p>Ice cream lemon drops tootsie roll chocolate marzipan. Liquorice icing caramels carrot cake sugar plum. Candy canes icing brownie.</p>
			<h2>This is a H2 Heading</h2>
			<img src="'.$img_left_url.'" class="'.$img_left_class.'" alt="'.$img_alt.'" /><p>Macaroon lemon drops bear claw tootsie roll gingerbread brownie sugar plum. Powder candy macaroon bear claw. Cupcake biscuit cotton candy sweet roll cheesecake danish. Souffle cookie halvah fruitcake. Cake candy canes chupa chups muffin biscuit bear claw. Liquorice bonbon caramels. Caramels jelly pastry.</p>
			<ul>
			<li>Jelly beans</li>
			<li>Sweet danish</li>
			<li>Chocolate topping</li>
			<li>Muffin</li>
			<li>Macaroon</li>
			</ul>
			<img src="'.$img_right_url.'" class="'.$img_right_class.'" alt="'.$img_alt.'" /><p>Gummi bears souffle souffle pudding muffin bonbon toffee. Sugar plum candy candy pastry. Applicake macaroon sugar plum sweet jelly-o pastry chocolate cake. Cookie danish lemon drops tiramisu chocolate bar donut bonbon. Apple pie donut chocolate bar jelly beans marzipan ice cream lollipop carrot cake carrot cake.</p>
			<h3>This is a H3 Heading</h3>
			<p>Applicake chocolate muffin toffee icing oat cake biscuit chocolate cake donut. Oat cake candy canes cheesecake biscuit lollipop. Marshmallow chupa chups ice cream gummi bears icing marzipan.</p>
			<ol>
			<li>Jelly beans</li>
			<li>Sweet danish</li>
			<li>Chocolate topping</li>
			<li>Muffin</li>
			<li>Macaroon</li>
			</ol>
			<p>Cheesecake cupcake liquorice chupa chups wafer fruitcake caramels. Marzipan topping donut marzipan marzipan cookie. Candy cotton candy dessert chupa chups. Apple pie brownie chupa chups chocolate chocolate cake donut. Danish sugar plum powder candy souffle jelly beans. Souffle liquorice gummi bears.</p>
			<h4>This is a H4 Heading</h4>
			<p>Thanks to <a href="//www.gratisography.com/" target="_blank">Ryan Mcguire and gratisography.com/</a> for awesome public domain photos and <a href="//www.cupcakeipsum.com/" target="_blank">cupcakeipsum.com</a> for the sweet Lorem Ipsum text.';

			
			$total_colours = count($colours) - 1;
			$total_animals = count($animals) - 1;
			$total_verbs = count($verbs) - 1;
			$total_adverbs = count($adverbs) - 1;

			while ($idx < $max_posts) {
				$max_days = 365;
				$date_offset = rand ( 0 , $max_days );
				
				$post_date = date('Y-m-d H:i:s', strtotime(' -'.$date_offset.' days'));

				$colour_idx = rand ( 0 , $total_colours );
				$animal_idx = rand ( 0 , $total_animals );
				$verb_idx = rand ( 0 , $total_verbs );
				$adverb_idx = rand ( 0 , $total_adverbs );
						
				$title = $colours[$colour_idx]." ". $animals[$animal_idx]." ". $verbs[$verb_idx]." ". $adverbs[$adverb_idx];

				// Create post object
				$my_post = array(
					'post_title'		=> $title,
					'post_content'	=> $post_content,
					'post_status'		=> 'publish',
					'post_author'		=> $this->get_random_author(),
					'post_category'	=> $this->get_random_category(),
					'post_date' 		=> $post_date,
					'tags_input'		=> $this->get_random_tags()
				);		

				// Insert the post into the database
				$post_id = wp_insert_post( $my_post, true );
				
				if ( is_wp_error( $post_id ) ) {
					$this->fb_log('Insert Error: '.$post_id->get_error_message());
				} else {
					// Set the featured image
					$this->attach_random_image($post_id);
					// Update (or add) the custom field
					add_post_meta( $post_id, 'random_post', 1 );
					
					if ($img_left_id < 1) {
						$img_left_id = $this->random_posts_add_file_to_media_library($post_id,'gratisography-post-left.jpg',$plugin_dir_path);
					}
					if ($img_right_id < 1) {
						$img_right_id = $this->random_posts_add_file_to_media_library($post_id,'gratisography-post-right.jpg',$plugin_dir_path);
					}

					$this->fb_log ('published #'.$post_id.' = '.$title);
				}
				
				$idx += 1;
			}
		} catch (Exception $e) {
			$err_msg = $e->getMessage;
			$this->fb_log('create_random_posts: '.$e->getMessage);
			$idx = -1;
		}
		
		return $idx;
	}

	function delete_associated_media($id) {
			$media = get_children(array(
					'post_parent' => $id,
					'post_type' => 'attachment'
			));
			if ( !empty($media) ) {
			foreach ($media as $file) {
				// Delete the attached media
				wp_delete_attachment($file->ID, true);
			}
		}
	}

	function delete_random_posts( &$err_msg ) {
		global $wpdb;
		$retCount = 0;
		
		try {
			$args = array(
				'meta_key' => 'random_post',
				'meta_value' => 1,
				'post_type' => 'post',
				'posts_per_page' => -1
			);
			
			$all_random_posts = new WP_Query( $args );
			while ( $all_random_posts->have_posts() ) {
				$all_random_posts->the_post();
				wp_delete_post( get_the_ID(), true );
				$retCount += 1;
			}

		} catch (Exception $e) {
			$err_msg = $e->getMessage;
			$this->fb_log('delete_random_posts: '.$e->getMessage);
			$retCount = -1;
		}

		wp_reset_postdata();
		return $retCount;
	}

}
?>