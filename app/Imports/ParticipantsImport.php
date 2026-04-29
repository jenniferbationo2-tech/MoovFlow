<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class ParticipantsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        Role::findOrCreate('participant');

        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));

            if ($email === '') {
                continue;
            }

            $participant = User::query()->firstOrNew([
                'email' => $email,
            ]);

            $participant->fill([
                'name' => (string) ($row['nom'] ?? $row['name'] ?? $email),
                'telephone' => $row['telephone'] ?? $row['phone'] ?? $participant->telephone,
            ]);

            if (! $participant->exists) {
                $participant->password = Str::password(16);
            }

            $participant->save();

            if (! $participant->hasRole('participant')) {
                $participant->assignRole('participant');
            }
        }
    }
}