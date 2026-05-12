<?php

function clean($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/** Email Validate  */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**  Replace Template Variables */
function replaceTemplateVariables($body, $contact = [])
{
    $variables = [
        '{{name}}'    => $contact['name'] ?? '',
        '{{company}}' => $contact['company'] ?? '',
        '{{email}}'   => $contact['email'] ?? '',
    ];

    return str_replace(
        array_keys($variables),
        array_values($variables),
        $body
    );
}

/** Redirect Helper */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/** Set Flash Message */
function setFlashMessage($type, $message)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/** Display Flash Message */
function displayFlashMessage()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['flash'])) {

        $flash = $_SESSION['flash'];

        echo '
        <div class="alert alert-' . $flash['type'] . '">
            ' . $flash['message'] . '
        </div>';

        unset($_SESSION['flash']);
    }
}

/** Generate Random String */
function randomString($length = 10)
{
    return substr(str_shuffle(
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ), 0, $length);
}

/** Format Date */
function formatDate($date)
{
    return date('d M Y h:i A', strtotime($date));
}