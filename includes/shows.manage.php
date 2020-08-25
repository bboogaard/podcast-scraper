<?php

namespace PodcastScraper;

class ShowManager {

    private $show_db;

    public function __construct() {

        add_menu_page(__('Podcast Scraper', 'podcast-scraper'), __('Podcast Scraper', 'podcast-scraper'), 'manage_options', 'podcast_scraper_settings', array( $this, 'render_manager' ));
        add_submenu_page(NULL, __('Podcast Scraper', 'podcast-scraper'), __('Nieuwe podcast', 'podcast-scraper'), 'manage_options', 'podcast_scraper_add', array( $this, 'render_new' ));
        add_submenu_page(NULL, __('Podcast Scraper', 'podcast-scraper'), __('Bewerk podcast', 'podcast-scraper'), 'manage_options', 'podcast_scraper_edit', array( $this, 'render_update' ));

        $this->show_db = new ShowDb();

        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

    }

    public function render_manager() {

        if (array_key_exists('message', $_GET)) {
            switch ($_GET['message']) {
                case 'deleted':
                    printf('<div class="updated"><p><strong>%s</strong></p></div>', __('Podcast verwijderd', 'podcast-scraper'));
                    break;
                case 'sync':
                    printf('<div class="updated"><p><strong>%s</strong></p></div>', __('Podcast gesynchroniseerd', 'podcast-scraper'));
                    break;
            }
        }

        $table = new ShowTable();
        $table->prepare_items();
        $new_url = add_query_arg(
            array(
                'page' => 'podcast_scraper_add'
            ),
            admin_url('admin.php')
        );

        echo '<div class="wrap">';
        printf('    <div id="icon-options-general" class="icon32"><br></div><h2>%s <a href="%s" class="add-new-h2">%s</a></h2>', __('Podcasts', 'podcast-scraper'), $new_url, __('Nieuwe podcast', 'podcast-scraper'));

        echo '        <form id="podcast-form" method="get">';
        printf('            <input type="hidden" name="page" value="%s" />', $_REQUEST['page']);

        $table->display();

        echo '        </form>';
        echo '</div>';

    }

    public function render_new() {

        if ($_POST) {
            if (check_admin_referer('podcast-scraper', 'podcast-scraper')) {
                $max_episodes = isset($_POST['max_episodes']) ? $_POST['max_episodes'] : 0;
                $this->show_db->add_show(array(
                    'show_id' => $_POST['show_id'],
                    'scraper_handle' => $_POST['scraper_handle'],
                    'max_episodes' => $max_episodes,
                    'num_episodes' => $_POST['num_episodes']
                ));
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'podcast_scraper_settings'
                ),
                admin_url('admin.php')
            );
            wp_redirect($redirect_url);
        }

        $form_url = add_query_arg(
            array(
                'page' => 'podcast_scraper_add'
            ),
            admin_url('admin.php')
        );

        echo '<div class="wrap">';
        printf('    <div id="icon-options-general" class="icon32"><br></div><h2>%s</h2>', __('Nieuwe podcast', 'podcast-scraper'));
        echo '        <div class="form-wrap">';
        printf('            <form id="podcast-form" method="post" action="%s">', $form_url);

        wp_nonce_field( 'podcast-scraper', 'podcast-scraper');

        printf('                <label for="show_id">%s</label>', __('Naam podcast', 'podcast-scraper'));
        echo '                <input name="show_id" id="show_id" type="text" value="" class="regular-text" />';

        echo '<br/>';

        printf('                <label for="scraper_handle">%s</label>', __('Podcast-site', 'podcast-scraper'));
        echo '                <select name="scraper_handle" id="scraper_handle">';

        $options = podcast_scraper_get_scrapers();
        foreach ($options as $key => $value):
        printf('                    <option value="%s">%s</option>', $key, $value);
        endforeach;

        echo '                </select>';

        echo '<br/>';

        echo '                <label for="max_episodes">';
        echo '                <input name="limit_episodes" id="limit_episodes" type="checkbox" />';
        echo __('Beperk te synchroniseren afleveringen tot: ', 'podcast-scraper');
        echo '                <input name="max_episodes" id="max_episodes" type="number" value="0" class="small-text" /></label>';

        echo '<br/>';

        printf('                <label for="num_episodes">%s</label>', __('Aantal te synchroniseren afleveringen per sessie', 'podcast-scraper'));
        echo '                <input name="num_episodes" id="num_episodes" type="number" value="10" class="small-text" />';

        submit_button(__('Opslaan', 'podcast-scraper'));

        echo '            </form>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';

    }

    public function render_update() {

        if ($_POST) {
            if (check_admin_referer('podcast-scraper', 'podcast-scraper')) {
                $max_episodes = isset($_POST['max_episodes']) ? $_POST['max_episodes'] : 0;
                $this->show_db->update_show(
                    $_GET['id'],
                    array(
                        'show_id' => $_POST['show_id'],
                        'scraper_handle' => $_POST['scraper_handle'],
                        'max_episodes' => $max_episodes,
                        'num_episodes' => $_POST['num_episodes']
                    )
                );
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'podcast_scraper_settings'
                ),
                admin_url('admin.php')
            );
            wp_redirect($redirect_url);
        }

        $show = $this->show_db->get_show($_GET['id']);

        $form_url = add_query_arg(
            array(
                'page' => 'podcast_scraper_edit',
                'id' => $_GET['id']
            ),
            admin_url('admin.php')
        );

        echo '<div class="wrap">';
        printf('    <div id="icon-options-general" class="icon32"><br></div><h2>%s</h2>', __('Bewerk podcast', 'podcast-scraper'));
        echo '        <div class="form-wrap">';
        printf('            <form id="podcast-form" method="post" action="%s">', $form_url);

        wp_nonce_field( 'podcast-scraper', 'podcast-scraper');

        printf('                <label for="show_id">%s</label>', __('Naam podcast', 'podcast-scraper'));
        printf('                <input name="show_id" id="show_id" type="text" value="%s" class="regular-text" />', esc_attr($show->show_id));

        echo '<br/>';

        printf('                <label for="scraper_handle">%s</label>', __('Podcast-site', 'podcast-scraper'));
        echo '                <select name="scraper_handle" id="scraper_handle">';

        $options = podcast_scraper_get_scrapers();
        foreach ($options as $key => $value):
        $checked = $key == $show->scraper_handle ? ' checked' : '';
        printf('                    <option value="%s"%s>%s</option>', $key, $checked, $value);
        endforeach;

        echo '                </select>';

        echo '<br/>';

        echo '                <label for="max_episodes">';
        $checked = $show->max_episodes ? ' checked' : '';
        printf('                <input name="limit_episodes" id="limit_episodes" type="checkbox"%s />', $checked);
        echo __('Beperk te synchroniseren afleveringen tot: ', 'podcast-scraper');
        printf('                <input name="max_episodes" id="max_episodes" type="number" value="%d" class="small-text" /></label>', $show->max_episodes);

        echo '<br/>';

        printf('                <label for="num_episodes">%s</label>', __('Aantal te synchroniseren afleveringen per sessie', 'podcast-scraper'));
        printf('                <input name="num_episodes" id="num_episodes" type="number" value="%d" class="small-text" />', $show->num_episodes);

        submit_button(__('Opslaan', 'podcast-scraper'));

        echo '            </form>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';

    }

    public function admin_enqueue_scripts() {

        wp_enqueue_script(
            'podcast-scraper-edit-show',
            podcast_scraper_url('js/edit_show.js'),
            array('jquery'),
            filemtime(PODCAST_SCRAPER_PATH . '/js/edit_show.js')
        );

    }

}

class ShowManagerFactory {

    public static function register() {

        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));

    }

    public static function add_admin_menu() {

        $show_manager = new ShowManager();

    }

}
