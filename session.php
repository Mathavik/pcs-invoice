<?php
function ensureSessionStarted()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
