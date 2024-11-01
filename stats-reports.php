<?php
/*
Plugin Name: WordPress.com Stats Reports
Plugin URI: http://www.malaiac.net/529-wordpress-com-stats-reports.html
Description: Displays views, post/page views, referrers, and clicks on your blog front end. Requires <a target="_blank" href="http://wordpress.org/extend/plugins/stats/">WordPress.com Stats Plugin</a>.
Author: Malaiac
Version: 1.04
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Requires WordPress 2.1 or later. Not for use with WPMU.
*/

/**
 * STATS FETCHING AND DISPLAY
 */

/**
 * Returns cached or fresh data for one table
 * @param $table
 * @return array or false
 */
function stats_reports_get($table, $force_refresh = false) {
  $table = sanitize_title($table);
  $tables = array('postviews','referrers','searchterms','clicks');
  if(!in_array($table,$tables)) return false;
  $cache_key = 'stats_reports_'.$table;

  $force_refresh = 0;

  /**
   * Double caching (legacy or options) because we do NOT want to make 10-15 seconds requests every time we have to display a post count !
   */
  $content = false;

  // try legacy cache
  if(!$force_refresh) $content = wp_cache_get($cache_key,'stats-reports');
  // try options cache
  if(!$force_refresh && !$content) {
    $content = get_option($cache_key);
    if($content && $content != -1) {
      $hours = max(1,absint(get_option('stats_reports_cache_duration')));
      if(!$content['date'] || $content['date'] < date('Y-m-d H:i:s',strtotime("-$hours hours")))
      $content = false;
    }
  }

  if(!$content) {
    $stats_options = get_option( 'stats_options' );
    if(!$stats_options['api_key'] || !$stats_options['blog_id']) return false;
    /**
     * According to : http://stats.wordpress.com/csv.php
     * Parameters:
     api_key     String    A secret unique to your WordPress.com user account.
     blog_id     Integer   The number that identifies your blog. Find it in other stats URLs.
     blog_uri    String    The full URL to the root directory of your blog. Including the full path.
     table       String    One of views, postviews, referrers, searchterms, clicks.
     post_id     Integer   For use with postviews table.
     end         String    The last day of the desired time frame. Format is 'Y-m-d' (e.g. 2007-05-01) and default is UTC date.
     days        Integer   The length of the desired time frame. Default is 30. "-1" means unlimited.
     limit       Integer   The maximum number of records to return. Default is 100. "-1" means unlimited.
     summarize   Flag      If present, summarizes all matching records.
     format      String    The format the data is returned in, 'csv' or 'xml'. Default is 'csv'.
     */
    $args = array(
      'api_key' => $stats_options['api_key'],
      'blog_id' => $stats_options['blog_id'],
      'blog_uri' => $stats_options['host'],
      'table' => $table,
      'days' => 720,
      'limit' => 500,
      'summarize' => 1,
      'format' => 'csv',
    );

    $url = 'http://stats.wordpress.com/csv.php?'.http_build_query($args);
    $request = wp_remote_request($url,'timeout=20');

    if(is_wp_error($request)) {
      $content = -1;
    }
    else {
      $content['data'] = stats_parse_content($request['body'],$table);
      $content['date'] = date('Y-m-d H:i:s');
    }
    wp_cache_add($cache_key,$content,'stats-reports');
    update_option($cache_key,$content);
  }

  return $content['data'];
}


function stats_parse_content($csv,$table) {
  if(!strlen($csv)) return false;

  $lines = explode("\n",$csv);
  unset($lines[0]);
  $results = array();
  foreach($lines as $line) {
    switch ($table) {
      case 'referrers' :
        $url = substr($line,0,strrpos($line,',')); // URLS might contain ,
        $count = substr($line,strrpos($line,',')+1);
        $results[$url] = $count;
        break;
      case 'postviews':
        $post_id = substr($line,0,strpos($line,','));
        $count = substr($line,strrpos($line,',')+1);
        if($post_id) $results[$post_id] = $count;
        break;
      case 'searchterms' :
        $term = substr($line,0,strrpos($line,','));
        $count = substr($line,strrpos($line,',')+1);
        $results[$term] = $count;
        break;
      case 'clicks' :
        $click = substr($line,0,strrpos($line,','));
        $count = substr($line,strrpos($line,',')+1);
        $results[$click] = $count;
        break;
    }
  }
  return $results;
}

/**
 * Usage :
 * stats_table('clicks'); // return all clicks data
 * stats_table('clicks','http://www.externalsite.com/'); // return click data for a given url
 * stats_table('searchterms');
 * stats_table('searchterms','foobar');
 * stats_table('postviews');
 * stats_table('postviews',3); // return view count for post 3
 * stats_table('referrers');
 * stats_table('referrers','http://www.inboundlink.com/');
 *
 * @param $table
 * @param $term
 *
 */
function stats_report($table,$term = '') {
  if(!$data = stats_reports_get($table)) return false;
  if(!empty($term)) {
    if(!isset($data[$term])) return false;
    return $data[$term];
  }
  return $data;
}

/**
 * Return full array of stats
 * views => view count,
 * clicks => array('external_url' => click count)
 * @param $post_id
 * @return unknown_type
 */
function stats_post_report($post_id = 0) {
  if(!$post_id) {
    global $post; $post_id = $post->ID;
  }
  if(!$post_id) return false;

  $stats = array();

  if(!$stats = wp_cache_get("stats_post_$post_id",'stats')) {
    // views
    $stats['views'] = stats_report('postviews',$post_id);

    // external clicks
    if($clicks) {
    $post = get_post($post_id);
    $stats['clicks'] = array();
    $regex = '#(?:[\'|"]{1})http(s?)://[A-Za-z0-9][A-Za-z0-9\-\.]+[A-Za-z0-9]\.[A-Za-z]{2,}[\43-\176]*(?:[\'|"]{1})#is';
    preg_match_all($regex,$post->post_content,$urls);
    if($urls) $urls = $urls[0];
    if(count($urls)) {
      $clicks = stats_reports_get('clicks');
      foreach($urls as $url) {
        $stats['clicks'][$url] = stats_report('clicks',$url);
      }
    }
    }
    wp_cache_add("stats_post_$post_id",$stats,'stats');
  }
  return $stats;
}

function stats_show_views($before = '<strong>',$after = '</strong>') {
  $stats = stats_post_report();
  $views = $stats['views'];
  if(!$views) return;
  echo $before . sprintf(__('%s views','stats-reports'),$views) . $after;
}

function stats_show_most($table = 'postviews',$args) {
  $defaults = array(
  'limit' => 5,
  'echo' => 1,
  'show_title' => 1,
  'show_description' => 0,
  'title_li' => '', 'title_before' => '<h2>', 'title_after' => '</h2>',
  'class' => "most_$table",
  'show_count' => 1,
  );
  $args = wp_parse_args($args,$defaults);
  extract($args,EXTR_OVERWRITE);

  // force refresh
  //if($table == 'postviews') stats_reports_get($table,1);

  $stats = stats_report($table);
  if(!$stats || !is_array($stats) || !count($stats)) return false;

  $best = $stats;
  arsort($best);
  $best = array_splice(array_keys($best),0,$limit);

  $output = '';
  $output .= "$title_before$title_li$title_after\n\t<ul class='most_$table'>\n";
  foreach($best as $key => $value) {
    if($table == 'postviews') {
      $post_id = $value;
      $count = $stats[$post_id];
      $permalink = get_permalink($post_id);
      $title = get_post_field('post_title',$post_id);
      $output .= "\n\t\t<li><a href='$permalink' title='$title'>$title</a> (".sprintf(__('%s views','stats-reports'),$count).")</li>";
    }
    elseif($table == 'clicks') {
      $url = $value;
      $count = $stats[$url];
      $output .= "\n\t\t<li><a href='$url'>$url</a>(".sprintf(__('%s clicks','stats-reports'),$count).")</li>";
    }
    elseif($table == 'referrers') {
      $url = $value;
      $count = $stats[$url];
      $output .= "\n\t\t<li><a href='$url'>$url</a>(".sprintf(__('%s visits','stats-reports'),$count).")</li>";
    }
    elseif($table == 'searchterms') {
      $term = $value;
      $count = $stats[$term];
      $permalink = get_option('home')."?s=$term";
      if($style && $style == 'cloud') {}
      else $output .= "\n\t\t<li><a href='$permalink'>$term</a> (".sprintf(__('%s visits','stats-reports'),$count).")</li>";
    }
  }
  $output .= "\n\t</ul>\n";

  $output = apply_filters("stats_reports_show_$table",$output);
  if($echo) echo $output;
  else return $output;
}

function stats_most_viewed($args = '') {
  $defaults = array('title_li' => __('Most viewed:','stats-reports'));
  $args = wp_parse_args($args,$defaults);
  echo stats_show_most('postviews',$args);
}
function stats_most_clicked($args = '') {
  $defaults = array('title_li' => __('Most clicked:','stats-reports'));
  $args = wp_parse_args($args,$defaults);
  echo stats_show_most('clicks',$args);
}
function stats_most_incoming($args = '') {
  $defaults = array('title_li' => __('Top referrers:','stats-reports'));
  $args = wp_parse_args($args,$defaults);
  echo stats_show_most('referrers',$args);
}
function stats_most_searched($args = '') {
  $defaults = array('title_li' => __('Search terms:','stats-reports'),'limit' => 15);
  $args = wp_parse_args($args,$defaults);
  echo stats_show_most('searchterms',$args);
}

/**
 * ADMIN FUNCTIONS
 */


/**
 * Load translation
 */
function stats_reports_action_init() {
  load_plugin_textdomain('stats-reports',false,'languages/stats-reports');
}
add_action('init','stats_reports_action_init');

function stats_reports_activate() {
  if(!get_option('stats_reports_cache_duration')) {
    add_option('stats_reports_cache_duration',24);
  }
}
register_activation_hook(__FILE__, 'stats_reports_activate');


function stats_reports_admin_page() {
	global $plugin_page;
	if(!function_exists('stats_get_options')) {
	  _e('Please activate WordPress.com Stats plugin','stats-reports');
	  return;
	}
	?>
	<div class="wrap">
  <h2><?php _e('WordPress.com Stats Reports','stats-reports'); ?></h2>
  <div class="narrow">

  <form method="post"><?php wp_nonce_field('stats'); ?> <input
  	type="hidden" name="action" value="update" />

  <p><label for="cache_duration"><?php _e('Cache duration (in hours)','stats-reports'); ?></label>
  <input type="text" name="cache_duration"
  	value="<?php echo get_option('stats_reports_cache_duration'); ?>" /></p>

  <input type="submit" value="<?php _e('Save Changes'); ?>" /></form>

	<br />
  <hr />
  <h3><?php _e('Template Tags :','stats-reports'); ?></h3>
  <?php _e('<ul>
  <li><strong>Top viewed posts </strong>: <pre>&lt;?php stats_most_viewed(); ?&gt;</pre></li>
  <li><strong>Top clicks (outbound links) </strong>: <pre>&lt;?php stats_most_clicked(); ?&gt;</pre></li>
  <li><strong>Top referrers (inbound links) </strong>: <pre>&lt;?php stats_most_incoming(); ?&gt;</pre></li>
  <li><strong>Top search terms </strong>: <pre>&lt;?php stats_most_searched(); ?&gt;</pre></li>
  <li><strong>Defaults arguments</strong> are </strong><pre>limit=5&echo=1&show_title=1&show_description=0&title_li=&lt;depending on function called&gt;&title_before=&lt;h2&gt;&title_after=&lt;/h2&gt;&class=most_&lt;table&gt;&show_count=1</pre>
  where &lt;table&gt; is one of [postviews,clicks,referrers,searchterms]</li>
  <li>You can <strong>display views count for a single post</strong> (Page or Post) with : <pre>&lt;?php if(function_exists(\'stats_show_views\')) stats_show_views(); ?&gt;</pre></li>
  </ul>','stats-reports'); ?>

  </div>
  </div>
	<?php
}



function stats_reports_admin_load() {
  global $plugin_page;
  if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'update' && $_POST['_wpnonce'] == wp_create_nonce('stats') ) {
    $hours = absint($_POST['cache_duration']);
    update_option('stats_reports_cache_duration',$hours);
    wp_redirect( "plugins.php?page=$plugin_page" );
    exit;
  }
}


function stats_reports_admin_menu() {
  $hook = add_submenu_page('plugins.php', __('WordPress.com Stats Reports','stats-reports'), __('WordPress.com Stats Reports','stats-reports'), 'manage_options', 'wpstatsreports', 'stats_reports_admin_page');
  add_action("load-$hook", 'stats_reports_admin_load');
}
add_action( 'admin_menu', 'stats_reports_admin_menu' );
