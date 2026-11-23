<?php
declare(strict_types=1);

// A mobile payment session grants access to one order only, not a web login.
function mobile_payment_user_id(int $orderId): int
{
    if ((int) ($_SESSION['mobile_payment_order'] ?? 0) === $orderId
        && (int) ($_SESSION['mobile_payment_until'] ?? 0) > time()) {
        return (int) $_SESSION['mobile_payment_user'];
    }
    return require_login();
}

function mobile_payment_return(int $orderId): bool
{
    return (int) ($_SESSION['mobile_payment_order'] ?? 0) === $orderId
        && (int) ($_SESSION['mobile_payment_until'] ?? 0) > time();
}
