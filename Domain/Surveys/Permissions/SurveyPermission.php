<?php

namespace Domain\Surveys\Permissions;

use Althinect\EnumPermission\Concerns\HasPermissionGroup;

enum SurveyPermission: string
{
    use HasPermissionGroup;

    case VIEW_ANY = 'Survey.view-any';
    case VIEW = 'Survey.view';
    case CREATE = 'Survey.create';
    case UPDATE = 'Survey.update';
    case DELETE = 'Survey.delete';
    case RESTORE = 'Survey.restore';
    case FORCE_DELETE = 'Survey.force-delete';
}
