<?php

namespace App\DTO;

class UserInsertOrUpdateRequest
{
    public ?string $id = null;
    public string $nip;
    public string $name;
    public string $username;
    public string $email;
    public string $unit;
}
