<?php

namespace App\Policies;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Определяет, может ли пользователь просматривать модель
     */
    public function view(User $user, Project $project): bool
    {
        // ДОБАВЛЕНО: Логирование для отладки
        Log::debug('ProjectPolicy::view вызван', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'project_id' => $project->id,
            'project_partner_id' => $project->partner_id,
            'is_admin' => $user->isAdmin()
        ]);
        
        // Администраторы могут видеть все проекты
        if ($user->isAdmin()) {
            return true;
        }
        
        // Партнеры могут видеть только свои проекты
        return $project->partner_id === $user->id;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Администраторы, партнеры и клиенты могут просматривать проекты
        return $user->isAdmin() || $user->isPartner() || $user->isClient();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isPartner() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->partner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->partner_id === $user->id;
    }
}
