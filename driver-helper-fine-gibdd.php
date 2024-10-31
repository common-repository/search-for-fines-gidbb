<?php

/*
 * Plugin Name: Поиск и оплата штрафов ГИБДД. Партнерская программа
 * Description: Плагин позволяет искать и оплачивать штрафы ГИБДД по номеру автомобиля, номеру водительского удостоверения и номеру штрафа (УИН). Установивший плагин будет зарабатывать процент с каждого оплаченного штрафа
 * Plugin URI:  https://www.driver-helper.ru/shtrafy-gibdd/partnership
 * Author URI:  https://driver-helper.ru
 * Author:      Driver-helper.ru
 * Version:     1.0.1
 *
 *
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:     Укажите "true" для возможности активировать плагин по все сети сайтов (для Мультисайтовой сборки).
 */


global $driverHelperFineApiId, $driverHelperFineJsCode;

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DH_FINE_GIBDD_VERSION', '1.0.0' );
define( 'DH_URL_GET_JS', 'https://www.driver-helper.ru/shtrafy-gibdd/widget/js/' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function dh_fine_activate_plugin_name() {
    add_option('driverHelperFineApiId', null);
    add_option('driverHelperFineJsCode', null);
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function dh_fine_deactivate_plugin_name() {
    delete_option('driverHelperFineApiId');
    delete_option('driverHelperFineJsCode');
}

register_activation_hook( __FILE__, 'dh_fine_activate_plugin_name' );
register_deactivation_hook( __FILE__, 'dh_fine_deactivate_plugin_name' );


add_shortcode( 'finegibdd', 'dh_fine_finegibddShortcode' );


function dh_fine_finegibddShortcode( $atts ){
    $width = '100%';
    $height = '500px';

    if(is_array($atts) && array_key_exists('width', $atts)) {
        $width = $atts['width'];
    }

    if(is_array($atts) && array_key_exists('height', $atts)) {
        $height = $atts['height'];
    }

    $driverHelperFineJsCode = get_option('driverHelperFineJsCode');

    $driverHelperFineJsCode = preg_replace("#width\:\s*'[^']+'#uis", "width: '{$width}'", $driverHelperFineJsCode);
    $driverHelperFineJsCode = preg_replace("#height\:\s*'[^']+'#uis", "height: '{$height}'", $driverHelperFineJsCode);

    return $driverHelperFineJsCode;
}

// Hook for adding admin menus
add_action('admin_menu', 'dh_fine_add_pages');

// action function for above hook
function dh_fine_add_pages() {
    add_menu_page('Штрафы ГИБДД', 'Штрафы ГИБДД', 8, 'dh_fine_fine_settings', 'dh_fine_options_page', '/wp-content/plugins/driver-helper-fine-gibdd/public/icon/menuicon.png');
}


function dh_fine_options_page(){
    global $driverHelperFineApiId, $driverHelperFineJsCode;
    $driverHelperFineApiId = get_option('driverHelperFineApiId');
    $driverHelperFineJsCode = get_option('driverHelperFineJsCode');

    if ( array_key_exists('action', $_POST) && $_POST[ 'action' ] == 'setting' ) {
        if ( array_key_exists('api_code', $_POST)  ) {
            $driverHelperFineApiId = sanitize_text_field(trim($_POST['api_code']));
            update_option('driverHelperFineApiId', $driverHelperFineApiId);

            $responseJson = file_get_contents(DH_URL_GET_JS . $driverHelperFineApiId);
            $codeResponse = \json_decode($responseJson, true);
            if(is_array($codeResponse) && array_key_exists('status', $codeResponse) && $codeResponse['status'] == 'ok') {
                $driverHelperFineJsCode = $codeResponse['response'];
                update_option('driverHelperFineJsCode', $codeResponse['response']);
            }
        }
    }

    $header = $content = '';
    $tabActionSett = '';
    $tabActionStat = '';
    $tabActionPlugin = '';

    switch ($_GET[ 'tab' ]) {
        case 'stat':
            $tabActionStat = 'nav-tab-active';
            $header = '<h1>Штрафы ГИБДД. Статистика</h1>';
            $content = dh_fine_getStatisticContent();
            break;
        case 'pluginlist':
            $tabActionPlugin = 'nav-tab-active';
            $header = '<h1>Штрафы ГИБДД. Наши другие плагины</h1>';
            $content = dh_fine_getPluginsContent();
            break;
        case 'settings':
        default:
            $tabActionSett = 'nav-tab-active';
            $header = '<h1>Штрафы ГИБДД. Настройки</h1>';
            $content = dh_fine_getSettingsContent();

            if($driverHelperFineApiId) {
                $content .= dh_fine_getGenerateShortcodeContent();
            }
            break;
    }

    $contentMenu = <<<EOF
<style>
#nav h3 {
	border-bottom: 1px solid #ccc;
	padding-bottom: 0;
	height: 1.5em;
}
table.wpsc-settings-table {
	clear: both;
}
</style>

<div id="nav">
<h3 class="themes-php">
<a class="nav-tab {$tabActionSett}" href="?page=dh_fine_fine_settings&tab=settings">Настройки</a>
<a class="nav-tab {$tabActionStat}" href="?page=dh_fine_fine_settings&tab=stat">Статистика</a>
<a class="nav-tab {$tabActionPlugin}" href="?page=dh_fine_fine_settings&tab=pluginlist">Наши другие плагины</a>
</h3>
</div>
EOF;

    echo  $header  . $contentMenu . $content;
}

function dh_fine_getSettingsContent() {
    global $driverHelperFineApiId;
$content = <<<EOF
<form method="post" action="">
<input type="hidden" name="action" value="setting">

</table>

<h2 class="title">Регистрация пользователя</h2>
<p>Перейдите по ссылке <a href="https://www.driver-helper.ru/shtrafy-gibdd/widget" target="_blank">https://www.driver-helper.ru/shtrafy-gibdd/widget</a> зарегистрируйтесь и получите Код API для виджета Wordpress</p>

<table class="form-table">
<tbody>
</tr>
<tr>
<th scope="row"><label for="mailserver_login">Код API для виджета</label></th>
<td><input name="api_code" type="text" id="api_code" value="{$driverHelperFineApiId}" class="regular-text ltr"></td>
</tr>
<tr>
<th scope="row"><label for="mailserver_login">&nbsp;</label></th>
<td><input type="submit" name="submit" id="submit" class="button button-primary" value="Сохранить"></td>
</tr>
</tbody></table>
</form>
EOF;

return $content;
}

function dh_fine_getStatisticContent()
{
    global $driverHelperFineApiId, $driverHelperFineJsCode;
    $driverHelperFineApiId = get_option('driverHelperFineApiId');
    $driverHelperFineJsCode = get_option('driverHelperFineJsCode');

    if(!empty($driverHelperFineApiId)) {
        $content = '<iframe width="100%" height="600px" src="https://www.driver-helper.ru/shtrafy-gibdd/widget/statiframe/' . $driverHelperFineApiId . '"></iframe>';
    } else {
        $content = '<p style="color:red">Для посмотра статистики нужно задать в настройках <b>Код API для виджета</b></p><p><a href="?page=dh_fine_fine_settings&tab=settings">перейти в настройки</a></p>';
    }

    return $content;
}

function dh_fine_getPluginsContent()
{

    $content = '<iframe width="100%" height="600px" src="https://www.driver-helper.ru/shtrafy-gibdd/widget/otherwidgetiframe"></iframe>';
    return $content;
}

function dh_fine_getGenerateShortcodeContent() {
$content = <<<EOF
<form method="post" action="">
<input type="hidden" name="action" value="setting">

</table>
<script>
function dh_fine_setShortcode() {
    var shortCode = document.getElementById( 'shortCode' );
    var width = document.getElementById( 'width' ).value;
    var height = document.getElementById( 'height' ).value;

    shortCode.innerHTML = '[finegibdd width="' + width + '" height="' + height + '"]';
    return false;
}
</script>
<h2 class="title">Шорткод для добавления на страницу</h2>

<table class="form-table">
<tbody>
</tr>
<tr>
<th scope="row"><label for="mailserver_login">Ширина</label></th>
<td><input name="api_code" type="text" id="width" value="100%" class="regular-text ltr"></td>
</tr>
<tr>
<th scope="row"><label for="mailserver_login">Высота</label></th>
<td><input name="api_code" type="text" id="height" value="500px" class="regular-text ltr"></td>
</tr>
<tr>
<th scope="row"><label for="mailserver_login">&nbsp;</label></th>
<td><input type="button" name="submit" id="submit" class="button button-primary" value="Создать шорткод" onclick="return dh_fine_setShortcode()"></td>
</tr>
</tbody></table>
</form>


<table class="form-table">
<tr>
<td>
<b>Shortcode</b>
<textarea name="shortCode" id="shortCode" class="large-text code" rows="3" style="width: 100%">[finegibdd width="100%" height="500px"]</textarea>
<span>Скопируйте этот код и вставьте в то место, где вам нужно вывести виджет</span>
</td>
</tr>
<tr>

</table>
EOF;

return $content;
}


function dh_fine_plugin_notice( $plugin ) {
    $driverHelperFineApiId = get_option('driverHelperFineApiId');

    if( $plugin == 'driver-helper-fine-gibdd/driver-helper-fine-gibdd.php' && empty($driverHelperFineApiId) )
        echo '<td colspan="5" class="plugin-update"><b>Поиск и оплата штрафов ГИБДД</b>. Плагин должен быть настроен. Чтобы включить его и настроить, перейдите в <a href="'.admin_url( 'admin.php?page=dh_fine_fine_settings' ).'">настройки</a></td>';
}
add_action( 'after_plugin_row', 'dh_fine_plugin_notice' );
