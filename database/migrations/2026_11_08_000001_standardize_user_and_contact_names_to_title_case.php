<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Helpers\NameFormatter;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardizes all existing person names to Title Case across the database.
     */
    public function up(): void
    {
        // 1. Standardize users.name
        if (DB::getSchemaBuilder()->hasTable('users') && DB::getSchemaBuilder()->hasColumn('users', 'name')) {
            DB::table('users')->orderBy('id')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    if (!empty($user->name)) {
                        $formatted = NameFormatter::format($user->name);
                        if ($formatted !== $user->name) {
                            DB::table('users')->where('id', $user->id)->update([
                                'name' => $formatted,
                            ]);
                        }
                    }
                }
            });
        }

        // 2. Standardize close_contacts.name
        if (DB::getSchemaBuilder()->hasTable('close_contacts') && DB::getSchemaBuilder()->hasColumn('close_contacts', 'name')) {
            DB::table('close_contacts')->orderBy('id')->chunk(100, function ($contacts) {
                foreach ($contacts as $contact) {
                    if (!empty($contact->name)) {
                        $formatted = NameFormatter::format($contact->name);
                        if ($formatted !== $contact->name) {
                            DB::table('close_contacts')->where('id', $contact->id)->update([
                                'name' => $formatted,
                            ]);
                        }
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Name Title Case transformation is inherently non-destructive,
        // no down action needed.
    }
};
