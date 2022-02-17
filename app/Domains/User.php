<?php

namespace App\Domains;

class User
{
    public string|int $id;
    public ?string $nip;
    public string $name;
    public string $username;
    public string $email;
    public ?bool $actived;
    public ?string $password;
    public string|int|null $unit_id;
    public string|int|null $role_id;
}
