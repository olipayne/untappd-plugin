<?php

use Carbon\Carbon;

if (! class_exists('Untappd_Plugin')) {

    /**
     * Main / front controller class
     *
     * Untappd_Plugin is an object-oriented/MVC base for building WordPress plugins
     */
    class Untappd_Plugin extends Untappd_Module
    {
        protected static $readable_properties  = array();    // These should really be constants, but PHP doesn't allow class constants to be arrays
        protected static $writeable_properties = array();
        protected $modules;

        const VERSION    = '0.1';
        const PREFIX     = 'untappd_';
        const DEBUG_MODE = false;


        /*
		 * Magic methods
		 */

        /**
         * Constructor
         *
         * @mvc Controller
         */
        protected function __construct()
        {
            $this->register_hook_callbacks();

            $this->modules = array(
                'Untappd_Settings'    => Untappd_Settings::get_instance(),
                'Untappd_Cron'        => Untappd_Cron::get_instance()
                );
        }


        /*
		 * Static methods
		 */

        /**
         * Enqueues CSS, JavaScript, etc
         *
         * @mvc Controller
         */
        public static function load_resources()
        {
            wp_register_script(
                self::PREFIX . 'untappd-plugin',
                plugins_url('javascript/untappd-plugin.js', dirname(__FILE__)),
                array( 'jquery' ),
                self::VERSION,
                true
            );

            wp_register_style(
                self::PREFIX . 'admin',
                plugins_url('css/admin.css', dirname(__FILE__)),
                array(),
                self::VERSION,
                'all'
            );

            if (is_admin()) {
                wp_enqueue_style(self::PREFIX . 'admin');
            } else {
                wp_enqueue_script(self::PREFIX . 'untappd-plugin');
            }
        }

        /**
         * Clears caches of content generated by caching plugins like WP Super Cache
         *
         * @mvc Model
         */
        protected static function clear_caching_plugins()
        {
            // WP Super Cache
            if (function_exists('wp_cache_clear_cache')) {
                wp_cache_clear_cache();
            }

            // W3 Total Cache
            if (class_exists('W3_Plugin_TotalCacheAdmin')) {
                $w3_total_cache = w3_instance('W3_Plugin_TotalCacheAdmin');

                if (method_exists($w3_total_cache, 'flush_all')) {
                    $w3_total_cache->flush_all();
                }
            }
        }


        /*
		 * Instance methods
		 */

        /**
         * Prepares sites to use the plugin during single or network-wide activation
         *
         * @mvc Controller
         *
         * @param bool $network_wide
         */
        public function activate($network_wide)
        {
            if ($network_wide && is_multisite()) {
                $sites = wp_get_sites(array( 'limit' => false ));

                foreach ($sites as $site) {
                    switch_to_blog($site['blog_id']);
                    $this->single_activate($network_wide);
                    restore_current_blog();
                }
            } else {
                $this->single_activate($network_wide);
            }
        }

        /**
         * Runs activation code on a new WPMS site when it's created
         *
         * @mvc Controller
         *
         * @param int $blog_id
         */
        public function activate_new_site($blog_id)
        {
            switch_to_blog($blog_id);
            $this->single_activate(true);
            restore_current_blog();
        }

        /**
         * Prepares a single blog to use the plugin
         *
         * @mvc Controller
         *
         * @param bool $network_wide
         */
        protected function single_activate($network_wide)
        {
            foreach ($this->modules as $module) {
                $module->activate($network_wide);
            }

            //flush_rewrite_rules();
        }

        /**
         * Rolls back activation procedures when de-activating the plugin
         *
         * @mvc Controller
         */
        public function deactivate()
        {
            foreach ($this->modules as $module) {
                $module->deactivate();
            }

            //flush_rewrite_rules();
        }

        /**
         * Register callbacks for actions and filters
         *
         * @mvc Controller
         */
        public function register_hook_callbacks()
        {
            add_action('wp_enqueue_scripts', __CLASS__ . '::load_resources');
            add_action('admin_enqueue_scripts', __CLASS__ . '::load_resources');

            add_action('wpmu_new_blog', array( $this, 'activate_new_site' ));
            add_action('init', array( $this, 'init' ));
            add_action('init', array( $this, 'upgrade' ), 11);

            add_shortcode('untappd', array( $this, 'shortcode_brewery'));
        }

        public function shortcode_brewery($atts)
        {
            $args = shortcode_atts(array(
                'brewery' => ''
                ), $atts);

            if ($args['brewery'] == '') {
                return 'No brewery argument set';
            }

            $brewery_id = (int) $args['brewery'];

            $request_uri = 'https://api.untappd.com/v4/brewery/checkins/' . $brewery_id;

            $secret_key = $this->modules['Untappd_Settings']->settings['basic']['field-secret-key'];
            $client_id = $this->modules['Untappd_Settings']->settings['basic']['field-client-id'];

            // Add query args to authenticate
            $request_uri = add_query_arg('client_id', $client_id, $request_uri);
            $request_uri = add_query_arg('client_secret', $secret_key, $request_uri);

            $json = json_decode(file_get_contents($request_uri));

            // echo '<pre>';
            // var_dump($json->response->checkins->items);
            // echo '</pre>';

            $checkins = $json->response->checkins->items;

            $result = '<table class="table">';
            foreach ($checkins as $checkin) {
                //var_dump($checkin);
                $time_stamp = new Carbon($checkin->created_at);
                $result .= '<tr>';
                $result .= '<td>';
                $result .= '<a target="_blank" href="https://untappd.com/user/' . $checkin->user->user_name . '/checkin/' . $checkin->checkin_id . '">' . $checkin->user->first_name . '</a> drank ';
                $result .= '<a target="_blank" href="https://untappd.com/b/' . $checkin->beer->beer_slug .'/' . $checkin->beer->bid .'">' . $checkin->beer->beer_name . '</a>';
                if ($checkin->rating_score > 0) {
                    $result .= ' and rated it ' . $checkin->rating_score;
                }
                if ($checkin->checkin_comment != '') {
                    $result .= '</br>';
                    $result .= '<i><small>"' . $checkin->checkin_comment . '"</small></i>';
                }
                $result .= '</br>';
                $result .= ' <small>' . $time_stamp->diffForHumans() . '</small>';
                $result .= '</td>';
                $result .= '</tr>';
            }
            $result .= '</table>';
            
            return $result;
        }

        /**
         * Initializes variables
         *
         * @mvc Controller
         */
        public function init()
        {
            try {
                $instance_example = new Untappd_Instance_Class('Instance example', '42');
                //add_notice( $instance_example->foo .' '. $instance_example->bar );
            } catch (Exception $exception) {
                add_notice(__METHOD__ . ' error: ' . $exception->getMessage(), 'error');
            }
        }

        /**
         * Checks if the plugin was recently updated and upgrades if necessary
         *
         * @mvc Controller
         *
         * @param string $db_version
         */
        public function upgrade($db_version = 0)
        {
            if (version_compare($this->modules['Untappd_Settings']->settings['db-version'], self::VERSION, '==')) {
                return;
            }

            foreach ($this->modules as $module) {
                $module->upgrade($this->modules['Untappd_Settings']->settings['db-version']);
            }

            $this->modules['Untappd_Settings']->settings = array( 'db-version' => self::VERSION );
            self::clear_caching_plugins();
        }

        /**
         * Checks that the object is in a correct state
         *
         * @mvc Model
         *
         * @param string $property An individual property to check, or 'all' to check all of them
         * @return bool
         */
        protected function is_valid($property = 'all')
        {
            return true;
        }
    } // end Untappd_Plugin
}
