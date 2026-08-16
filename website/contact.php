<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html', true, 303);
    exit;
}

if (! empty($_POST['website'])) {
    header('Location: contact.html?sent=1', true, 303);
    exit;
}

function field(string $key, int $max): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    $value = preg_replace('/[\r\n]+/', ' ', $value) ?? '';

    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

$name = field('name', 120);
$organization = field('organization', 160);
$role = field('role', 120);
$email = field('email', 160);
$phone = field('phone', 40);
$message = trim((string) ($_POST['message'] ?? ''));
$message = function_exists('mb_substr') ? mb_substr($message, 0, 4000) : substr($message, 0, 4000);
$modules = $_POST['modules'] ?? [];
$modules = is_array($modules) ? array_map('strval', $modules) : [];
$modules = array_values(array_intersect($modules, ['DMS', 'QMS', 'AI']));

if ($name === '' || $organization === '' || $email === '' || $message === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: contact.html', true, 303);
    exit;
}

$to = 'contact@qualigxp.com';
$subject = 'QualiGxP website enquiry from '.$organization;
$body = implode("\n", [
    'Name: '.$name,
    'Organization: '.$organization,
    'Role: '.$role,
    'Email: '.$email,
    'Phone: '.$phone,
    'Modules: '.(implode(', ', $modules) !== '' ? implode(', ', $modules) : 'Not specified'),
    '',
    $message,
]);

$encodedName = function_exists('mb_encode_mimeheader')
    ? mb_encode_mimeheader($name, 'UTF-8', 'Q')
    : $name;

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: QualiGxP Website <noreply@qualigxp.com>',
    'Reply-To: '.$encodedName.' <'.$email.'>',
    'X-Mailer: QualiGxP-Website',
];

@mail($to, $subject, $body, implode("\r\n", $headers));

header('Location: contact.html?sent=1', true, 303);
exit;
