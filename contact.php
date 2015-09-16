<?php
global $formular_names; // use in ajax
$formular_names = array();

Aspect\Type::set('contact form')
    ->setArgument('show_ui', true)
    ->attach(
        Aspect\Box::set('email')
            ->setArgument('description', email_box_description())
            ->attach(
                Aspect\Input::set('to'),
                Aspect\Input::set('from'),
                Aspect\Input::set('subject'),
                Aspect\Input::set('message')
                    ->setType('textarea')
            ),
        Aspect\Box::set('messages')
            ->attach(
                Aspect\Input::set('ok send')
                    ->setArgument('sanitizeTextField', false),
                Aspect\Input::set('email send error')
                    ->setArgument('sanitizeTextField', false),
                Aspect\Input::set('empty')
                    ->setArgument('sanitizeTextField', false)
                    ->setArgument('description', 'You can use [_field] to describe field name in error message'),
                Aspect\Input::set('not valid email')
                    ->setArgument('sanitizeTextField', false)
                    ->setArgument('description', 'You can use [_field] to describe field name in error message'),
                Aspect\Input::set('not valid phone')
                    ->setArgument('sanitizeTextField', false)
                    ->setArgument('description', 'You can use [_field] to describe field name in error message')
            )
    );
function get_attributes($atts, $short, $id)
{
    global $formular_names;
    $placeholder = null;
    $value = null;
    $name = null;
    $required = null;
    $class = null;
    $size = 40;
    $default_attr = compact('placeholder', 'value', 'required', 'name', 'class', 'size');
    $atts = shortcode_atts($default_attr, $atts, $short);
    extract($atts);
    switch ($required) {
        case null: {
            $required = null;
            break;
        }
        case 'off': {
            $required = null;
            break;
        }
        case 'no': {
            $required = null;
            break;
        }
        case 'false': {
            $required = null;
            break;
        }
        default:
            $required = 'required';
    }
    $attributes = compact('placeholder', 'value', 'required', 'name', 'class', 'size');
    if ($name != null)
        $formular_names[$id][$name] = array_merge($attributes, array('type' => $short));
    return $attributes;
}

function attributes($atts, $short, $id)
{
    $attributes = get_attributes($atts, $short, $id);
    $classes = explode(' ', $attributes['class']);
    $classes[] = 'wpcf7-form-control';
    switch ($short) {
        case 'formular-text': {
            $classes[] = 'wpcf7-text';
            break;
        }
        case 'formular-email': {
            $classes[] = 'wpcf7-text';
            $classes[] = 'wpcf7-email';
            break;
        }
        case 'formular-textarea': {
            $classes[] = 'wpcf7-textarea';
            break;
        }
        case 'formular-submit': {
            $classes[] = 'wpcf7-submit';
            break;
        }
    }
    $classes = array_unique($classes);
    $attributes['class'] = implode(' ', $classes);
    $attrs = array();
    foreach ($attributes as $name => $value) {
        if (!$value) continue;
        $attrs[] = $name . '="' . esc_attr($value) . '"';
    }
    return implode(' ', $attrs);
}

add_shortcode('formular', function ($atts) {
    ob_start();
    global $form;
    $atts = shortcode_atts(array(
        'id' => null
    ), $atts, 'formular');
    $id = null;
    extract($atts);
    $form = $id;
    $post = get_post($id);
    setup_postdata($post); ?>
    <div class="wpcf7" id="formular-<?= $id ?>">
        <form class="wpcf7-form formular" novalidate="novalidate" action="<?= admin_url('admin-ajax.php') ?>"
              method="post">
            <?= do_shortcode(get_the_content()); ?>
            <input type="hidden" name="action" value="formular">
            <input type="hidden" name="_url"
                   value="<?= 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ?>">
            <input type="hidden" name="formular_id" value="<?= $id; ?>">
            <?php wp_nonce_field('formular' . $id, 'formular_nonce') ?>
            <div class="wpcf7-response-output wpcf7-display-none"></div>
        </form>
    </div>
    <?php wp_reset_postdata();
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
});

add_shortcode('formular-text', function ($atts) {
    global $form;
    ob_start(); ?>
    <span class="wpcf7-form-control-wrap">
        <input type="text" <?= attributes($atts, 'formular-text', $form) ?>></span>
    <?php $content = ob_get_contents();
    ob_end_clean();
    return $content;
});
add_shortcode('formular-phone', function ($atts) {
    global $form;
    ob_start(); ?>
    <span class="wpcf7-form-control-wrap">
        <input type="tel" <?= attributes($atts, 'formular-phone', $form) ?>></span>
    <?php $content = ob_get_contents();
    ob_end_clean();
    return $content;
});

add_shortcode('formular-email', function ($atts) {
    global $form;
    ob_start(); ?>
    <span class="wpcf7-form-control-wrap">
        <input type="email" <?= attributes($atts, 'formular-email', $form) ?>></span>
    <?php $content = ob_get_contents();
    ob_end_clean();
    return $content;
});
add_shortcode('formular-textarea', function ($atts) {
    global $form;
    if (isset($atts['value'])) {
        $value = $atts['value'];
        unset($atts['value']);
    } else {
        $value = null;
    }
    ob_start(); ?>
    <span class="wpcf7-form-control-wrap">
        <textarea type="email" <?= attributes($atts, 'formular-textarea', $form) ?>><?= $value ?></textarea></span>
    <?php $content = ob_get_contents();
    ob_end_clean();
    return $content;
});
add_shortcode('formular-submit', function ($atts) {
    global $form;
    ob_start(); ?>
    <img class="ajax-loader" src="<?= get_template_directory_uri() ?>/img/ajax-loader.gif" alt="Senden ..."
         style="opacity: 0;">
    <input type="submit" <?= attributes($atts, 'formular-submit', $form) ?>>
    <?php $content = ob_get_contents();
    ob_end_clean();
    return $content;
});
add_action('wp_ajax_formular', 'formulare_ajax');
add_action('wp_ajax_nopriv_formular', 'formulare_ajax');
function formulare_ajax()
{
    global $formular_names;
    $id = intval($_POST['formular_id']);
    if (!wp_verify_nonce($_POST['formular_nonce'], 'formular' . $id)) {
        wp_send_json_error();
        wp_die();
    }
    $to = Aspect\Input::get('to')->getValue(Aspect\Box::get('email'), null, $id);
    $subject = Aspect\Input::get('subject')->getValue(Aspect\Box::get('email'), null, $id);
    $message = Aspect\Input::get('message')->getValue(Aspect\Box::get('email'), null, $id);
    $from = Aspect\Input::get('from')->getValue(Aspect\Box::get('email'), null, $id);
    do_shortcode('[formular id="' . $id . '"]');
    $data_names = $formular_names[$id];
    $data_names['_url'] = array(
        'type' => 'formular-url'
    );
    $informs = array(&$to, &$subject, &$message, &$from);
    foreach ($data_names as $name => $field) {
        $value = $_POST[$name];
        $type = substr($field['type'], 9);
        $field_name = $field['placeholder'];
        if ($field['required'] !== null && empty($value))
            $error = get_message_formulare('empty', $id, $field_name);
        switch ($type) {
            case 'email': {
                if (!is_email($value) && !empty($value))
                    $error = get_message_formulare('not valid email', $id, $field_name);
                if ($field['required'] !== null && empty($value))
                    $error = get_message_formulare('empty', $id, $field_name);
                break;
            }
            case 'phone': {
                if (!is_numeric($value) && !empty($value))
                    $error = get_message_formulare('not valid phone', $id, $field_name);
                if ($field['required'] !== null && empty($value))
                    $error = get_message_formulare('empty', $id, $field_name);
                break;
            }
        }
        foreach ($informs as &$info) {
            $info = str_replace('[' . $name . ']', $value, $info);
        }
    }
    if (isset($from))
        $headers = 'From: ' . $from;
    if (!isset($error)) {
        if (isset($headers)) {
            $send = wp_mail($to, $subject, $message, $headers);
        } else {
            $send = wp_mail($to, $subject, $message);
        }
        if ($send) {
            wp_send_json_success(get_message_formulare('ok send', $id));
        } else {
            wp_send_json_error(get_message_formulare('email send error', $id));
        }
    } else {
        wp_send_json_error($error);
    }
    wp_die();
}

function get_message_formulare($code, $id, $name = null)
{
    $code = Aspect\Input::get($code)->getValue(Aspect\Box::get('messages'), null, $id);
    if ($name !== null)
        $code = str_replace('[_field]', $name, $code);
    return $code;
}

function get_formulare_names($id)
{
    global $formular_names;
    if (!empty($formular_names[$id]))
        return array_keys($formular_names[$id]);
    $post = get_post($id);
    setup_postdata($post);
    preg_match_all('/[ *formular[^]]*name *= *["\']?([^"\']*)/i', get_the_content(), $matches);
    wp_reset_postdata();
    return $matches[1];
}

function email_box_description()
{
    $id = intval($_GET['post']);
    $message = 'In the following fields, you can use these mail-tags:';
    $keys = array();
    foreach (get_formulare_names($id) as $key) {
        $keys[] = '[' . $key . ']';
    }
    $keys[] = '[_url]';
    $message .= '<br><b>' . implode(', ', $keys) . '</b>';
    return $message;
}