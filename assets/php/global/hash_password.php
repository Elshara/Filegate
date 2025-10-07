<?php

function fg_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

