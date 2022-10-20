<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе установки.
 * Необязательно использовать веб-интерфейс, можно скопировать файл в "wp-config.php"
 * и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки базы данных
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Параметры базы данных: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'epiz_32805130_123' );

/** Имя пользователя базы данных */
define( 'DB_USER', 'epiz_32805130' );

/** Пароль к базе данных */
define( 'DB_PASSWORD', 'lOC78nAiD7' );

/** Имя сервера базы данных */
define( 'DB_HOST', 'sql113.epizy.com' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу. Можно сгенерировать их с помощью
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}.
 *
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными.
 * Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'nR6#7oju8GQF7^AL$a!%ql: `KBxyc?Fe ##Vgm |LVjl&U)Xi4UEJ}&e@Qz?3s;' );
define( 'SECURE_AUTH_KEY',  '<80mPGZ%]e-<Y[boeS=B/5/E3s}C:]-z-3jNwDh77Cgilt_9:ndo@zuJ[6^o5d6C' );
define( 'LOGGED_IN_KEY',    '52ltafkgY*J-xv4eX&`{*8!hRNuW]xL+Ozk#4V<c*H!RYySem~a>OX~IQHa;g^e@' );
define( 'NONCE_KEY',        '>c+GpUiOh9*Yc,]0y3eSW^Nv [gwOoJk57o0YaRM}0i+$q~9t-VGu&bhb=OZ|]vb' );
define( 'AUTH_SALT',        'pnwAd#VU<G`U~pQ+u-Xrog:<26XHNw!%x5~{syc?gjN&)8S;Y0b k%>|ZvCYAn7p' );
define( 'SECURE_AUTH_SALT', '/s ;{t0i&G~JuBI4<0D=pn}{a@OaO6zKe&t$6j:vdMZe#:+cp;%O;KM/DZ:8)nKV' );
define( 'LOGGED_IN_SALT',   'fb!c)^hH%IPeM|xAZ5k<)%+wA=GR9jUa*Aatym;3t }9 E.|=QhZtVey{Sb3*Yz-' );
define( 'NONCE_SALT',       'i<#]Ov^rgrTLJB|bT58n|NG.hRXX?xzy!`e@(=hx<R-gg.2bMn@#?erYLyVn /JT' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Произвольные значения добавляйте между этой строкой и надписью "дальше не редактируем". */



/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';
