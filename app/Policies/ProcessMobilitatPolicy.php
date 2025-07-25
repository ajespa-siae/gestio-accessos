<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProcessMobilitat;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessMobilitatPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // RRHH pot veure tots els processos
        if ($user->hasRole('rrhh') || $user->hasRole('admin')) {
            return $user->getAllPermissions()->contains('name', 'view_any_process::mobilitat');
        }
        
        // Gestors només poden veure processos dels seus departaments
        if ($user->hasRole('gestor')) {
            return $user->getAllPermissions()->contains('name', 'view_any_process::mobilitat::gestor');
        }
        
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProcessMobilitat $processMobilitat): bool
    {
        // RRHH pot veure qualsevol procés
        if ($user->hasRole('rrhh') || $user->hasRole('admin')) {
            return $user->getAllPermissions()->contains('name', 'view_process::mobilitat');
        }
        
        // Gestors només poden veure processos dels seus departaments
        if ($user->hasRole('gestor')) {
            $departaments = $user->departamentsGestionats->pluck('id');
            $potVeure = $departaments->contains($processMobilitat->departament_actual_id) ||
                       $departaments->contains($processMobilitat->departament_nou_id);
                       
            return $potVeure && $user->getAllPermissions()->contains('name', 'view_process::mobilitat::gestor');
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Només RRHH pot crear processos de mobilitat
        return ($user->hasRole('rrhh') || $user->hasRole('admin')) && 
               $user->getAllPermissions()->contains('name', 'create_process::mobilitat');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProcessMobilitat $processMobilitat): bool
    {
        // RRHH pot editar qualsevol procés
        if ($user->hasRole('rrhh') || $user->hasRole('admin')) {
            return $user->getAllPermissions()->contains('name', 'update_process::mobilitat');
        }
        
        // Gestors no poden editar, només revisar
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProcessMobilitat $processMobilitat): bool
    {
        // Només admins poden eliminar processos de mobilitat
        return $user->hasRole('admin') && $user->getAllPermissions()->contains('name', 'delete_process::mobilitat');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Només admins poden eliminar processos de mobilitat
        return $user->hasRole('admin') && $user->getAllPermissions()->contains('name', 'delete_any_process::mobilitat');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ProcessMobilitat $processMobilitat): bool
    {
        // Només admins poden eliminar permanentment
        return $user->hasRole('admin') && $user->getAllPermissions()->contains('name', 'force_delete_process::mobilitat');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Només admins poden eliminar permanentment
        return $user->hasRole('admin') && $user->getAllPermissions()->contains('name', 'force_delete_any_process::mobilitat');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ProcessMobilitat $processMobilitat): bool
    {
        return $user->can('restore_process::mobilitat::gestor');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_process::mobilitat::gestor');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ProcessMobilitat $processMobilitat): bool
    {
        return $user->can('replicate_process::mobilitat::gestor');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_process::mobilitat::gestor');
    }
}
