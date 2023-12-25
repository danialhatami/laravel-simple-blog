<?php

use App\Enums\PermissionEnum as Permission;
use App\Enums\RoleEnum as Rule;

return [
    'permissions' => [
        Permission::CREATE_ARTICLES->value => [Rule::AUTHOR->value],
        Permission::DELETE_ARTICLES->value => [Rule::ADMIN->value],
        Permission::UPDATES_ARTICLES->value => [Rule::AUTHOR->value],
        Permission::PUBLISH_ARTICLES->value => [Rule::ADMIN->value],
        Permission::ACCESS_PANEL->value => [Rule::ADMIN->value, Rule::AUTHOR->value],
        Permission::RESTORE_ARTICLES->value => [Rule::ADMIN->value],
    ],
];
