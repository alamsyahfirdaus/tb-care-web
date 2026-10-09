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

                if (empty($villageId)) {
                    return false;
                }

                $rwStr = !is_null($rw) ? (string)$rw : '';
                $rwVariants = array_unique([$rwStr, ltrim($rwStr, '0'), sprintf('%02d', (int)$rwStr), sprintf('%03d', (int)$rwStr)]);

                $query = $officer->kaderAreas()->where('village_id', $villageId);
                if (!empty($rwStr)) {
                    $query->where(function ($q) use ($rwVariants) {
                        $q->whereNull('rw')->orWhereIn('rw', $rwVariants);
                    });
                }

                if (!is_null($rt) && $rt !== '') {
                    $rtStr = (string)$rt;
                    $rtVariants = array_unique([$rtStr, ltrim($rtStr, '0'), sprintf('%02d', (int)$rtStr), sprintf('%03d', (int)$rtStr)]);
                    $query->where(function ($q) use ($rtVariants) {
                        $q->whereNull('rt')->orWhereIn('rt', $rtVariants);
                    });
                }

                return $query->exists();
            }

            // Dinkes Kab/Kota (2): Puskesmas must belong to officer's district
            if ($officer->officer_type_id == 2) {
                if ($puskesmasId) {
                    $pusk = \App\Models\Puskesmas::with('subdistrict')->find($puskesmasId);
                    if (!$pusk || optional($pusk->subdistrict)->district_id != $officer->district_id) {
                        return false;
                    }
                }
                return true;
            }

            // Dinkes Provinsi (1): Puskesmas must belong to officer's province
            if ($officer->officer_type_id == 1) {
                $dist = \App\Models\District::find($officer->district_id);
                $provId = $dist ? $dist->province_id : null;
                if ($puskesmasId && $provId) {
                    $pusk = \App\Models\Puskesmas::with('subdistrict.district')->find($puskesmasId);
                    if (!$pusk || optional(optional($pusk->subdistrict)->district)->province_id != $provId) {
                        return false;
                    }
                }
                return true;
            }

            return true;
        }

        return false;
    }
}
