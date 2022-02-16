<?php

namespace App\DTO;

class UserUpdateRequest
{
    public string|int|null $id = null;
    public string $nip;
    public string $name;
    public string $username;
    public string $email;
    public string $unit;
}
