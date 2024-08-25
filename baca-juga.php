<?php

/**
 * Plugin Name: Baca Juga
 * Description: Menampilkan link 'Baca Juga' di setiap beberapa artikel.
 * Version: 1.4
 * Author: Kris
 * Author URI: https://google.com
 */

// Membuat menu pengaturan
function baca_juga_menu()
{
    add_options_page('Pengaturan Baca Juga', 'Baca Juga', 'manage_options', 'baca-juga', 'baca_juga_options_page');
}
add_action('admin_menu', 'baca_juga_menu');

// Menampilkan halaman pengaturan
function baca_juga_options_page()
{
?>
    <div class="wrap">
        <h2>Pengaturan Baca Juga</h2>
        <form action="options.php" method="post">
            <?php settings_fields('baca_juga_options'); ?>
            <?php do_settings_sections('baca_juga'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Interval Paragraf</th>
                    <td><input type="number" name="baca_juga_interval" value="<?php echo get_option('baca_juga_interval'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Teks Custom</th>
                    <td><input type="text" name="baca_juga_text" value="<?php echo get_option('baca_juga_text'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tipe Artikel Terkait</th>
                    <td>
                        <select name="baca_juga_type">
                            <option value="latest" <?php selected(get_option('baca_juga_type'), 'latest'); ?>>Terbaru</option>
                            <option value="random" <?php selected(get_option('baca_juga_type'), 'random'); ?>>Acak</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom CSS</th>
                    <td>
                        <textarea name="baca_juga_custom_css" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('baca_juga_custom_css')); ?></textarea>
                        <p class="description">Tambahkan CSS kustom untuk mengatur tampilan 'Baca Juga'.<br>
                            Rekomendasi CSS :<br><br>

                            div.baca-juga {<br>
    font-weight: bold;<br>
    font-style: italic;<br>
    margin-bottom: 20px;<br>
}<br>
div.baca-juga span {<br>
    margin-right: 5px;<br>
}
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

// Mendaftarkan pengaturan
function baca_juga_register_settings()
{
    register_setting('baca_juga_options', 'baca_juga_interval');
    register_setting('baca_juga_options', 'baca_juga_text');
    register_setting('baca_juga_options', 'baca_juga_type');
    register_setting('baca_juga_options', 'baca_juga_custom_css');
}
add_action('admin_init', 'baca_juga_register_settings');

// Menambahkan 'Baca Juga' ke konten
function baca_juga_content($content)
{
    if (!is_single()) {
        return $content;
    }

    $interval = get_option('baca_juga_interval', 3);
    $custom_text = get_option('baca_juga_text', 'Baca Juga:');
    $type = get_option('baca_juga_type', 'latest');

    // Menghitung jumlah paragraf
    $jumlah_paragraf = substr_count($content, '<p>');

    global $post;
    $categories = wp_get_post_categories($post->ID);
    $exclude_posts = array($post->ID); // Menyimpan ID postingan yang sudah ditampilkan

    $pisah_paragraf = explode('</p>', $content);
    foreach ($pisah_paragraf as $index => $paragraf) {
        if (($index + 1) % $interval === 0 && $index + 1 < $jumlah_paragraf) {
            $args = array(
                'category__in' => $categories,
                'post__not_in' => $exclude_posts,
                'posts_per_page' => -1, // Mengambil semua postingan
                'orderby' => $type === 'random' ? 'rand' : 'date',
                'order' => 'DESC'
            );
            $related_posts = get_posts($args);

            if ($related_posts) {
                $related_post = $related_posts[0]; // Mengambil satu postingan terkait
                $exclude_posts[] = $related_post->ID; // Menambahkan ID ke daftar yang dikecualikan

                $baca_juga_content = '<div class="baca-juga">';
                $baca_juga_content .= '<span>' . esc_html($custom_text) . '</span>';
                $baca_juga_content .= '<a href="' . get_permalink($related_post) . '">' . get_the_title($related_post) . '</a>';
                $baca_juga_content .= '</div>';

                wp_reset_postdata();

                $pisah_paragraf[$index] .= '</p>' . $baca_juga_content;
            } else {
                $pisah_paragraf[$index] .= '</p>';
            }
        } else {
            $pisah_paragraf[$index] .= '</p>';
        }
    }
    return implode('', array_slice($pisah_paragraf, 0, -1));
}
add_filter('the_content', 'baca_juga_content');

// Menambahkan Custom CSS ke header situs
function baca_juga_custom_css()
{
    $custom_css = get_option('baca_juga_custom_css');
    if (!empty($custom_css)) {
        echo '<style type="text/css">' . $custom_css . '</style>';
    }
}
add_action('wp_head', 'baca_juga_custom_css');

// Menambahkan link pengaturan di plugin page
function baca_juga_action_links($links)
{
    $settings_link = '<a href="options-general.php?page=baca-juga">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'baca_juga_action_links');
