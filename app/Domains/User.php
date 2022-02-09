<?php

namespace App\Domains;

class User {
    public ?string $id;
    public ?string $nip;
    public string $name;
    public string $username;
    public string $email;
    public bool $actived;
    public string $password;
    public ?string $unit_id;
    public int $role_id;
}
