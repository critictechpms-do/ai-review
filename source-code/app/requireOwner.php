<?php

function isOwner() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        return false;
    }
    
    $ownerEmail = env('OWNER_EMAIL');
    return $_SESSION['user_email'] === $ownerEmail;
}

