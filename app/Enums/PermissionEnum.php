<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case VIEW_ARTICLE = 'View Articles';
    case CREATE_ARTICLES = 'Create Articles';
    case DELETE_ARTICLES = 'Delete Articles';
    case UPDATES_ARTICLES = 'Update Articles';
    case PUBLISH_ARTICLES = 'Publish Articles';
    case RESTORE_ARTICLES = 'Restore Articles';
    case ACCESS_PANEL = 'Access Panel';
}
