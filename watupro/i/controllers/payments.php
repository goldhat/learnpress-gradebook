<?php
class WatuPROPayments {
	// view/add payments for exam
	static function manage() {
		global $wpdb, $user_ID;
		
		// select this exam
		if(!empty($_GET['exam_id'])) {
			$exam = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".WATUPRO_EXAMS." WHERE ID = %d", $_GET['exam_id']));
			if(empty($exam->ID)) wp_die(__('No quiz with this ID', 'watupro'));
			$item = $exam;
			$field = 'exam_id';
		}
		if(!empty($_GET['bundle_id'])) {
			$bundle = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID = %d", $_GET['bundle_id']));
			if(empty($bundle->ID)) wp_die(__('No bundle with this ID', 'watupro'));
			$item = $bundle;
			$field = 'bundle_id';
			if(empty($bundle->name)) {
				if($bundle->bundle_type == 'category') $bundle_name = sprintf(__('Access to a categories of %s (Bundle ID: %d)', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, $bundle->ID);
		      else $bundle_name = sprintf(__('Access to a selection of %s (Bundle ID: %d)', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, $bundle->ID);
			}
			else $bundle_name = $bundle->name;
		}	
		
		// add payment manually
		if(!empty($_POST['add_payment'])) {
			// find the given user first
			$user = get_user_by('login', $_POST['user_login']);
			if(empty($user->user_login)) wp_die(__('Unrecognized user login', 'watupro'));
			
			// now insert the payment
			$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET
				$field=%d, user_id=%d, date=CURDATE(), amount=%s, status='completed', paycode='manual', method='manual'", 
				$item->ID, $user->ID, $_POST['amount']));
				
			watupro_redirect("admin.php?page=watupro_payments&".$field."=".$item->ID);	
		}
		
		$offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
		
		// delete payment
		if(!empty($_GET['delete'])) {
			$wpdb->query( $wpdb->prepare("DELETE FROM ".WATUPRO_PAYMENTS." WHERE id=%d", $_GET['id']));
			watupro_redirect("admin.php?page=watupro_payments&".@$field."=".@$item->ID."&offset=$offset");
		}
		
		// approve/unapprove payment
		if(!empty($_GET['change_status'])) {
			$status = empty($_GET['status']) ? 'pending' : 'completed';
			$wpdb->query( $wpdb->prepare("UPDATE ".WATUPRO_PAYMENTS." SET status='$status' WHERE id=%d", $_GET['id']));
			watupro_redirect("admin.php?page=watupro_payments&".@$field."=".@$item->ID."&offset=$offset");
		}
		
		// select payments made		
		$see_all_quizzes = empty($field); // when $field variable is empty we are looking at the payments made for all quizzes
		if(!$see_all_quizzes) {
			$payments = $wpdb->get_results( $wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS tP.*, tU.user_login as user_login 
				FROM ".WATUPRO_PAYMENTS." tP LEFT JOIN {$wpdb->users} tU ON tU.ID = tP.user_id
				WHERE tP.$field=%d ORDER BY tP.ID DESC LIMIT $offset, 100", $item->ID));
		}
		else {
			$payments = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS tP.*, tE.name as quiz_name, tU.user_login as user_login 
				FROM ".WATUPRO_PAYMENTS." tP LEFT JOIN {$wpdb->users} tU ON tU.ID = tP.user_id
				JOIN ".WATUPRO_EXAMS." tE ON tE.ID = tP.exam_id
				ORDER BY tP.ID DESC LIMIT $offset, 100");
		}	
			
		$count = $wpdb->get_var("SELECT FOUND_ROWS()");	
		
		$currency = get_option('watupro_currency');
		$paypoints_price = get_option('watupro_paypoints_price');
		
		// select all paid quizzes for the dropdown
		$paid_exams = $wpdb->get_results("SELECT name, ID FROM ".WATUPRO_EXAMS." WHERE fee>0 ORDER BY name");
			
		if(@file_exists(get_stylesheet_directory().'/watupro/i/payments.html.php')) require get_stylesheet_directory().'/watupro/i/payments.html.php';
		else require WATUPRO_PATH."/i/views/payments.html.php";
	}
	
	// handle the ajax request of the payment with points
	static function pay_with_points() {
		global $wpdb, $user_ID;
		
		if(!is_user_logged_in()) die("ERROR: Not logged in");
		
		// payment with points accepted at all?
		$accept_paypoints = get_option('watupro_accept_paypoints');
		if(empty($accept_paypoints)) die("ERROR: points not accepted as payment method."); 
		
		// enough points to pay?
		$paypoints_price = get_option('watupro_paypoints_price');
		if(empty($_POST['is_bundle'])) {
			$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_POST['id']));
			if($exam->fee > 0 and class_exists('WatuPROIExam') and method_exists('WatuPROIExam', 'adjust_price')) WatuPROIExam :: adjust_price($exam);
			$fee = $exam->fee;
			$cost_in_points = $exam->fee * $paypoints_price;
		}
		else {
			// bundle
			$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $_POST['id']));
			$fee = $bundle->price;
			$cost_in_points = $bundle->price * $paypoints_price;
		}
		
		// used coupon?
		$coupon_code = get_user_meta($user_ID, 'watupro_coupon', true);		 
		if(!empty($coupon_code)) {
			$coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_COUPONS." WHERE code=%s", trim($coupon_code)));
			if(WatuPROICoupons :: is_valid($coupon)) {
				// apply to the price
				$fee = WatuPROICoupons :: apply($coupon, $fee, $user_ID, false);
				$cost_in_points = $fee * $paypoints_price;
			}	
		}		
		
		$user_points = get_user_meta($user_ID, 'watuproplay-points', true);	
		if($user_points < $cost_in_points) die("ERROR: Not enough points");
		
		// now make payment
		$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET 
			exam_id=%d, user_id=%s, date=CURDATE(), amount=%s, status='completed', paycode=%s, 
			method='points', bundle_id=%d", 
			@$exam->ID, $user_ID, $fee, '', @$bundle->ID));
				
		// deduct user points
		$user_points -= $cost_in_points;
		update_user_meta($user_ID, 'watuproplay-points', $user_points);	
		
		 // cleanup coupon code if any
		if(!empty($coupon_code)) if(!empty($coupon_code)) WatuPROICoupons :: coupon_used($coupon, $user_ID);		
			
		echo "SUCCESS";
		exit;
	}
	
	
		// handle the ajax request of the payment with MoolaMojo
	static function pay_with_moolamojo() {
		global $wpdb, $user_ID;
		
		if(!is_user_logged_in()) die("ERROR: Not logged in");
		
		// payment with moolamojo accepted at all?
		$accept_moolamojo = get_option('watupro_accept_moolamojo');
		if(empty($accept_moolamojo)) die("ERROR: virtual credits are not accepted as payment method."); 
		
		// enough points to pay?
		$moola_price = get_option('watupro_moolamojo_price');
		if(empty($_POST['is_bundle'])) {
			$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", intval($_POST['id'])));
			if($exam->fee > 0 and class_exists('WatuPROIExam') and method_exists('WatuPROIExam', 'adjust_price')) WatuPROIExam :: adjust_price($exam);
			$fee = $exam->fee;
			$cost_in_moola = $exam->fee * $moola_price;
		}
		else {
			// bundle
			$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", intval($_POST['id'])));
			$fee = $bundle->price;
			$cost_in_moola = $bundle->price * $moola_price;
		}
		
		// used coupon?
		$coupon_code = get_user_meta($user_ID, 'watupro_coupon', true);		 
		if(!empty($coupon_code)) {
			$coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_COUPONS." WHERE code=%s", trim($coupon_code)));
			if(WatuPROICoupons :: is_valid($coupon)) {
				// apply to the price
				$fee = WatuPROICoupons :: apply($coupon, $fee, $user_ID, false);
				$cost_in_moola = $fee * $moola_price;
			}	
		}		
		
		$user_balance = get_user_meta($user_ID, 'moolamojo_balance', true);	
		if($user_balance < $cost_in_moola) die("ERROR: Not enough virtual credits");
		
		// now make payment
		$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET 
			exam_id=%d, user_id=%s, date=CURDATE(), amount=%s, status='completed', paycode=%s, 
			method='moolamojo', bundle_id=%d", 
			@$exam->ID, $user_ID, $fee, '', @$bundle->ID));
				
		// deduct user points
		$user_balance -= $cost_in_moola;
		update_user_meta($user_ID, 'moolamojo_balance', $user_balance);	
		
		 // cleanup coupon code if any
		if(!empty($coupon_code)) if(!empty($coupon_code)) WatuPROICoupons :: coupon_used($coupon, $user_ID);		
			
		echo "SUCCESS";
		exit;
	}
	
	// display and create buttons for buying quiz bundles
	static function bundles() {
		global $wpdb, $user_ID;
		$currency = get_option('watupro_currency');
		$accept_stripe = get_option('watupro_accept_stripe');
		$accept_points = get_option('watupro_accept_paypoints');
		$other_payments = get_option('watupro_other_payments');
		$do = empty($_GET['do']) ? 'list' : $_GET['do'];
		
		$multiuser_access = 'all';
		if(watupro_intel()) $multiuser_access = WatuPROIMultiUser::check_access('bundles_access');
		
		// select all quizzes
		$quizzes = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_EXAMS." WHERE fee > 0 ORDER BY name");
		
		// select quiz cats
		$cats = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_CATS." ORDER BY name");
		
		$is_time_limited = empty($_POST['is_time_limited']) ? 0 : 1;
		
		switch($do) {
			case 'add':
				if(!empty($_POST['ok']) and check_admin_referer('watupro_bundle')) {
					$cat_ids = ($_POST['bundle_type'] == 'quizzes') ? '' : '|'.implode('|', watupro_int_array(@$_POST['cat_ids'])).'|';
					$quiz_ids = ($_POST['bundle_type'] == 'quizzes') ? implode(",", watupro_int_array(@$_POST['quizzes'])) : '';
					$num_quizzes = empty($_POST['num_quizzes']) ? 0 : intval($_POST['num_quizzes']);
					$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_BUNDLES." SET
						price=%f, bundle_type=%s, quiz_ids=%s, cat_ids=%s, redirect_url=%s, is_time_limited=%d, time_limit=%d, 
						name=%s, editor_id=%d, num_quizzes=%d",
						floatval($_POST['price']), sanitize_text_field($_POST['bundle_type']), $quiz_ids, $cat_ids, esc_url_raw($_POST['redirect_url']), 
						$is_time_limited, intval($_POST['time_limit']), sanitize_text_field($_POST['name']), $user_ID, $num_quizzes));
						
					watupro_redirect("admin.php?page=watupro_bundles");	
				}
				
				if(@file_exists(get_stylesheet_directory().'/watupro/i/bundle.html.php')) require get_stylesheet_directory().'/watupro/i/bundle.html.php';
		else require WATUPRO_PATH."/i/views/bundle.html.php";
			break;		
			
			case 'edit':
				$own_sql = ($multiuser_access == 'own') ? $wpdb->prepare(" AND editor_id = %d ", $user_ID) : "";
				
				if(!empty($_POST['ok']) and check_admin_referer('watupro_bundle')) {
					$cat_ids = ($_POST['bundle_type'] == 'quizzes') ? '' : '|'.implode('|', watupro_int_array(@$_POST['cat_ids'])).'|';
					$quiz_ids = ($_POST['bundle_type'] == 'quizzes') ? implode(",", watupro_int_array(@$_POST['quizzes'])) : '';
					$num_quizzes = empty($_POST['num_quizzes']) ? 0 : intval($_POST['num_quizzes']);
					$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_BUNDLES." SET
						price=%f, bundle_type=%s, quiz_ids=%s, cat_ids=%s, redirect_url=%s, is_time_limited=%d, 
						time_limit=%d, name=%s, num_quizzes=%d
						WHERE ID=%d $own_sql",
						floatval($_POST['price']), sanitize_text_field($_POST['bundle_type']), $quiz_ids, $cat_ids, esc_url_raw($_POST['redirect_url']), 
						$is_time_limited, intval($_POST['time_limit']), sanitize_text_field($_POST['name']), $num_quizzes, intval($_GET['id'])));
						
					watupro_redirect("admin.php?page=watupro_bundles");	
				}
				
				$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d $own_sql ", intval($_GET['id'])));
				$qids = explode(",", $bundle->quiz_ids);
				
				if(@file_exists(get_stylesheet_directory().'/watupro/i/bundle.html.php')) require get_stylesheet_directory().'/watupro/i/bundle.html.php';
		else require WATUPRO_PATH."/i/views/bundle.html.php";
			break;				
			
			case 'list':
			default:
				$own_sql = ($multiuser_access == 'own') ? $wpdb->prepare(" AND editor_id = %d ", $user_ID) : "";
				
				if(!empty($_GET['del'])) {
					$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRO_BUNDLES." WHERE ID=%d $own_sql ", intval($_GET['id'])));
					watupro_redirect("admin.php?page=watupro_bundles");
				}		
				
				if(!empty($_POST['bundle_settings']) and check_admin_referer('watupro_bundle_settings')) {
					$enable_my_bundles = empty($_POST['enable_my_bundles']) ? 0 : 1;
					update_option('watupro_enable_my_bundles', $enable_my_bundles);
				}
				
				$enable_my_bundles = get_option('watupro_enable_my_bundles');
			
				$own_sql = ($multiuser_access == 'own') ? $wpdb->prepare(" AND tB.editor_id = %d ", $user_ID) : "";
				
				// select current bundles left join by cat
				$bundles = $wpdb->get_results("SELECT tB.* FROM ".WATUPRO_BUNDLES." tB WHERE 1=1 $own_sql ORDER BY tB.ID");
					
				// add cats and quizzes to the bundles
				foreach($bundles as $cnt => $bundle) {
					$quiz_names = array();
					if($bundle->bundle_type == 'quizzes') {
						$qids = explode(",", $bundle->quiz_ids);
						foreach($quizzes as $quiz) {
							if(in_array($quiz->ID, $qids)) $quiz_names[] = stripslashes($quiz->name);
						} // end foreach quiz
						
						$bundles[$cnt]->quizzes = implode(", ", $quiz_names);
					} // end if
					
					$cat_names = array();
					if($bundle->bundle_type == 'category') {
						$cids = explode("|", $bundle->cat_ids);
						foreach($cats as $cat) {
							if(in_array($cat->ID, $cids)) $cat_names[] = stripslashes($cat->name);
						} // end foreach quiz
						
						$bundles[$cnt]->cat = implode(", ", $cat_names);
					} // end if
				}	// end foreach bundle
				
				if(@file_exists(get_stylesheet_directory().'/watupro/i/bundles.html.php')) require get_stylesheet_directory().'/watupro/i/bundles.html.php';
		else require WATUPRO_PATH."/i/views/bundles.html.php";
			break;
		}
	} // end managing bundles
	
	// list bundles I have purchased
	static function my_bundles($in_shortcode = false) {
		global $wpdb, $user_ID;
		
		if(!is_user_logged_in()) return "";
		$enable_my_bundles = get_option('watupro_enable_my_bundles');
		if(!$enable_my_bundles) return "";
		
		// select all quizzes
		$quizzes = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_EXAMS." WHERE fee > 0 ORDER BY name");
		
		// select quiz cats
		$cats = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_CATS." ORDER BY name");
		
		// select my bundles
		$bundles = $wpdb->get_results($wpdb->prepare("SELECT tB.*, tP.date as payment_date, tP.num_quizzes_used as num_quizzes_used 
			FROM ".WATUPRO_BUNDLES." tB 
			JOIN ".WATUPRO_PAYMENTS." tP ON tP.user_id=%d AND tP.status='completed' AND tP.bundle_id=tB.ID
			ORDER BY tB.name", $user_ID));
			
		// add quizzes to the bundles
		foreach($bundles as $cnt => $bundle) {
			$quiz_names = array();
			if($bundle->bundle_type == 'quizzes') {
				$qids = explode(",", $bundle->quiz_ids);
				foreach($quizzes as $quiz) {
					if(in_array($quiz->ID, $qids)) $quiz_names[] = $quiz->name;
				} // end foreach quiz
				
				$bundles[$cnt]->quizzes = implode(", ", $quiz_names);
			} // end if
			
			$cat_names = array();
			if($bundle->bundle_type == 'category') {
				$cids = explode("|", $bundle->cat_ids);
				foreach($cats as $cat) {
					if(in_array($cat->ID, $cids)) $cat_names[] = stripslashes($cat->name);
				} // end foreach quiz
				
				$bundles[$cnt]->cat = implode(", ", $cat_names);
			} // end if
		}	// end foreach bundle	
		
		$dateformat = get_option('date_format');
		if(@file_exists(get_stylesheet_directory().'/watupro/i/my-bundles.html.php')) require get_stylesheet_directory().'/watupro/i/my-bundles.html.php';
		else require WATUPRO_PATH."/i/views/my-bundles.html.php";
	}
	
	// call my_bundles from shortcode
	static function my_bundles_shortcode() {
		ob_start();
		self :: my_bundles(true);
		$content = ob_get_clean();
		return $content;
	}
	
	// display payment bundle button
	static function bundle_button($atts) {
		global $wpdb, $post, $user_ID;
		watupro_vc_scripts();
		$mode = @$atts['mode'];
		$currency = get_option('watupro_currency');
		
		if(empty($user_ID)) return __('You need to be logged in.', 'watupro');
				
		// select this bundle
		$bundle = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_BUNDLES." WHERE ID=%d", $atts['id']));
		if(empty($bundle->ID)) return "<p>".__('This offer has been disabled.', 'watupro')."</p>";
		
		// if the user already paid for this bundle and the bundle has redirect URL defined, just go to it
		if(!empty($bundle->redirect_url)) {
			$valid_bundle_payment = $wpdb->get_row($wpdb->prepare("SELECT ID, date FROM ".WATUPRO_PAYMENTS."
				WHERE user_id=%d AND bundle_id=%d AND status='completed'", $user_ID, $bundle->ID));
				
			// if the bundle is time limited and the limit has expired, unset the ID
			if($bundle->is_time_limited and !empty($valid_bundle_payment->ID)) {
				$payment_time = strtotime($valid_bundle_payment->date);
				if(current_time('timestamp') > ($payment_time + 24 * 3600 * $bundle->time_limit) ) unset($valid_bundle_payment->ID);
			}	
				
			if(!empty($valid_bundle_payment->ID)) watupro_redirect($bundle->redirect_url);	
		}
	
		// coupon code inserted
		$any_coupons = $wpdb->get_var("SELECT id FROM ".WATUPRO_COUPONS." WHERE num_uses = 0 OR (CAST(num_uses as signed) - CAST(times_used as signed)) > 0");

		if($any_coupons and !empty($_POST['watupro_coupon'])) {
			// check if the coupon is valid
			$coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_COUPONS." WHERE code=%s", trim($_POST['watupro_coupon'])));
			
			if(WatuPROICoupons :: is_valid($coupon)) {
				// apply to the price
				$old_fee = $bundle->price;
				$bundle->price = WatuPROICoupons :: apply($coupon, $bundle->price, $user_ID);	
				$coupon_applied = true;			
			}
		}
		
		// prepare bundle name
		if(empty($bundle->name)) {
			$bundle_name = '';
			if($bundle->bundle_type == 'category') $bundle_name = sprintf(__('Access to a category of %s', 'watupro'), __('quizzes', 'watupro'));
	    	else $bundle_name = sprintf(__('Access to a selection of %s', 'watupro'), __('quizzes', 'watupro'));
		}
		else $bundle_name = $bundle->name;
 
		
		ob_start();
		
		// display extra info, i.e. price?
		$info = '';
		if(!empty($atts['info'])) {
			$info = $atts['info'];
			if(!empty($atts['info_p'])) {
				$info = empty($atts['center']) ? '<p class="watupro-bundle-info">'.$info.'</p>' : '<p class="watupro-bundle-info" align="center">'.$info.'</p>';
			}
		}
		
		switch($mode) {
			case 'paypoints':
				$paypoints_price = get_option('watupro_paypoints_price');
				$paypoints_button = get_option('watupro_paypoints_button');
				
				$cost_in_points = round($bundle->price * $paypoints_price);
				$user_points = get_user_meta($user_ID, 'watuproplay-points', true);	
				
				if($user_points < $cost_in_points) $paybutton = __('Not enough points.', 'watupro');
				else {
					$url = admin_url("admin-ajax.php?action=watupro_pay_with_points");
					$paybutton = "<input type='button' value='".sprintf(__('Pay %d points', 'watupro'), $cost_in_points)."' onclick='WatuPROPay.payWithPoints({$bundle->ID}, \"$url\", 1, \"{$bundle->redirect_url}\");'>";
				}
				
				// replace the codes in the design
				$paypoints_button = str_replace('{{{points}}}', $cost_in_points, $paypoints_button);
				$paypoints_button = str_replace('{{{user-points}}}', $user_points, $paypoints_button);
				$paypoints_button = str_replace('{{{button}}}', $paybutton, $paypoints_button);
				$paypoints_button = stripslashes($paypoints_button);
				
				if(!empty($info)) {
					$info = str_replace('{{{cost}}}', $cost_in_points, $info);				
					echo $info;
				}

				echo do_shortcode($paypoints_button);
			break;			
			
			case 'stripe':
				include_once(WATUPRO_PATH.'/i/lib/Stripe.php');
 
				$stripe = array(
				  'secret_key'      => get_option('watupro_stripe_secret'),
				  'publishable_key' => get_option('watupro_stripe_public')
				);
				 
				\Stripe\Stripe::setApiKey($stripe['secret_key']);
				
				if(!empty($info)) {
					$info = str_replace('{{{cost}}}', $bundle->price, $info);				
					echo $info;
				}
				?>
				<form method="post">
					<?php if(!empty($atts['center'])):?><p align="center"><?php endif;?>
				  <script src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
				          data-key="<?php echo $stripe['publishable_key']; ?>"
				          data-amount="<?php echo $bundle->price*100?>" data-description="<?php echo $bundle_name?>" data-currency="<?php echo $currency?>"></script>
				<input type="hidden" name="stripe_bundle_pay" value="1">
				<input type="hidden" name="bundle_id" value="<?php echo $bundle->ID?>">
				   <?php if(!empty($atts['center'])):?></p><?php endif;?>
				</form>
			<?php break;			
			
			case 'paypal':
			default:
			if(!empty($_GET['watupro_pdt'])) WatuPROPayment::paypal_ipn(); // in case return URL is here, check payment
			
			if(!empty($info)) {
				$info = str_replace('{{{cost}}}', $bundle->price, $info);				
				echo $info;
			}			
			
			$return_url = $bundle->redirect_url ? $bundle->redirect_url : get_permalink( $post->ID );
			$use_pdt = get_option('watupro_use_pdt');
			if($use_pdt == 1) $return_url = esc_url(add_query_arg(array('watupro_pdt' => 1, 'watupro_pdt_bundle'=>1), $return_url));
			$paypal_email = get_option("watupro_paypal");
			$paypal_host = "www.paypal.com";
			$paypal_sandbox = get_option('watupro_paypal_sandbox');
			$paypal_button = get_option('watupro_paypal_button');
			if(empty($paypal_button)) $paypal_button = 'https://www.paypal.com/en_US/i/btn/x-click-butcc.gif';
		if($paypal_sandbox == '1') $paypal_host = 'www.sandbox.paypal.com'; ?>
			<form action="https://<?php echo $paypal_host?>/cgi-bin/webscr" method="post">
				<?php if(!empty($atts['center'])):?><p align="center"><?php endif;?>
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="<?php echo $paypal_email?>">
				<input type="hidden" name="item_name" value="<?php echo $bundle_name?>">
				<input type="hidden" name="item_number" value="<?php echo $bundle->ID?>">
				<input type="hidden" name="amount" value="<?php echo $bundle->price?>">
				<input type="hidden" name="return" value="<?php echo $return_url;?>">
				<input type="hidden" name="notify_url" value="<?php echo site_url('?watupro=paypal_bundle&user_id='.$user_ID, 'https');?>">
				<input type="hidden" name="no_shipping" value="1">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="<?php echo $currency;?>">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="bn" value="PP-BuyNowBF">
				<input type="image" src="<?php echo $paypal_button?>" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
				<?php if(!empty($atts['center'])):?></p><?php endif;?>
			</form> 
			<?php break;
			
			case 'custom':
				$contents = stripslashes(get_option('watupro_other_payments'));
				$contents = str_replace('[AMOUNT]', $bundle->price, $contents);
				$contents = str_replace('[USER_ID]', $user_ID, $contents);
				$contents = str_replace('[EXAM_TITLE]', $bundle_name, $contents);
				$contents = str_replace('[EXAM_ID]', $bundle->ID, $contents);
				$contents = str_replace('[ITEM_TYPE]', 'bundle', $contents);
				
				if(!empty($info)) {					
					$info = str_replace('{{{cost}}}', $bundle->price, $info);
				}				
				
				return $info . do_shortcode($contents);
			break;
		}
		
		$contents = ob_get_clean();
		return $contents;		
	} // end bundle_button
	
	// view and manage certificate payments
	static function certificate_payments() {
		global $wpdb, $user_ID;
		
		// select certificate
		$certificate = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_CERTIFICATES." WHERE ID=%d", intval($_GET['certificate_id'])));
		$offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
		
		// add payment manually
		if(!empty($_POST['add_payment']) and check_admin_referer('watupro_certificate_payments')) {
			// find the given user first
			$user = get_user_by('login', $_POST['user_login']);
			if(empty($user->user_login)) wp_die(__('Unrecognized user login', 'watupro'));
			
			// now insert the payment
			$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_PAYMENTS." SET
				certificate_id=%d, user_id=%d, date=CURDATE(), amount=%s, status='completed', paycode='manual', method='manual'", 
				$certificate->ID, $user->ID, floatval($_POST['amount'])));
				
			watupro_redirect("admin.php?page=watupro_certificate_payments&certificate_id=".$certificate->ID."&offset=$offset");	
		}
		
		// delete payment
		if(!empty($_GET['delete'])) {
			$wpdb->query( $wpdb->prepare("DELETE FROM ".WATUPRO_PAYMENTS." WHERE id=%d", intval($_GET['id'])));
			watupro_redirect("admin.php?page=watupro_certificate_payments&certificate_id=".$certificate->ID."&offset=$offset");
		}
		
		// approve/unapprove payment
		if(!empty($_GET['change_status'])) {
			$status = empty($_GET['status']) ? 'pending' : 'completed';
			$wpdb->query( $wpdb->prepare("UPDATE ".WATUPRO_PAYMENTS." SET status='$status' WHERE id=%d", intval($_GET['id'])));
			watupro_redirect("admin.php?page=watupro_certificate_payments&certificate_id=".$certificate->ID."&offset=$offset");
		}
		
		
		$payments = $wpdb->get_results($wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS tP.*, tC.title as certificate, tU.user_login as user_login 
				FROM ".WATUPRO_PAYMENTS." tP LEFT JOIN {$wpdb->users} tU ON tU.ID = tP.user_id
				JOIN ".WATUPRO_CERTIFICATES." tC ON tC.ID = tP.certificate_id
				WHERE tC.id=%d ORDER BY tP.ID DESC LIMIT %d, 100", $certificate->ID, $offset));
			
		$count = $wpdb->get_var("SELECT FOUND_ROWS()");	
		
		$currency = get_option('watupro_currency');
		$paypoints_price = get_option('watupro_paypoints_price');
		
		if(@file_exists(get_stylesheet_directory().'/watupro/i/certificate-payments.html.php')) require get_stylesheet_directory().'/watupro/i/certificate-payments.html.php';
		else require WATUPRO_PATH."/i/views/certificate-payments.html.php";
	}
}