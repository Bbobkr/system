<?php
declare(strict_types=1);

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_success(string $message): void { flash_set('success', $message); }
function flash_error(string $message): void { flash_set('error', $message); }

/** يسحب كل الرسائل ويفرغها (تُعرض مرة واحدة فقط) */
function flash_pull(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}
