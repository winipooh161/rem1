<?php

namespace App\Policies;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EstimatePolicy
{
    use HandlesAuthorization;

    /**
     * Определяет, может ли пользователь просматривать смету.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Estimate  $estimate
     * @return bool
     */
    public function view(User $user, Estimate $estimate)
    {
        // Администратор может просматривать любую смету
        if ($user->isAdmin()) {
            return true;
        }
        
        // Сметчик может просматривать только свои сметы
        if ($user->isEstimator()) {
            return $estimate->user_id === $user->id;
        }
        
        // Партнер может просматривать:
        // 1. Сметы своих проектов
        // 2. Сметы, созданные его сметчиками
        // 3. Свои собственные сметы
        if ($user->isPartner()) {
            // Проверяем, принадлежит ли проект партнеру
            $project = $estimate->project;
            if ($project && $project->partner_id === $user->id) {
                return true;
            }
            
            // Проверяем, создана ли смета сметчиком партнера
            $estimateCreator = $estimate->user;
            if ($estimateCreator && $estimateCreator->partner_id === $user->id && $estimateCreator->isEstimator()) {
                return true;
            }
            
            // Проверяем, создана ли смета самим партнером
            if ($estimate->user_id === $user->id) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Определяет, может ли пользователь редактировать смету.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Estimate  $estimate
     * @return bool
     */
    public function update(User $user, Estimate $estimate)
    {
        // Администратор может редактировать любую смету
        if ($user->isAdmin()) {
            return true;
        }
        
        // Сметчик может редактировать только свои сметы
        if ($user->isEstimator()) {
            return $estimate->user_id === $user->id;
        }
        
        // Партнер может редактировать:
        // 1. Сметы своих проектов
        // 2. Сметы, созданные его сметчиками
        // 3. Свои собственные сметы
        if ($user->isPartner()) {
            // Проверяем, принадлежит ли проект партнеру
            $project = $estimate->project;
            if ($project && $project->partner_id === $user->id) {
                return true;
            }
            
            // Проверяем, создана ли смета сметчиком партнера
            $estimateCreator = $estimate->user;
            if ($estimateCreator && $estimateCreator->partner_id === $user->id && $estimateCreator->isEstimator()) {
                return true;
            }
            
            // Проверяем, создана ли смета самим партнером
            if ($estimate->user_id === $user->id) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Определяет, может ли пользователь удалять смету.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Estimate  $estimate
     * @return bool
     */
    public function delete(User $user, Estimate $estimate)
    {
        // Администратор может удалять любую смету
        if ($user->isAdmin()) {
            return true;
        }
        
        // Сметчик может удалять только свои сметы
        if ($user->isEstimator()) {
            return $estimate->user_id === $user->id;
        }
        
        // Партнер может удалять:
        // 1. Сметы своих проектов
        // 2. Сметы, созданные его сметчиками
        // 3. Свои собственные сметы
        if ($user->isPartner()) {
            // Проверяем, принадлежит ли проект партнеру
            $project = $estimate->project;
            if ($project && $project->partner_id === $user->id) {
                return true;
            }
            
            // Проверяем, создана ли смета сметчиком партнера
            $estimateCreator = $estimate->user;
            if ($estimateCreator && $estimateCreator->partner_id === $user->id && $estimateCreator->isEstimator()) {
                return true;
            }
            
            // Проверяем, создана ли смета самим партнером
            if ($estimate->user_id === $user->id) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Определяет, может ли пользователь создавать сметы.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Администраторы, партнеры и сметчики могут создавать сметы
        return $user->isAdmin() || $user->isPartner() || $user->isEstimator();
    }

    /**
     * Определяет, может ли пользователь просматривать любые сметы.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        // Администраторы, партнеры и сметчики могут просматривать сметы
        return $user->isAdmin() || $user->isPartner() || $user->isEstimator();
    }
}
