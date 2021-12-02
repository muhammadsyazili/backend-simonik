<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $roles = ['super-admin', 'admin', 'data-entry', 'employee'];

        for ($i = 0; $i < count($roles); $i++) {
            DB::table('roles')->insert([
                'name' => $roles[$i],
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        $levels = [
            ['id' => 1, 'parent_id' => null, 'name' => 'Super Master', 'slug' => 'super-master'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'UIW', 'slug' => 'uiw'],
            ['id' => 3, 'parent_id' => 2, 'name' => 'UP3', 'slug' => 'up3'],
            ['id' => 4, 'parent_id' => 3, 'name' => 'ULP', 'slug' => 'ulp'],
            ['id' => 5, 'parent_id' => 2, 'name' => 'UP2K', 'slug' => 'up2k'],
            ['id' => 6, 'parent_id' => 2, 'name' => 'UP2D', 'slug' => 'up2d'],
        ];

        for ($i = 0; $i < count($levels); $i++) {
            DB::table('levels')->insert([
                'id' => $levels[$i]['id'],
                'name' => $levels[$i]['name'],
                'slug' => $levels[$i]['slug'],
                'parent_id' => $levels[$i]['parent_id'],
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        $uuid_up2k = [
            'muara-bungo' => (string) Str::orderedUuid(),
            'bengkulu' => (string) Str::orderedUuid(),
            'palembang' => (string) Str::orderedUuid(),
            'lahat' => (string) Str::orderedUuid(),
            'jambi' => (string) Str::orderedUuid(),
            'ogan-ilir' => (string) Str::orderedUuid(),
        ];

        $uuid_up2d = [
            'muara-bungo' => (string) Str::orderedUuid(),
            'bengkulu' => (string) Str::orderedUuid(),
            'palembang' => (string) Str::orderedUuid(),
            'lahat' => (string) Str::orderedUuid(),
            'jambi' => (string) Str::orderedUuid(),
            'ogan-ilir' => (string) Str::orderedUuid(),
        ];

        $units = [
            ['id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'name' => 'UIW - S2JB', 'slug' => 'uiw-s2jb', 'parent_id' => null, 'level_id' => 2],

            ['id' => '28712ea2-7fbf-4a2e-8167-d7cf66b89499', 'name' => 'UP3 - MUARA BUNGO', 'slug' => 'up3-muara-bungo', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 3],
            ['id' => '41220c8e-6419-4e4b-972e-2f8e8d8a84cb', 'name' => 'UP3 - BENGKULU', 'slug' => 'up3-bengkulu', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 3],
            ['id' => '6078b31c-6bc9-4643-a4e6-e9a33d69547a', 'name' => 'UP3 - PALEMBANG', 'slug' => 'up3-palembang', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 3],
            ['id' => '455b7b18-093d-4b5d-97f1-348e4fddccb3', 'name' => 'UP3 - LAHAT', 'slug' => 'up3-lahat', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 3],
            ['id' => '65a94f8e-f5f7-40f9-8a71-7002307dba4f', 'name' => 'UP3 - JAMBI', 'slug' => 'up3-jambi', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 3],
            ['id' => 'c836809b-5675-44bf-9241-944aa2aa4014', 'name' => 'UP3 - OGAN ILIR', 'slug' => 'up3-ogan-ilir', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 3],

            ['id' => $uuid_up2k['muara-bungo'], 'name' => 'UP2K - MUARA BUNGO', 'slug' => 'up2k-muara-bungo', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 5],
            ['id' => $uuid_up2k['bengkulu'], 'name' => 'UP2K - BENGKULU', 'slug' => 'up2k-bengkulu', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 5],
            ['id' => $uuid_up2k['palembang'], 'name' => 'UP2K - PALEMBANG', 'slug' => 'up2k-palembang', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 5],
            ['id' => $uuid_up2k['lahat'], 'name' => 'UP2K - LAHAT', 'slug' => 'up2k-lahat', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 5],
            ['id' => $uuid_up2k['jambi'], 'name' => 'UP2K - JAMBI', 'slug' => 'up2k-jambi', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 5],
            ['id' => $uuid_up2k['ogan-ilir'], 'name' => 'UP2K - OGAN ILIR', 'slug' => 'up2k-ogan-ilir', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 5],

            ['id' => $uuid_up2d['muara-bungo'], 'name' => 'UP2D - MUARA BUNGO', 'slug' => 'up2d-muara-bungo', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 6],
            ['id' => $uuid_up2d['bengkulu'], 'name' => 'UP2D - BENGKULU', 'slug' => 'up2d-bengkulu', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 6],
            ['id' => $uuid_up2d['palembang'], 'name' => 'UP2D - PALEMBANG', 'slug' => 'up2d-palembang', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 6],
            ['id' => $uuid_up2d['lahat'], 'name' => 'UP2D - LAHAT', 'slug' => 'up2d-lahat', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 6],
            ['id' => $uuid_up2d['jambi'], 'name' => 'UP2D - JAMBI', 'slug' => 'up2d-jambi', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 6],
            ['id' => $uuid_up2d['ogan-ilir'], 'name' => 'UP2D - OGAN ILIR', 'slug' => 'up2d-ogan-ilir', 'parent_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'level_id' => 6],
        ];

        for ($i = 0; $i < count($units); $i++) {
            DB::table('units')->insert([
                'id' => $units[$i]['id'],
                'name' => $units[$i]['name'],
                'slug' => $units[$i]['slug'],
                'parent_id' => $units[$i]['parent_id'],
                'level_id' => $units[$i]['level_id'],
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }


        $up3_id = [
            '28712ea2-7fbf-4a2e-8167-d7cf66b89499',
            '41220c8e-6419-4e4b-972e-2f8e8d8a84cb',
            '6078b31c-6bc9-4643-a4e6-e9a33d69547a',
            '455b7b18-093d-4b5d-97f1-348e4fddccb3',
            '65a94f8e-f5f7-40f9-8a71-7002307dba4f',
            'c836809b-5675-44bf-9241-944aa2aa4014',
        ];

        for ($i=0; $i < count($up3_id); $i++) {
            $response = Http::get("http://asiikpro.pln-jatim.co.id/api/peta-pohon/data-migration/ulp", ['unit_id' => $up3_id[$i]]);

            for ($j = 0; $j < count($response->object()->data); $j++) {
                DB::table('units')->insert([
                    'id' => $response->object()->data[$j]->id,
                    'name' => sprintf('ULP - %s', strtoupper($response->object()->data[$j]->nama)),
                    'slug' => sprintf('ulp-%s', Str::slug($response->object()->data[$j]->nama, '-')),
                    'parent_id' => $response->object()->data[$j]->unit_id,
                    'level_id' => 4,
                    'created_at' =>  \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);

                DB::table('users')->insert([
                    'id' => (string) Str::orderedUuid(),
                    'nip' => null,
                    'name' => sprintf('Admin ULP %s', strtoupper($response->object()->data[$j]->nama)),
                    'username' => sprintf('admin.ulp.%s', Str::slug($response->object()->data[$j]->nama, '-')),
                    'email' => sprintf('admin.ulp.%s@email.com', Str::slug($response->object()->data[$j]->nama, '-')),
                    'actived' => 1,
                    'password' => Hash::make('1234567890'),
                    'unit_id' => $response->object()->data[$j]->id,
                    'role_id' => 2,
                    'created_at' =>  \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);

                DB::table('users')->insert([
                    'id' => (string) Str::orderedUuid(),
                    'nip' => null,
                    'name' => sprintf('Data Entry ULP %s', strtoupper($response->object()->data[$j]->nama)),
                    'username' => sprintf('data-entry.ulp.%s', Str::slug($response->object()->data[$j]->nama, '-')),
                    'email' => sprintf('data-entry.ulp.%s@email.com', Str::slug($response->object()->data[$j]->nama, '-')),
                    'actived' => 1,
                    'password' => Hash::make('1234567890'),
                    'unit_id' => $response->object()->data[$j]->id,
                    'role_id' => 3,
                    'created_at' =>  \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);
            }
        }

        $users = [
            ['nip' => null, 'name' => 'Super Admin', 'username' => 'super.admin', 'email' => 'super.admin@email.com', 'unit_id' => null, 'role_id' => 1],

            ['nip' => null, 'name' => 'Admin UIW', 'username' => 'admin.uiw.s2jb', 'email' => 'admin.uiw.s2jb@email.com', 'unit_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'role_id' => 2],

            ['nip' => null, 'name' => 'Admin UP3 MUARA BUNGO', 'username' => 'admin.up3.muara-bungo', 'email' => 'admin.up3.muara-bungo@email.com', 'unit_id' => '28712ea2-7fbf-4a2e-8167-d7cf66b89499', 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP3 BENGKULU', 'username' => 'admin.up3.bengkulu', 'email' => 'admin.up3.bengkulu@email.com', 'unit_id' => '41220c8e-6419-4e4b-972e-2f8e8d8a84cb', 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP3 PALEMBANG', 'username' => 'admin.up3.palembang', 'email' => 'admin.up3.palembang@email.com', 'unit_id' => '6078b31c-6bc9-4643-a4e6-e9a33d69547a', 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP3 LAHAT', 'username' => 'admin.up3.lahat', 'email' => 'admin.up3.lahat@email.com', 'unit_id' => '455b7b18-093d-4b5d-97f1-348e4fddccb3', 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP3 JAMBI', 'username' => 'admin.up3.jambi', 'email' => 'admin.up3.jambi@email.com', 'unit_id' => '65a94f8e-f5f7-40f9-8a71-7002307dba4f', 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP3 OGAN ILIR', 'username' => 'admin.up3.ogan-ilir', 'email' => 'admin.up3.ogan-ilir@email.com', 'unit_id' => 'c836809b-5675-44bf-9241-944aa2aa4014', 'role_id' => 2],

            ['nip' => null, 'name' => 'Admin UP2K MUARA BUNGO', 'username' => 'admin.up2k.muara-bungo', 'email' => 'admin.up2k.muara-bungo@email.com', 'unit_id' => $uuid_up2k['muara-bungo'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2K BENGKULU', 'username' => 'admin.up2k.bengkulu', 'email' => 'admin.up2k.bengkulu@email.com', 'unit_id' => $uuid_up2k['bengkulu'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2K PALEMBANG', 'username' => 'admin.up2k.palembang', 'email' => 'admin.up2k.palembang@email.com', 'unit_id' => $uuid_up2k['palembang'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2K LAHAT', 'username' => 'admin.up2k.lahat', 'email' => 'admin.up2k.lahat@email.com', 'unit_id' => $uuid_up2k['lahat'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2K JAMBI', 'username' => 'admin.up2k.jambi', 'email' => 'admin.up2k.jambi@email.com', 'unit_id' => $uuid_up2k['jambi'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2K OGAN ILIR', 'username' => 'admin.up2k.ogan-ilir', 'email' => 'admin.up2k.ogan-ilir@email.com', 'unit_id' => $uuid_up2k['ogan-ilir'], 'role_id' => 2],

            ['nip' => null, 'name' => 'Admin UP2D MUARA BUNGO', 'username' => 'admin.up2d.muara-bungo', 'email' => 'admin.up2d.muara-bungo@email.com', 'unit_id' => $uuid_up2d['muara-bungo'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2D BENGKULU', 'username' => 'admin.up2d.bengkulu', 'email' => 'admin.up2d.bengkulu@email.com', 'unit_id' => $uuid_up2d['bengkulu'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2D PALEMBANG', 'username' => 'admin.up2d.palembang', 'email' => 'admin.up2d.palembang@email.com', 'unit_id' => $uuid_up2d['palembang'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2D LAHAT', 'username' => 'admin.up2d.lahat', 'email' => 'admin.up2d.lahat@email.com', 'unit_id' => $uuid_up2d['lahat'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2D JAMBI', 'username' => 'admin.up2d.jambi', 'email' => 'admin.up2d.jambi@email.com', 'unit_id' => $uuid_up2d['jambi'], 'role_id' => 2],
            ['nip' => null, 'name' => 'Admin UP2D OGAN ILIR', 'username' => 'admin.up2d.ogan-ilir', 'email' => 'admin.up2d.ogan-ilir@email.com', 'unit_id' => $uuid_up2d['ogan-ilir'], 'role_id' => 2],

            ['nip' => null, 'name' => 'Data Entry UIW', 'username' => 'data-entry.uiw.s2jb', 'email' => 'data-entry.uiw.s2jb@email.com', 'unit_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283', 'role_id' => 3],

            ['nip' => null, 'name' => 'Data Entry UP3 MUARA BUNGO', 'username' => 'data-entry.up3.muara-bungo', 'email' => 'data-entry.up3.muara-bungo@email.com', 'unit_id' => '28712ea2-7fbf-4a2e-8167-d7cf66b89499', 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP3 BENGKULU', 'username' => 'data-entry.up3.bengkulu', 'email' => 'data-entry.up3.bengkulu@email.com', 'unit_id' => '41220c8e-6419-4e4b-972e-2f8e8d8a84cb', 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP3 PALEMBANG', 'username' => 'data-entry.up3.palembang', 'email' => 'data-entry.up3.palembang@email.com', 'unit_id' => '6078b31c-6bc9-4643-a4e6-e9a33d69547a', 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP3 LAHAT', 'username' => 'data-entry.up3.lahat', 'email' => 'data-entry.up3.lahat@email.com', 'unit_id' => '455b7b18-093d-4b5d-97f1-348e4fddccb3', 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP3 JAMBI', 'username' => 'data-entry.up3.jambi', 'email' => 'data-entry.up3.jambi@email.com', 'unit_id' => '65a94f8e-f5f7-40f9-8a71-7002307dba4f', 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP3 OGAN ILIR', 'username' => 'data-entry.up3.ogan-ilir', 'email' => 'data-entry.up3.ogan-ilir@email.com', 'unit_id' => 'c836809b-5675-44bf-9241-944aa2aa4014', 'role_id' => 3],

            ['nip' => null, 'name' => 'Data Entry UP2K MUARA BUNGO', 'username' => 'data-entry.up2k.muara-bungo', 'email' => 'data-entry.up2k.muara-bungo@email.com', 'unit_id' => $uuid_up2k['muara-bungo'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2K BENGKULU', 'username' => 'data-entry.up2k.bengkulu', 'email' => 'data-entry.up2k.bengkulu@email.com', 'unit_id' => $uuid_up2k['bengkulu'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2K PALEMBANG', 'username' => 'data-entry.up2k.palembang', 'email' => 'data-entry.up2k.palembang@email.com', 'unit_id' => $uuid_up2k['palembang'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2K LAHAT', 'username' => 'data-entry.up2k.lahat', 'email' => 'data-entry.up2k.lahat@email.com', 'unit_id' => $uuid_up2k['lahat'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2K JAMBI', 'username' => 'data-entry.up2k.jambi', 'email' => 'data-entry.up2k.jambi@email.com', 'unit_id' => $uuid_up2k['jambi'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2K OGAN ILIR', 'username' => 'data-entry.up2k.ogan-ilir', 'email' => 'data-entry.up2k.ogan-ilir@email.com', 'unit_id' => $uuid_up2k['ogan-ilir'], 'role_id' => 3],

            ['nip' => null, 'name' => 'Data Entry UP2D MUARA BUNGO', 'username' => 'data-entry.up2d.muara-bungo', 'email' => 'data-entry.up2d.muara-bungo@email.com', 'unit_id' => $uuid_up2d['muara-bungo'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2D BENGKULU', 'username' => 'data-entry.up2d.bengkulu', 'email' => 'data-entry.up2d.bengkulu@email.com', 'unit_id' => $uuid_up2d['bengkulu'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2D PALEMBANG', 'username' => 'data-entry.up2d.palembang', 'email' => 'data-entry.up2d.palembang@email.com', 'unit_id' => $uuid_up2d['palembang'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2D LAHAT', 'username' => 'data-entry.up2d.lahat', 'email' => 'data-entry.up2d.lahat@email.com', 'unit_id' => $uuid_up2d['lahat'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2D JAMBI', 'username' => 'data-entry.up2d.jambi', 'email' => 'data-entry.up2d.jambi@email.com', 'unit_id' => $uuid_up2d['jambi'], 'role_id' => 3],
            ['nip' => null, 'name' => 'Data Entry UP2D OGAN ILIR', 'username' => 'data-entry.up2d.ogan-ilir', 'email' => 'data-entry.up2d.ogan-ilir@email.com', 'unit_id' => $uuid_up2d['ogan-ilir'], 'role_id' => 3],
        ];

        for ($i = 0; $i < count($users); $i++) {
            DB::table('users')->insert([
                'id' => (string) Str::orderedUuid(),
                'nip' => $users[$i]['nip'],
                'name' => $users[$i]['name'],
                'username' => $users[$i]['username'],
                'email' => $users[$i]['email'],
                'actived' => 1,
                'password' => Hash::make('1234567890'),
                'unit_id' => $users[$i]['unit_id'],
                'role_id' => $users[$i]['role_id'],
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        $response = Http::get("http://asiikpro.pln-jatim.co.id/api/peta-pohon/data-migration/users", ['induk_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283']);

        for ($i = 0; $i < count($response->object()->data); $i++) {
            DB::table('users')->insert([
                'id' => $response->object()->data[$i]->id,
                'nip' => $response->object()->data[$i]->nip,
                'name' => strtoupper($response->object()->data[$i]->nama),
                'username' => $response->object()->data[$i]->username,
                'email' => sprintf('%s@email.com', $response->object()->data[$i]->username),
                'actived' => 0,
                'password' => Hash::make('1234567890'),
                'unit_id' => '3fb49e43-537f-4df4-9a49-bbe9e9887283',
                'role_id' => 4,
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        $uuid_indicators = [
            (string) Str::orderedUuid(),
            (string) Str::orderedUuid(),
            (string) Str::orderedUuid(),
            (string) Str::orderedUuid(),
            (string) Str::orderedUuid(),
            (string) Str::orderedUuid(),
            (string) Str::orderedUuid(),
        ];

        $indicators = [
            [
                'id' => $uuid_indicators[0],
                'indicator' => 'Penjualan dan Pendapatan Tenaga Listrik',
                'formula' => null,
                'measure' => null,
                'weight' => null,
                'polarity' => null,
                'year' => null,
                'reducing_factor' => null,
                'dummy' => true,
                'validity' => null,
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 1,
                'code' => $uuid_indicators[0],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => null,
                'created_by' => null
            ],
            [
                'id' => $uuid_indicators[1],
                'indicator' => 'Penjualan Tenaga Listrik',
                'formula' => null,
                'measure' => null,
                'weight' => null,
                'polarity' => null,
                'year' => null,
                'reducing_factor' => null,
                'dummy' => true,
                'validity' => null,
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 2,
                'code' => $uuid_indicators[1],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => $uuid_indicators[0],
                'created_by' => null
            ],
            [
                'id' => $uuid_indicators[2],
                'indicator' => 'Intensifikasi',
                'formula' => 'kWh penjualan tenaga listrik dari Intensifikasi',
                'measure' => 'GWH',
                'weight' => json_encode([
                    'jan' => 8,
                    'feb' => 8,
                    'mar' => 8,
                    'apr' => 8,
                    'may' => 8,
                    'jun' => 8,
                    'jul' => 8,
                    'aug' => 8,
                    'sep' => 8,
                    'oct' => 8,
                    'nov' => 8,
                    'dec' => 8,
                ]),
                'polarity' => 1,
                'year' => null,
                'reducing_factor' => 0,
                'dummy' => false,
                'validity' => json_encode([
                    'jan' => true,
                    'feb' => true,
                    'mar' => true,
                    'apr' => true,
                    'may' => true,
                    'jun' => true,
                    'jul' => true,
                    'aug' => true,
                    'sep' => true,
                    'oct' => true,
                    'nov' => true,
                    'dec' => true,
                ]),
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 3,
                'code' => $uuid_indicators[2],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => $uuid_indicators[1],
                'created_by' => null
            ],
            [
                'id' => $uuid_indicators[3],
                'indicator' => 'Ekstensifikasi',
                'formula' => 'kWh penjualan tenaga listrik dari Ekstensifikasi',
                'measure' => 'GWH',
                'weight' => json_encode([
                    'jan' => 2,
                    'feb' => 2,
                    'mar' => 2,
                    'apr' => 2,
                    'may' => 2,
                    'jun' => 2,
                    'jul' => 2,
                    'aug' => 2,
                    'sep' => 2,
                    'oct' => 2,
                    'nov' => 2,
                    'dec' => 2,
                ]),
                'polarity' => 1,
                'year' => null,
                'reducing_factor' => 0,
                'dummy' => false,
                'validity' => json_encode([
                    'jan' => true,
                    'feb' => true,
                    'mar' => true,
                    'apr' => true,
                    'may' => true,
                    'jun' => true,
                    'jul' => true,
                    'aug' => true,
                    'sep' => true,
                    'oct' => true,
                    'nov' => true,
                    'dec' => true,
                ]),
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 4,
                'code' => $uuid_indicators[3],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => $uuid_indicators[1],
                'created_by' => null
            ],
            [
                'id' => $uuid_indicators[4],
                'indicator' => 'Rupiah Pendapatan Penjualan Tenaga Listrik',
                'formula' => 'Rupiah Pendapatan Penjualan Tenaga Listrik',
                'measure' => 'Milyar',
                'weight' => json_encode([
                    'jan' => 10,
                    'feb' => 10,
                    'mar' => 10,
                    'apr' => 10,
                    'may' => 10,
                    'jun' => 10,
                    'jul' => 10,
                    'aug' => 10,
                    'sep' => 10,
                    'oct' => 10,
                    'nov' => 10,
                    'dec' => 10,
                ]),
                'polarity' => 1,
                'year' => null,
                'reducing_factor' => 0,
                'dummy' => false,
                'validity' => json_encode([
                    'jan' => true,
                    'feb' => true,
                    'mar' => true,
                    'apr' => true,
                    'may' => true,
                    'jun' => true,
                    'jul' => true,
                    'aug' => true,
                    'sep' => true,
                    'oct' => true,
                    'nov' => true,
                    'dec' => true,
                ]),
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 5,
                'code' => $uuid_indicators[4],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => $uuid_indicators[0],
                'created_by' => null
            ],
            [
                'id' => $uuid_indicators[5],
                'indicator' => 'Pengendalian Piutang- Rata-Rata Tunggakan',
                'formula' => 'Rata-Rata Bulanan Rupiah Saldo Rekening Berjalan dan Rekening Tunggakan (PAL dan TS TUL 404 di luar Kogol 1)',
                'measure' => 'Rp (juta)',
                'weight' => json_encode([
                    'jan' => 10,
                    'feb' => 10,
                    'mar' => 10,
                    'apr' => 10,
                    'may' => 10,
                    'jun' => 10,
                    'jul' => 10,
                    'aug' => 10,
                    'sep' => 10,
                    'oct' => 10,
                    'nov' => 10,
                    'dec' => 10,
                ]),
                'polarity' => 1,
                'year' => null,
                'reducing_factor' => 0,
                'dummy' => false,
                'validity' => json_encode([
                    'jan' => true,
                    'feb' => true,
                    'mar' => true,
                    'apr' => true,
                    'may' => true,
                    'jun' => true,
                    'jul' => true,
                    'aug' => true,
                    'sep' => true,
                    'oct' => true,
                    'nov' => true,
                    'dec' => true,
                ]),
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 6,
                'code' => $uuid_indicators[5],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => $uuid_indicators[0],
                'created_by' => null
            ],
            [
                'id' => $uuid_indicators[6],
                'indicator' => 'Susut Jaringan (Tanpa E-min)',
                'formula' => '{(Total kWh Produksi Netto - PSGI - kWh kirim ke Unit Lain - PSSD - kWh Jual tanpa E-min)/Total kWh Produksi Netto} x 100%',
                'measure' => '%',
                'weight' => json_encode([
                    'jan' => 10,
                    'feb' => 10,
                    'mar' => 10,
                    'apr' => 10,
                    'may' => 10,
                    'jun' => 10,
                    'jul' => 10,
                    'aug' => 10,
                    'sep' => 10,
                    'oct' => 10,
                    'nov' => 10,
                    'dec' => 10,
                ]),
                'polarity' => 1,
                'year' => null,
                'reducing_factor' => 0,
                'dummy' => false,
                'validity' => json_encode([
                    'jan' => true,
                    'feb' => true,
                    'mar' => true,
                    'apr' => true,
                    'may' => true,
                    'jun' => true,
                    'jul' => true,
                    'aug' => true,
                    'sep' => true,
                    'oct' => true,
                    'nov' => true,
                    'dec' => true,
                ]),
                'label' => 'super-master',
                'unit_id' => null,
                'level_id' => 1,
                'order' => 7,
                'code' => $uuid_indicators[6],
                'parent_vertical_id' => null,
                'parent_horizontal_id' => $uuid_indicators[0],
                'created_by' => null
            ],
        ];

        for ($i = 0; $i < count($indicators); $i++) {
            DB::table('indicators')->insert([
                'id' => $indicators[$i]['id'],
                'indicator' => $indicators[$i]['indicator'],
                'formula' => $indicators[$i]['formula'],
                'measure' => $indicators[$i]['measure'],
                'weight' => $indicators[$i]['weight'],
                'polarity' => $indicators[$i]['polarity'],
                'year' => $indicators[$i]['year'],
                'reducing_factor' => $indicators[$i]['reducing_factor'],
                'dummy' => $indicators[$i]['dummy'],
                'validity' => $indicators[$i]['validity'],
                'reviewed' => 1,
                'referenced' => 1,

                'label' => $indicators[$i]['label'],
                'unit_id' => $indicators[$i]['unit_id'],
                'level_id' => $indicators[$i]['level_id'],
                'order' => $indicators[$i]['order'],
                'code' => $indicators[$i]['code'],
                'parent_vertical_id' => $indicators[$i]['parent_vertical_id'],
                'parent_horizontal_id' => $indicators[$i]['parent_horizontal_id'],
                'created_by' => $indicators[$i]['created_by'],

                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);
        }

        // $targets = [
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'dec', 'value' => 1200, 'locked' => 0],
        // ];

        // for ($i = 0; $i < count($targets); $i++) {
        //     DB::table('targets')->insert([
        //         'id' => (string) Str::orderedUuid(),
        //         'indicator_id' => $targets[$i]['indicator_id'],
        //         'month' => $targets[$i]['month'],
        //         'value' => $targets[$i]['value'],
        //         'locked' => $targets[$i]['locked'],

        //         'created_at' =>  \Carbon\Carbon::now(),
        //         'updated_at' => \Carbon\Carbon::now(),
        //     ]);
        // }

        // $realizations = [
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[2], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[3], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[4], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[5], 'month' => 'dec', 'value' => 1200, 'locked' => 0],

        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'jan', 'value' => 100, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'feb', 'value' => 200, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'mar', 'value' => 300, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'apr', 'value' => 400, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'may', 'value' => 500, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'jun', 'value' => 600, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'jul', 'value' => 700, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'aug', 'value' => 800, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'sep', 'value' => 900, 'locked' => 1],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'oct', 'value' => 1000, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'nov', 'value' => 1100, 'locked' => 0],
        //     ['indicator_id' => $uuid_indicators[6], 'month' => 'dec', 'value' => 1200, 'locked' => 0],
        // ];

        // for ($i = 0; $i < count($realizations); $i++) {
        //     DB::table('realizations')->insert([
        //         'id' => (string) Str::orderedUuid(),
        //         'indicator_id' => $realizations[$i]['indicator_id'],
        //         'month' => $realizations[$i]['month'],
        //         'value' => $realizations[$i]['value'],
        //         'locked' => $realizations[$i]['locked'],

        //         'created_at' =>  \Carbon\Carbon::now(),
        //         'updated_at' => \Carbon\Carbon::now(),
        //     ]);
        // }
    }
}
