<?php

namespace Domain\Surveys\Policies;

use App\Models\User;
use Domain\Surveys\Models\Survey;
use Domain\Surveys\Permissions\SurveyPermission;

class SurveyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::VIEW_ANY, guardName: 'api');
    }

    public function view(User $user, Survey $model): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::VIEW, guardName: 'api');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::CREATE, guardName: 'api');
    }

    public function update(User $user, Survey $model): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::UPDATE, guardName: 'api');
    }

    public function delete(User $user, Survey $model): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::DELETE, guardName: 'api');
    }

    public function restore(User $user, Survey $model): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::RESTORE, guardName: 'api');
    }

    public function forceDelete(User $user, Survey $model): bool
    {
        return $user->hasPermissionTo(permission: SurveyPermission::FORCE_DELETE, guardName: 'api');
    }
}
