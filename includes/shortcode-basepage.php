<?php
/**
 * Class for SmartQa base page shortcode
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-2.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for SmartQa base page shortcode
 */
class SmartQa_BasePage_Shortcode {
	/**
	 * Instance.
	 *
	 * @var Instance
	 */
	protected static $instance = null;

	/**
	 * Get current instance.
	 */
	public static function get_instance() {
		// Create an object.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance; // Return the object.
	}

	/**
	 * Current page
	 *
	 * @var string
	 * @since 4.1.9
	 */
	public $current_page = '';

	/**
	 * Control the output of [smartqa] shortcode.
	 *
	 * @param array  $atts {
	 *     Attributes of the shortcode.
	 *
	 *     @type string  $categories                 Slug of question_category
	 *     @type string  $tags                       Slug of question_tag
	 *     @type string  $tax_relation           Taxonomy relation, see here @link https://codex.wordpress.org/Taxonomies
	 *     @type string  $tags_operator            Operator for question_tag taxonomy
	 *     @type string  $categories_operator  Operator for question_category taxonomy
	 *     @type string  $page                       Select a page to display.
	 *     @type boolean $hide_list_head           Hide list head?
	 *     @type string  $order_by                   Sort by.
	 *  }
	 * @param string $content Shortcode content.
	 * @return string
	 * @since 2.0.0
	 * @since 3.0.0 Added new attribute `hide_list_head` and `attr_order_by`.
	 */
	public function smartqa_sc( $atts, $content = '' ) {
		global $asqa_shortcode_loaded;

		// Drop current page variable cache. As we are allowing to change page from shortcode.
		wp_cache_delete( 'current_page', 'smartqa' );

		// Check if SmartQa shortcode already loaded.
		if ( true === $asqa_shortcode_loaded ) {
			return __( 'SmartQa shortcode cannot be nested.', 'smart-question-answer' );
		}

		wp_enqueue_script( 'smartqa-main' );
		wp_enqueue_script( 'smartqa-theme' );
		wp_enqueue_style( 'smartqa-main' );
		wp_enqueue_style( 'smartqa-fonts' );

		$asqa_shortcode_loaded = true;

		$this->attributes( $atts, $content );
		if (isset($atts['frontpage'])){ $test = $atts['frontpage'];
		$extraclass = "asqafrontpage".$test; } else { $extraclass = "";}
		

		ob_start();
		$locatti = $_SERVER['REQUEST_URI']; $invisi = "";
		if ( !is_user_logged_in() AND $locatti=="/fragen/eine-frage-stellen/" ) { $invisi = "asqainvisible"; 
	?><script>
		document.location.href="/";
	</script>

<?php
		} 

		echo '<div id="smartqa" class="smartqa '.$extraclass.' '.$invisi.'">'; 

			/**
			 * Action is fired before loading SmartQa body.
			 */
			do_action( 'asqa_before' );
			global $wpdb;
	        $table_name = $wpdb->prefix . 'posts';
	        $table_terms = $wpdb->prefix . 'terms';
	        $table_termsid = $wpdb->prefix . 'terms.term_id';
	        $table_terms_tax = $wpdb->prefix . 'term_taxonomy';
	        $table_terms_taxtermid = $wpdb->prefix . 'term_taxonomy.term_id';
	        $kattype = "question_category"; 

	        $type = "answer"; $pstatus = "publish";

	        $answerscount = $wpdb->get_row($wpdb->prepare("SELECT count(ID) as anzahl FROM $table_name WHERE post_type='%s' AND post_status='%s'", $type, $pstatus));
if ( $extraclass == "asqafrontpagetrue" ) {
		?>
		
		<div class="widget-text wp_widget_smartqa_visitorquestion_plugin_box" style="text-align:center;"><div class="smartqa_vq_titlebox"></div><div class="smartqa_vq_solutioncountbox"><p>Insgesamt wurden im Computerwissen Club schon <span class="asqa_answerscount"><b><?php echo $answerscount->anzahl; ?></b></span> Lösungen präsentiert!</p></div><div class="smartqa_vq_alltoactionbox"><h1 class="smartqa_vq_calltoactiontextmaxwidth">Stellen Sie Ihre Computer-Frage allen Experten vom Computerwissen Club</h1></div><div class="smartqa_vq_alltoactiondescbox"><p>
		<?php if ( is_user_logged_in() ) {
				    echo '(So einfach geht’s: Klick auf Frage stellen, Kategorie auswählen, Titel eingeben, Frage formulieren und absenden)<br /><img src="https://wpclub.webtyphoon.productions/wp-content/uploads/2022/11/arrowicon.png" alt="" border="0"><p style="text-align: center;"><a class="produktboxbutton" style="margin: 0!important; font-size: 20px;" href="/fragen/eine-frage-stellen/">Frage stellen</a></p>';
				} else {
				    echo '(So einfach geht’s: Einfach Titel eingeben, Kategorie auswählen, Frage formulieren und Frage absenden.)';
				}
		?></p></div>
        </div><?php }
 			if ( $extraclass == "" ){ asqa_page( $this->current_page );}
       
			// Include theme file.
        if ( $extraclass == "asqafrontpagetrue" ) { if ( !is_user_logged_in() ) {
			asqa_page( $this->current_page );}} 

		echo '<div class="asqa_closebutton"><button name="button" id="fragebereichclose" style="display: none; margin: 0px auto;" onclick="fragebereichschliessen()">Fragebereich schliessen</button></div>';
		echo '</div><div id="asqa-alert-success" class="asqa-alert asqa-alert-success" style="display:none;">Vielen Dank. Ihre Frage wird zwischengespeichert.<br>
<br>Damit Sie über die Experten-Lösung per E-Mail informiert werden können, melden Sie sich bitte oben rechts mit Ihren Zugangsdaten an. Sie sind neu im Computerwissen Club? Dann klicken Sie <a href="/registrierung">hier</a>.</div>';
		// Linkback to author.
		if ( ! asqa_opt( 'author_credits' ) ) {
			echo '<div class="asqa-credits">' . esc_attr__( 'Question and answer is powered by', 'smart-question-answer' ) . ' <a href="https://extensionforge.com" target="_blank">extensionforge.com</a></div>';
		}
		?>



<script type="text/javascript">	
	function asqa_uploadclick(){
		document.getElementById('form_question-post_attachment').click();	
	}

	function asqa_delete_file_in_list(id){
		var result = id;
		maxuploadsize="<?php echo round( asqa_opt( 'max_upload_size' ) / ( 1024 * 1024 ), 2 ); ?>";
		
		const myArray = result.split("-");
		var number = myArray[2];
		var find1 = "form_question-post_attachmentx-"+number;
		var find2 = "form_question-post_attachmentx-"+number+"-filenamedisplay";
		document.getElementById(find1).remove();
		document.getElementById(find2).remove();
		document.getElementById("asqa_upload_button_fake").innerHTML = "Dateien hochladen (Maximale Dateigröße: <b>"+maxuploadsize+" MB</b>)";

		document.getElementById("asqa_upload_button_fake").setAttribute("onclick","asqa_uploadclick()");
		document.getElementById("asqa_maximum_attachs").setAttribute("style","color:#666;");
		
	}


    function switch_question() { 
    	x = document.getElementById('form_question');
        y = document.getElementsByClassName('asqa-field-form_question-category');
        y[0].style.display = "block";
        y = document.getElementsByClassName('asqa-field-form_question-post_content');
        y[0].style.display = "block";
       
        document.getElementById('fragebereichclose').style.display = 'block';
        xi = document.getElementsByClassName('asqa-btn-insertimage')[0];
        //x.style.display = "none";
        test = document.getElementById("asqa_description_question");
        if (test!==null){
        test.style.display = "block";} else {
        	var iDiv = document.createElement('div');
		iDiv.id = 'asqa_description_question';
		iDiv.className = 'asqa_description_question';
		iDiv.innerHTML = '<p>Damit unsere Experten Ihre Frage schnellst möglich beantworten können, werden Sie nach dem Senden gebeten sich anzumelden oder zu registrieren, sofern Sie noch kein Konto haben.</p><p>Felder mit * markiert, sind Pflichtfelder und werden zur korrekten Übergabe Ihrer Frage benötigt.</p>';

		// Create the inner div before appending to the body
		
		x.appendChild(iDiv);

        }
    }

    function check_questionform() {
        return true;
    }

    function fragebereichschliessen() {
        x = document.getElementById('form_question');
        y = document.getElementsByClassName('asqa-field-form_question-category');
        y[0].style.display = "none";
        y = document.getElementsByClassName('asqa-field-form_question-post_content');
        y[0].style.display = "none";
        y = document.getElementsByClassName('asqa-field-form_question-post_attachment');
        //y[0].style.display = "none";
        document.getElementById('fragebereichclose').style.display = 'none';

        test = document.getElementById("asqa_description_question");
        if (test!==null){
        test.style.display = "none";}
    }
    
</script>
		<script>
			document.getElementById("form_question-post_title").setAttribute("onclick", "switch_question()");
		</script><?php

		if ( $extraclass == "" ){
			?>	<script type="text/javascript">x = document.getElementById('form_question');
        y = document.getElementsByClassName('asqa-field-form_question-category');
        y[0].style.display = "block";
        y = document.getElementsByClassName('asqa-field-form_question-post_content');
        y[0].style.display = "block";
       
        document.getElementById('fragebereichclose').style.display = 'none';
        x = document.getElementsByClassName('asqa-field-form_question-post_attachment')[0];
        x.style.display = "block";</script><?php
		}
		

		wp_reset_postdata();
		$asqa_shortcode_loaded = false;
		return ob_get_clean();
	}

	/**
	 * Get attributes from shortcode and set it as query var.
	 *
	 * @param array  $atts Attributes.
	 * @param string $content Content.
	 *
	 * @since 3.0.0
	 * @since 4.1.8 Added `post_parent` attribute.
	 */
	public function attributes( $atts, $content ) {
		global $wp;

		if ( isset( $atts['categories'] ) ) {
			$categories = explode( ',', str_replace( ', ', ',', $atts['categories'] ) );
			$wp->set_query_var( 'asqa_categories', $categories );
		}

		if ( isset( $atts['tags'] ) ) {
			$tags = explode( ',', str_replace( ', ', ',', $atts['tags'] ) );
			$wp->set_query_var( 'asqa_tags', $tags );
		}

		if ( isset( $atts['tax_relation'] ) ) {
			$tax_relation = $atts['tax_relation'];
			$wp->set_query_var( 'asqa_tax_relation', $tax_relation );
		}

		if ( isset( $atts['tags_operator'] ) ) {
			$tags_operator = $atts['tags_operator'];
			$wp->set_query_var( 'asqa_tags_operator', $tags_operator );
		}

		if ( isset( $atts['categories_operator'] ) ) {
			$categories_operator = $atts['categories_operator'];
			$wp->set_query_var( 'asqa_categories_operator', $categories_operator );
		}

		// Load specefic SmartQa page.
		if ( isset( $atts['page'] ) ) {
			$this->current_page = $atts['page'];
			set_query_var( 'asqa_page', $atts['page'] );
			$_GET['asqa_page'] = $atts['page'];
		}

		if ( isset( $atts['hide_list_head'] ) ) {
			set_query_var( 'asqa_hide_list_head', (bool) $atts['hide_list_head'] );
			$_GET['asqa_hide_list_head'] = $atts['hide_list_head'];
		}

		// Sort by.
		if ( isset( $atts['order_by'] ) ) {
			$_GET['filters'] = array( 'order_by' => $atts['order_by'] );
		}

		// parent post.
		if ( isset( $atts['post_parent'] ) ) {
			set_query_var( 'post_parent', $atts['post_parent'] );
		}
	}
}
