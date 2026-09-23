<?php

namespace Database\Seeders;

use App\Models\AiExamination;
use App\Models\BcsRecord;
use App\Models\Breed;
use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\FeedRecord;
use App\Models\HealthRecord;
use App\Models\ReproductionRecord;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Models\Vaccine;
use App\Models\WeightRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $adminRole = Role::findOrCreate('admin');
        $farmerRole = Role::findOrCreate('peternak');

        $admin = User::updateOrCreate(
            ['email' => 'admin@wowsapi.id'],
            [
                'name' => 'Admin Wow Sapi',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->syncRoles([$adminRole]);

        $breeds = collect([
            ['Simental', 'SIM'],
            ['Limousin', 'LIM'],
            ['Brahman', 'BRA'],
            ['PO', 'PO'],
            ['Bali', 'BAL'],
        ])->map(fn ($b) => Breed::updateOrCreate(['code' => $b[1]], ['name' => $b[0], 'is_active' => true]));

        $vaccines = collect([
            ['Vaksin LSD (Ulangan)', 180],
            ['Vaksin SE (Tahap 2)', 90],
            ['Vaksin Brucellosis', 365],
            ['Vaksin PMK', 180],
            ['Vaksin Anthrax', 365],
        ])->map(fn ($v) => Vaccine::updateOrCreate(['name' => $v[0]], ['default_interval_days' => $v[1], 'is_active' => true]));

        $farmersData = [
            ['Budi Santoso', 'budi@wowsapi.id', 'Sari Ternak Sejahtera', '081234500012', 'Klaten', 'Jawa Tengah'],
            ['Sari Ternak', 'sari@wowsapi.id', 'Sari Ternak', '081234500021', 'Boyolali', 'Jawa Tengah'],
            ['Kelompok Maju', 'maju@wowsapi.id', 'Kelompok Maju', '081234500045', 'Sragen', 'Jawa Tengah'],
            ['Agro Mandiri', 'agro@wowsapi.id', 'Agro Mandiri', '081234500057', 'Karanganyar', 'Jawa Tengah'],
            ['Tani Makmur', 'makmur@wowsapi.id', 'Tani Makmur', '081234500076', 'Sukoharjo', 'Jawa Tengah'],
            ['Wahyu Peternak', 'wahyu@wowsapi.id', 'Wahyu Farm', '081234500088', 'Wonogiri', 'Jawa Tengah'],
            ['Dewi Lestari', 'dewi@wowsapi.id', 'Lestari Farm', '081234500099', 'Klaten', 'Jawa Tengah'],
            ['Hendra Pratama', 'hendra@wowsapi.id', 'Pratama Farm', '081234500101', 'Solo', 'Jawa Tengah'],
        ];

        $farmers = collect();
        foreach ($farmersData as $row) {
            $user = User::updateOrCreate(
                ['email' => $row[1]],
                [
                    'name' => $row[0],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$farmerRole]);
            $profile = FarmerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'farm_name' => $row[2],
                    'phone' => $row[3],
                    'regency' => $row[4],
                    'province' => $row[5],
                    'village' => 'Tegalsari',
                    'district' => 'Delanggu',
                ]
            );
            $farmers->push($profile);
        }

        $cattleSeed = [
            ['S-012', 'Budi Santoso', 512, 'sehat'],
            ['S-021', 'Sari Ternak', 478, 'sehat'],
            ['S-045', 'Kelompok Maju', 436, 'suspek'],
            ['S-057', 'Agro Mandiri', 525, 'sehat'],
            ['S-076', 'Tani Makmur', 482, 'sehat'],
            ['S-088', 'Wahyu Peternak', 390, 'sehat'],
            ['S-099', 'Dewi Lestari', 455, 'sehat'],
            ['S-101', 'Hendra Pratama', 501, 'positif'],
            ['S-110', 'Budi Santoso', 360, 'sehat'],
            ['S-118', 'Sari Ternak', 410, 'sehat'],
            ['S-125', 'Kelompok Maju', 470, 'sehat'],
            ['S-132', 'Agro Mandiri', 445, 'suspek'],
            ['S-140', 'Tani Makmur', 530, 'sehat'],
            ['S-155', 'Wahyu Peternak', 318, 'sehat'],
            ['S-162', 'Dewi Lestari', 492, 'sehat'],
        ];

        $scanDates = [
            now()->setTime(8, 30),
            now()->setTime(7, 15),
            now()->subDay()->setTime(16, 20),
            now()->subDay()->setTime(14, 10),
            now()->subDay()->setTime(11, 5),
        ];

        foreach ($cattleSeed as $i => $row) {
            $profile = $farmers->first(fn ($p) => $p->user->name === $row[1]) ?? $farmers->first();
            $cattle = Cattle::updateOrCreate(
                ['code' => $row[0]],
                [
                    'farmer_id' => $profile->id,
                    'breed_id' => $breeds[$i % $breeds->count()]->id,
                    'name' => 'Sapi '.$row[0],
                    'sex' => $i % 3 === 0 ? 'male' : 'female',
                    'birth_date' => now()->subMonths(18 + $i)->toDateString(),
                    'color' => 'Cokelat',
                    'origin' => $profile->regency,
                    'entry_date' => now()->subMonths(8)->toDateString(),
                    'status' => 'active',
                    'qr_token' => (string) Str::uuid(),
                ]
            );

            $base = $row[2];
            for ($m = 5; $m >= 0; $m--) {
                WeightRecord::updateOrCreate(
                    [
                        'cattle_id' => $cattle->id,
                        'source' => 'manual',
                        'measured_at' => now()->subMonths($m)->startOfMonth()->addDays(10),
                    ],
                    [
                        'weight_kg' => $base - ($m * 12) + ($i % 5),
                        'created_by' => $admin->id,
                        'notes' => 'Pencatatan bulanan',
                    ]
                );
            }

            $health = $row[3];
            $detected = $health !== 'sehat';
            $label = match ($health) {
                'suspek' => 'Perlu Pemeriksaan Lanjutan',
                'positif' => 'Terindikasi Lumpy Skin',
                default => 'Tidak Terindikasi Lumpy Skin',
            };
            $exam = AiExamination::updateOrCreate(
                ['cattle_id' => $cattle->id, 'type' => 'combined'],
                [
                    'user_id' => $admin->id,
                    'image_path' => 'cattle/placeholder.svg',
                    'cow_count' => 1,
                    'detector_confidence' => 0.91,
                    'estimated_weight_kg' => $base,
                    'lumpy_detected' => $detected,
                    'lumpy_label' => $label,
                    'lumpy_probability' => $health === 'suspek' ? 0.58 : ($detected ? 0.88 : 0.12),
                    'raw_response' => ['seed' => true],
                    'status' => 'success',
                    'examined_at' => $scanDates[$i % count($scanDates)],
                ]
            );
            WeightRecord::updateOrCreate(
                ['cattle_id' => $cattle->id, 'ai_examination_id' => $exam->id],
                [
                    'source' => 'ai',
                    'weight_kg' => $base,
                    'measured_at' => $exam->examined_at,
                    'created_by' => $admin->id,
                    'notes' => 'Estimasi Bobot AI',
                ]
            );

            BcsRecord::updateOrCreate(
                ['cattle_id' => $cattle->id],
                [
                    'score' => 3.2,
                    'category' => 'Ideal',
                    'assessed_at' => now()->subDays(4),
                    'created_by' => $admin->id,
                ]
            );

            if ($detected) {
                HealthRecord::updateOrCreate(
                    ['cattle_id' => $cattle->id, 'title' => $label],
                    [
                        'type' => 'lumpy',
                        'description' => 'Hasil AI menunjukkan indikasi Lumpy Skin. Disarankan pemeriksaan lanjutan.',
                        'occurred_at' => $exam->examined_at,
                        'status' => 'perlu_pemeriksaan',
                        'created_by' => $admin->id,
                    ]
                );
            }

            $vaccine = $vaccines[$i % $vaccines->count()];
            VaccinationSchedule::updateOrCreate(
                ['cattle_id' => $cattle->id, 'vaccine_id' => $vaccine->id],
                [
                    'scheduled_date' => now()->addDays($i),
                    'status' => 'scheduled',
                    'created_by' => $admin->id,
                    'notes' => $vaccine->name,
                ]
            );

            FeedRecord::updateOrCreate(
                ['cattle_id' => $cattle->id, 'feed_name' => 'Hijauan + konsentrat'],
                [
                    'quantity' => 12,
                    'unit' => 'kg',
                    'cost' => 85000,
                    'fed_at' => now()->subDay(),
                    'created_by' => $profile->user_id,
                ]
            );

            if ($cattle->sex === 'female') {
                ReproductionRecord::updateOrCreate(
                    ['cattle_id' => $cattle->id, 'type' => 'insemination'],
                    [
                        'event_date' => now()->subMonths(2),
                        'inseminator' => 'Petugas IB',
                        'created_by' => $admin->id,
                    ]
                );
            }
        }

        $admin->notify(new \App\Notifications\SystemAlertNotification(
            'Sapi S-045 terdeteksi gejala Lumpy Skin',
            '2 jam yang lalu'
        ));
        $admin->notify(new \App\Notifications\SystemAlertNotification(
            'Vaksinasi menjelang jadwal',
            '5 ekor sapi perlu vaksin minggu ini'
        ));
        $admin->notify(new \App\Notifications\SystemAlertNotification(
            'Scan AI berhasil',
            'Sapi S-021 · Bobot 478 kg'
        ));
        $admin->notify(new \App\Notifications\SystemAlertNotification(
            'Peternak baru mendaftar',
            'Kelompok Tani Maju Sejahtera'
        ));
    }
}
