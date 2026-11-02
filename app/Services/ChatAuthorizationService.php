<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\User;

class ChatAuthorizationService
{
    /**
     * Check if userA is authorized to initiate or continue communication with userB.
     */
    public static function canCommunicate(User $userA, User $userB): bool
    {
        if ($userA->id === $userB->id) {
            return false;
        }

        if (!$userA->is_active || !$userB->is_active) {
            return false;
        }

        // Admin has global communication rights
        if ($userA->user_type_id == 1 || $userB->user_type_id == 1) {
            return true;
        }

        // Case 1: userA is Pasien, userB is Petugas
        if ($userA->user_type_id == 2 && $userB->user_type_id == 3) {
            return self::patientCanContactOfficer($userA, $userB);
        }

        // Case 2: userA is Petugas, userB is Pasien
        if ($userA->user_type_id == 3 && $userB->user_type_id == 2) {
            return self::officerCanContactPatient($userA, $userB);
        }

        // Petugas to Petugas within same Puskesmas (if needed)
        if ($userA->user_type_id == 3 && $userB->user_type_id == 3) {
            $offA = $userA->officer;
            $offB = $userB->officer;
            if ($offA && $offB && $offA->puskesmas_id && $offA->puskesmas_id === $offB->puskesmas_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a patient can contact a specific officer.
     */
    public static function patientCanContactOfficer(User $patientUser, User $officerUser): bool
    {
        $patient = Patient::where('user_id', $patientUser->id)->first();
        $officer = Officer::where('user_id', $officerUser->id)->first();

        if (!$patient || !$officer) {
            return false;
        }

        // If officer is PJTB (officer_type_id = 3): Must belong to the same Puskesmas
        if ((int) $officer->officer_type_id === 3) {
            return $patient->puskesmas_id && (int) $patient->puskesmas_id === (int) $officer->puskesmas_id;
        }

        // If officer is Kader (officer_type_id = 4): Must be assigned to the patient's Desa/Kelurahan
        if ((int) $officer->officer_type_id === 4) {
            if (!$patient->village_id) {
                return false;
            }

            return $officer->kaderAreas()
                ->where('village_id', $patient->village_id)
                ->exists();
        }

        // Dinkes Kab/Kota (officer_type_id = 2) or Provinsi (officer_type_id = 1)
        if (in_array((int) $officer->officer_type_id, [1, 2])) {
            return $patient->isAccessibleBy($officerUser);
        }

        return false;
    }

    /**
     * Determine if an officer can contact a specific patient.
     */
    public static function officerCanContactPatient(User $officerUser, User $patientUser): bool
    {
        $patient = Patient::where('user_id', $patientUser->id)->first();
        if (!$patient) {
            return false;
        }

        return $patient->isAccessibleBy($officerUser);
    }

    /**
     * Verify that the user is authorized to participate in the conversation.
     */
    public static function authorizeConversationAccess(User $user, Conversation $conversation): bool
    {
        if ($user->user_type_id == 1) {
            return true;
        }

        return $conversation->isParticipant($user);
    }

    /**
     * Get valid chat recipients for the given user based on territory boundaries.
     */
    public static function getEligibleRecipients(User $user)
    {
        // 1. PASIEN: Kader in village + PJTB in Puskesmas
        if ($user->user_type_id == 2) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                return collect([]);
            }

            // Kaders in same village
            $kaderOfficers = Officer::with(['user', 'kaderAreas.village', 'puskesmas'])
                ->where('officer_type_id', 4)
                ->whereHas('user', fn($q) => $q->where('is_active', true))
                ->whereHas('kaderAreas', fn($q) => $q->where('village_id', $patient->village_id))
                ->get();

            // PJTB in same Puskesmas
            $pjtbOfficers = Officer::with(['user', 'puskesmas'])
                ->where('officer_type_id', 3)
                ->where('puskesmas_id', $patient->puskesmas_id)
                ->whereHas('user', fn($q) => $q->where('is_active', true))
                ->get();

            $results = collect();

            foreach ($kaderOfficers as $ko) {
                if (!$ko->user) continue;
                $results->push([
                    'id'          => $ko->user->id,
                    'name'        => $ko->user->name,
                    'email'       => $ko->user->email,
                    'phone'       => $ko->user->phone,
                    'photo'       => $ko->user->photo,
                    'role'        => 'Kader',
                    'role_detail' => 'Kader Desa ' . ($patient->village?->name ?? ''),
                    'sub_info'    => $ko->puskesmas ? 'Puskesmas ' . $ko->puskesmas->name : 'Kader Wilayah',
                ]);
            }

            foreach ($pjtbOfficers as $po) {
                if (!$po->user) continue;
                $results->push([
                    'id'          => $po->user->id,
                    'name'        => $po->user->name,
                    'email'       => $po->user->email,
                    'phone'       => $po->user->phone,
                    'photo'       => $po->user->photo,
                    'role'        => 'PJTB',
                    'role_detail' => 'PJTB Puskesmas',
                    'sub_info'    => $po->puskesmas ? 'Puskesmas ' . $po->puskesmas->name : 'Puskesmas',
                ]);
            }

            return $results->unique('id')->values();
        }

        // 2. PETUGAS (Kader / PJTB)
        if ($user->user_type_id == 3) {
            $officer = Officer::where('user_id', $user->id)->first();
            if (!$officer) {
                return collect([]);
            }

            // Using Patient::accessibleBy($user) strictly filters by village for Kader and Puskesmas for PJTB
            $patients = Patient::accessibleBy($user)
                ->whereHas('user', fn($q) => $q->where('is_active', true))
                ->with(['user', 'village', 'puskesmas'])
                ->get();

            return $patients->map(function ($p) {
                return [
                    'id'          => $p->user->id,
                    'patient_id'  => $p->id,
                    'name'        => $p->user->name,
                    'email'       => $p->user->email,
                    'phone'       => $p->user->phone,
                    'photo'       => $p->user->photo,
                    'role'        => 'Pasien',
                    'role_detail' => 'Pasien TB',
                    'sub_info'    => 'Desa ' . ($p->village?->name ?? '-') . ', RT ' . ($p->rt ?? '-') . '/RW ' . ($p->rw ?? '-'),
                ];
            })->values();
        }

        // 3. ADMIN: All active users except self
        return User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($u) {
                return [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'email'       => $u->email,
                    'phone'       => $u->phone,
                    'photo'       => $u->photo,
                    'role'        => match ((int) $u->user_type_id) {
                        1 => 'Admin',
                        2 => 'Pasien',
                        3 => 'Petugas',
                        default => 'User',
                    },
                    'role_detail' => 'Pengguna TB Care',
                    'sub_info'    => $u->email,
                ];
            })->values();
    }
}
