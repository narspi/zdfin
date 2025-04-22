<?php
show_admin_bar(false);

add_theme_support('post-thumbnails');
add_theme_support('title-tag');
add_theme_support('menus');

register_nav_menus(
    array(
        'footer_course' => 'Системные курсы',
        'footer_docs' => 'Документы сайта'
    )
);

function zdfinance_assets()
{
    wp_enqueue_style('global-page-styles', get_template_directory_uri() . '/assets/css/style.css', array(), '1.0');
    wp_enqueue_script('global-page-scripts', get_template_directory_uri() . '/assets/js/main.js', array(), '1.0', true);
}
add_action('wp_enqueue_scripts', 'zdfinance_assets');

add_action('wp_ajax_send_form', 'send_data_to_bitrix');
add_action('wp_ajax_nopriv_send_form', 'send_data_to_bitrix');

function send_data_to_bitrix()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    {
        wp_die('Invalid request');
    }

    $form_name = sanitize_text_field($_POST['title']);
    $name = sanitize_text_field($_POST['name'] ?? '');
    $surname = sanitize_text_field($_POST['surname'] ?? '');
    $tel = preg_replace('/\D+/', '', $_POST['tel'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($form_name))
    {
        wp_send_json_error(['message' => 'Недопустимый запрос']);
        wp_die();
    }

    // if ($form_name !== 'Заказать обратный звонок' || $form_name !== 'Консультация с финансовым директором')
    // {
    //     wp_send_json_error(['message' => 'Пошёл нахер бот']);
    //     wp_die();
    // }

    if (empty($name))
    {
        wp_send_json_error(['message' => 'Имя обязательно']);
        wp_die();
    }

    if (empty($tel) || strlen($tel) < 10)
    {
        wp_send_json_error(['message' => 'Некорректный номер телефона']);
        wp_die();
    }

    if (!empty($email) and !is_email($email))
    {
        wp_send_json_error(['message' => 'Некорректный email']);
        wp_die();
    }


    // Вебхук Bitrix24
    $webhook_base = 'Входящий хук из Битрикс 24';

    $contact_fields = [
        'NAME' => $name,
        'PHONE' => [['VALUE' => $tel, 'VALUE_TYPE' => 'WORK']],
        'SOURCE_ID' => 'UC_NK3IQH'
    ];

    if (!empty($surname))
    {
        $contact_fields['LAST_NAME'] = $surname;
    }

    if (!empty($email))
    {
        $contact_fields['EMAIL'] = [['VALUE' => $email, 'VALUE_TYPE' => 'WORK']];
    }

    $contact_data = ['fields' => $contact_fields];

    $contact_response = wp_remote_post($webhook_base . 'crm.contact.add.json', [
        'body' => json_encode($contact_data),
        'headers' => ['Content-Type' => 'application/json'],
        'method' => 'POST',
    ]);

    $contact_body = json_decode(wp_remote_retrieve_body($contact_response), true);
    $contact_id = $contact_body['result'] ?? null;

    if (!$contact_id)
    {
        wp_send_json_error(['message' => 'Ошибка при создании контакта']);
        wp_die();
    }

    $deal_fields = [
        'TITLE' => $form_name,
        'CONTACT_ID' => $contact_id,
        'SOURCE_ID' => 'UC_NK3IQH',
        'CATEGORY_ID' => 42
    ];

    if (!empty($message))
    {
        $deal_fields['COMMENTS'] = $message;
    }

    if ($form_name === 'Заказать обратный звонок')
    {
        $deal_fields['STAGE_ID'] = 'C42:UC_1LQT3P';
    }

    if ($form_name === 'Консультация с финансовым директором')
    {
        $deal_fields['STAGE_ID'] = 'C42:UC_FMD2I4';
    }

    $deal_data = ['fields' => $deal_fields];

    $deal_response = wp_remote_post($webhook_base . 'crm.deal.add.json', [
        'body' => json_encode($deal_data),
        'headers' => ['Content-Type' => 'application/json'],
        'method' => 'POST',
    ]);

    $deal_body = json_decode(wp_remote_retrieve_body($deal_response), true);
    $deal_id = $deal_body['result'] ?? null;

    if (!$deal_id)
    {
        wp_send_json_error(['message' => 'Ошибка при создании сделки']);
    }

    wp_send_json_success(['message' => 'Заявка успешно отправлена']);
    wp_die();
}