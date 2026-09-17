<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any patients.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return in_array($user->user_type_id, [1, 2, 3]);
    }

    /**
     * Determine whether the user can view the specific patient.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Patient  $patient
     * @return bool
     */
    public function view(User $user, Patient $patient)
    {
        return $patient->isAccessibleBy($user);
    }

    /**
     * Determine whether the user can create patients.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Admin (1) and Petugas (3) can create patients
        return in_array($user->user_type_id, [1, 3]);
    }

    /**
     * Determine whether the user can update the specific patient.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Patient  $patient
     * @return bool
     */
    public function update(User $user, Patient $patient)
    {
        return $patient->isAccessibleBy($user);
    }

    /**
     * Determine whether the user can delete the specific patient.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Patient  $patient
     * @return bool
     */
    public function delete(User $user, Patient $patient)
    {
        return $patient->isAccessibleBy($user);
    }

    /**
     * Validate whether the user has authority to assign or update a patient
     * to the specified territory (village, rw, rt, puskesmas).
     *
     * @param  \App\Models\User  $user
     * @param  int|string  $villageId
     * @param  string  $rw
     * @param  string|null  $rt
     * @param  int|null  $puskesmasId
     * @return bool
     */
    public function canAssignTerritory(User $user, $villageId, $rw, $rt = null, $puskesmasId = null): bool
    {
        // 1. Administrator can assign any territory
        if ($user->user_type_id == 1) {
            return true;
        }

        // 2. Petugas (officer)
        if ($user->user_type_id == 3) {
            $officer = $user->officer;
            if (!$officer) {
                return false;
            }

            // PJTB Puskesmas (3): Puskesmas must match
            if ($officer->officer_type_id == 3) {
                return is_null($puskesmasId) || $puskesmasId == $officer->puskesmas_id;
            }

            // Kader Puskesmas (4): Puskesmas must match AND territory must match an assigned area
            if ($officer->officer_type_id == 4) {
                if (!is_null($puskesmasId) && $puskesmasId != $officer->puskesmas_id) {
                    return false;
                }

                if (empty($villageId) || empty($rw)) {
                    return false;
                }

                // Check if the combination exists in kader_areas
                return $officer->kaderAreas()
                    ->where('village_id', $villageId)
                    ->where('rw', $rw)
                    ->where(function ($q) use ($rt) {
                        $q->whereNull('rt');
                        if (!is_null($rt) && $rt !== '') {
                            $q->orWhere('rt', $rt);
                        }
                    })
                    ->exists();
            }

            // Dinkes
            return true;
        }

        return false;
    }
}
