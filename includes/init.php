<?php
/**
 * Initialization functions for WPLMS ACADEMY MIGRATION
 * @author      H.K.Latiyan(VibeThemes)
 * @category    Admin
 * @package     Initialization
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPLMS_ACADEMY_INIT{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new WPLMS_ACADEMY_INIT();

        return self::$instance;
    }

    private function __construct(){
    	$theme = wp_get_theme(); // gets the current theme
        if ('Academy' == $theme->name || 'Academy' == $theme->parent_theme){
        	add_action( 'admin_notices',array($this,'migration_notice' ));
        	add_action('wp_ajax_migration_am_courses',array($this,'migration_am_courses'));
            add_action('wp_ajax_migration_am_course_to_wplms',array($this,'migration_am_course_to_wplms'));
        }

        $this->migration_status = get_option('wplms_academy_migration');
        if(('WPLMS' == $theme->name || 'WPLMS' == $theme->parent_theme) && !empty($this->migration_status)){
            add_action( 'admin_notices',array($this,'revert_migration' ));
            add_action('wp_ajax_revert_migrated_courses',array($this,'revert_migrated_courses'));
            add_action('wp_ajax_dismiss_message',array($this,'dismiss_message'));
        }
    }

    function migration_notice(){
    	$this->migration_status = get_option('wplms_academy_migration');

    	if(!empty($this->migration_status)){
            return;
        }

        $check = 1;
        if(!function_exists('woocommerce')){
            $check = 0;
            ?>
            <div class="welcome-panel" id="welcome_am_panel" style="padding-bottom:20px;width:96%">
                <h1><?php echo __('Please note: Woocommerce must be activated if using paid courses.','wplms-am'); ?></h1>
                <p><?php echo __('Please click on the button below to proceed to migration proccess','wplms-am'); ?></p>
                <form method="POST">
                    <input name="am_click" type="submit" value="<?php echo __('Click Here','wplms-am'); ?>" class="button">
                </form>
            </div>
            <?php
        }

        if(isset($_POST['am_click'])){
            $check = 1;
            ?> <style> #welcome_am_panel{display:none;} </style> <?php
        } 
        
        if($check){
        	?>
            <div id="migration_academy_courses" class="error notice ">
               <p id="am_message"><?php printf( __('Migrate academy coruses to WPLMS %s Begin Migration Now %s', 'wplms-am' ),'<a id="begin_wplms_academy_migration" class="button primary">','</a>'); ?>
                
               </p>
           <?php wp_nonce_field('security','security'); ?>
                <style>.wplms_am_progress .bar{-webkit-transition: width 0.5s ease-in-out;
    -moz-transition: width 1s ease-in-out;-o-transition: width 1s ease-in-out;transition: width 1s ease-in-out;}</style>
                <script>
                    jQuery(document).ready(function($){
                        $('#begin_wplms_academy_migration').on('click',function(){
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: ajaxurl,
                                data: { action: 'migration_am_courses', 
                                          security: $('#security').val(),
                                        },
                                cache: false,
                                success: function (json) {
                                    $('#migration_academy_courses').append('<div class="wplms_am_progress" style="width:100%;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');

                                    var x = 0;
                                    var width = 100*1/json.length;
                                    var number = 0;
                                    var loopArray = function(arr) {
                                        am_ajaxcall(arr[x],function(){
                                            x++;
                                            if(x < arr.length) {
                                                loopArray(arr);   
                                            }
                                        }); 
                                    }
                                    
                                    // start 'loop'
                                    loopArray(json);

                                    function am_ajaxcall(obj,callback) {
                                        
                                        $.ajax({
                                            type: "POST",
                                            dataType: 'json',
                                            url: ajaxurl,
                                            data: {
                                                action:'migration_am_course_to_wplms', 
                                                security: $('#security').val(),
                                                id:obj.id,
                                            },
                                            cache: false,
                                            success: function (html) {
                                                number = number + width;
                                                $('.wplms_am_progress .bar').css('width',number+'%');
                                                if(number >= 100){
                                                    $('#migration_academy_courses').removeClass('error');
                                                    $('#migration_academy_courses').addClass('updated');
                                                    $('#am_message').html('<strong>'+x+' '+'<?php _e('Courses successfully migrated from Academy to WPLMS <p style="font-size:16px;color:#0073aa;">Please deactivate Academy theme and activate the WPLMS theme and plugins OR install WPLMS theme to check migrated courses in wplms</p>','wplms-am'); ?>'+'</strong>');
                                                }
                                            }
                                        });
                                        // do callback when ready
                                        callback();
                                    } 
                                }
                            });
                        });
                    });
                </script>
            </div>
            <?php
        }
    }

    function revert_migration(){
        $this->revert_status = get_option('wplms_academy_migration_reverted');
        if(empty($this->revert_status)){
            ?>
            <div id="migration_academy_courses_revert" class="update-nag notice ">
               <p id="revert_message"><?php printf( __('Academy Courses migrated to WPLMS: Want to revert changes %s Revert Changes Now %s Otherwise dismiss this notice.', 'wplms-am' ),'<a id="begin_revert_migration" class="button primary">','</a><a id="dismiss_message" href=""><i class="fa fa-times-circle-o"></i>Dismiss</a>'); ?>
               </p>
            </div>
            <style>
                #migration_academy_courses_revert{width:97%;} 
                #dismiss_message {float:right;padding:5px 10px 10px 10px;color:#e00000;}
                #dismiss_message i {padding-right:3px;}
            </style>
            <?php wp_nonce_field('security','security'); ?>
            <script>
                jQuery(document).ready(function($){
                    $('#begin_revert_migration').on('click',function(){
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: { action: 'revert_migrated_courses', 
                                      security: $('#security').val(),
                                    },
                            cache: false,
                            success: function () {
                                $('#migration_academy_courses_revert').removeClass('update-nag');
                                $('#migration_academy_courses_revert').addClass('updated');
                                $('#migration_academy_courses_revert').html('<p id="revert_message">'+'<?php _e('WPLMS - ACADEMY MIGRATION : Migrated courses Reverted !', 'wplms-am' ); ?>'+'</p>');
                            }
                        });
                    });
                    $('#dismiss_message').on('click',function(){
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: { action: 'dismiss_message', 
                                      security: $('#security').val(),
                                    },
                            cache: false,
                            success: function () {
                                
                            }
                        });
                    });
                });
            </script>
            <?php
            return;
        }
    }

    function migration_am_courses(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-am');
            die();
        }

        global $wpdb;
        $courses = $wpdb->get_results("SELECT id,post_title FROM {$wpdb->posts} where post_type='course'");
        $json=array();
        foreach($courses as $course){
            $json[]=array('id'=>$course->id,'title'=>$course->post_title);
        }

        update_option('wplms_academy_migration',1);

        $this->migrate_units_and_taxonomy();

        print_r(json_encode($json));
        die();
    }

    function revert_migrated_courses(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-am');
            die();
        }
        update_option('wplms_academy_migration_reverted',1);
        $this->revert_migrated_posts();
        die();
    }

    function dismiss_message(){
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-am');
            die();
        }
        update_option('wplms_academy_migration_reverted',1);
        die();
    }

    function migration_am_course_to_wplms(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-am');
            die();
        }

        $this->migrate_course_settings($_POST['id']);
        $this->build_curriculum($_POST['id']);
    }

    function migrate_units_and_taxonomy(){
    	global $wpdb;
    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'lesson'");
        $wpdb->query("UPDATE {$wpdb->term_taxonomy} SET taxonomy = 'course-cat' WHERE taxonomy = 'course_category'");
    }

    function revert_migrated_posts(){
        global $wpdb;
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'lesson' WHERE post_type = 'unit'");
        $wpdb->query("UPDATE {$wpdb->term_taxonomy} SET taxonomy = 'course_category' WHERE taxonomy = 'course-cat'");
    }

    function migrate_course_settings($course_id){
        $max_students = get_post_meta($course_id,'_course_capacity',true);
        if(!empty($max_students)){
            update_post_meta($course_id,'vibe_max_students',$max_students);
        }

        $vibe_students = get_post_meta($course_id,'_course_popularity',true);
        if(!empty($vibe_students)){
            update_post_meta($course_id,'vibe_students',$vibe_students);
        }

        $course_price = get_post_meta($course_id,'_course_status',true);
        if(!empty($course_price)){
            if($course_price == 'premium'){
                $course_product = get_post_meta($course_id,'_course_product',true);
                if(!empty($course_product)){
                    update_post_meta($course_id,'vibe_course_free','H');
                    update_post_meta($course_id,'vibe_product',$course_product);
                }
            }
            if($course_price == 'private'){
                update_post_meta($course_id,'vibe_course_free','H');
            }
            if($course_price == 'free'){
                update_post_meta($course_id,'vibe_course_free','S');
            }
        }

        $rating = get_post_meta($course_id,'_course_rating',true);
        if(!empty($rating)){
            update_post_meta($course_id,'average_rating',$rating);
        }

        update_post_meta($course_id,'vibe_duration',999);
        update_post_meta($course_id,'vibe_course_duration_parameter',86400);
    }

    function build_curriculum($course_id){
        global $wpdb;
        $this->curriculum = array();
        $units = $wpdb->get_results("SELECT m.post_id as id FROM {$wpdb->postmeta} as m LEFT JOIN {$wpdb->posts} as p ON p.id = m.post_id WHERE m.meta_value = $course_id AND m.meta_key = '_lesson_course' ORDER BY p.menu_order ASC");
        if(!empty($units)){
            foreach($units as $unit){
                $this->curriculum[] = $unit->id;
                $this->migrate_unit_settings($unit->id);
                $this->migrate_quizzes($course_id,$unit->id);
            }
        }

        update_post_meta($course_id,'vibe_course_curriculum',$this->curriculum);
    }

    function migrate_unit_settings($unit_id){
        $unit_free = get_post_meta($unit_id,'_lesson_status',true);
        if(!empty($unit_free)){
            if($unit_free == 'free'){
                update_post_meta($unit_id,'vibe_free','S');
            }else{
                update_post_meta($unit_id,'vibe_free','H');
            }
        }

        update_post_meta($unit_id,'vibe_duration',10);
        update_post_meta($unit_id,'vibe_unit_duration_parameter',60);
    }

    function migrate_quizzes($course_id,$unit_id){
        global $wpdb;
        $quizzes = $wpdb->get_results("SELECT m.post_id as id FROM {$wpdb->postmeta} as m LEFT JOIN {$wpdb->posts} as p ON p.id = m.post_id WHERE m.meta_value = $unit_id AND m.meta_key = '_quiz_lesson'");
        if(!empty($quizzes)){
            foreach($quizzes as $quiz){
                $this->curriculum[] = $quiz->id;
                $this->migrate_quiz_settings($course_id,$quiz->id);
            }
        }
    }

    function migrate_quiz_settings($course_id,$quiz_id){
        $questions = get_post_meta($quiz_id,'_quiz_questions',true);
        if(!empty($questions)){
            $this->migrate_quiz_questions($quiz_id,$questions);
        }

        update_post_meta($quiz_id,'vibe_quiz_course',$course_id);
        update_post_meta($quiz_id,'vibe_duration',20);
        update_post_meta($quiz_id,'vibe_quiz_duration_parameter',60);
        update_post_meta($quiz_id,'vibe_quiz_auto_evaluate','S');
    }

    function migrate_quiz_questions($quiz_id,$questions){
        global $post;
        $author_id = $post->post_author;
        $quiz_questions = array('ques'=>array(),'marks'=>array());
        foreach($questions as $question){
            if(!empty($question['title'])){
                $insert_question = array(
                        'post_title' => $question['title'],
                        'post_content' => $question['title'],
                        'post_author' => $author_id,
                        'post_status' => 'publish',
                        'comment_status' => 'open',
                        'post_type' => 'question'
                    );
                $question_id = wp_insert_post( $insert_question );
                $quiz_questions['ques'][]=$question_id;
                $quiz_questions['marks'][]=1;
            }

            if(!empty($question['type'])){
                if($question['type'] == 'string'){
                    update_post_meta($question_id,'vibe_question_type','smalltext');
                }else{
                    update_post_meta($question_id,'vibe_question_type',$question['type']);
                }
            }

            $question_options = array();
            $correct_answers = array();
            if(!empty($question['answers'])){
                foreach($question['answers'] as $key => $val){
                    if(!empty($val['title'])){
                        $question_options[] = $val['title'];
                        if(isset($val['result'])){
                            $correct_answers[] = $val['title'];
                        }
                    }
                }

                if($question['type'] == 'single' || $question['type'] == 'multiple'){
                    $answers = array();
                    for ($i=0; $i < sizeof($question_options); $i++) { 
                        for ($j=0; $j < sizeof($correct_answers); $j++) {
                            if( $question_options[$i] == $correct_answers[$j] ){
                                $answers[] = $i+1;
                            }
                        }
                    }
                    update_post_meta($question_id,'vibe_question_options',$question_options);
                    if(!empty($answers)){
                        $answer = implode(',',$answers);
                        update_post_meta($question_id,'vibe_question_answer',$answer);
                    }
                }else{
                    update_post_meta($question_id,'vibe_question_options',$question_options);
                    $correct_answer = implode(',',$correct_answers);
                    update_post_meta($question_id,'vibe_question_answer',$correct_answer);
                }
            }
        }

        update_post_meta($quiz_id,'vibe_quiz_questions',$quiz_questions);
    }
}

WPLMS_ACADEMY_INIT::init();
