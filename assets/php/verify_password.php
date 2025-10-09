<?php

function fg_verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

