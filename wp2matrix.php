<?php namespace sbronsted;

/**
 * Plugin Name:       Wp2Matrix
 * Plugin URI:        https://github.com/sorenbronsted/wp2matrix
 * Version:           1.0
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            Søren Brønsted
 * Author URI:        https://bronsted.dk/
 * License:           GPL v3
 * License URI:       https://github.com/sorenbronsted/wp2matrix/blob/master/LICENSE
 * Text Domain:       w2m
 * Description:       Display your blog post on matrix.org network.
 */
require plugin_dir_path( __FILE__ ).'vendor/autoload.php';

Wp2Matrix::instance();
