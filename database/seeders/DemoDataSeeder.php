<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\Role;
use App\Models\Sector;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Synthetic records only. Nothing here comes from the Foundation's real
 * operations, and no clinical or personnel data is represented.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = collect([
            'Administration' => 'Head office, finance and governance',
            'Education'      => 'Schools and training centres',
            'Health'         => 'Hospital, clinics and community health',
            'Production'     => 'Workshops and the donations warehouse',
            'Projects'       => 'Community and donor-funded projects',
        ])->mapWithKeys(fn ($d, $n) => [$n => Sector::updateOrCreate(['name' => $n], ['description' => $d])]);

        $sites = collect([
            ['Administration','Head office','Port-au-Prince'],
            ['Education','Ecole St. Luc, Tabarre','Tabarre'],
            ['Education','Ecole St. Luc, Cite Soleil','Cite Soleil'],
            ['Health','Hopital St. Luc','Tabarre'],
            ['Health','Clinic, Fontamara','Carrefour'],
            ['Production','Production centre','Tabarre'],
            ['Projects','Field office','Croix-des-Bouquets'],
        ])->map(fn ($s) => Site::updateOrCreate(
            ['name' => $s[1]],
            ['sector_id' => $sectors[$s[0]]->id, 'commune' => $s[2]]
        ));

        $people = [
            ['Jaebets Dorsainvil','j.dorsainvil@stlukehaiti.org','Administrator','Administration','Head office','IT systems engineer','+509 3400 0101','email',true],
            ['Micheline Pierre','m.pierre@stlukehaiti.org','Sector manager','Education','Ecole St. Luc, Tabarre','Education coordinator','+509 3400 0112','sms',true],
            ['Ronald Jean-Baptiste','r.jeanbaptiste@stlukehaiti.org','Staff','Health','Hopital St. Luc','Ward nurse','+509 3400 0124','email',true],
            ['Carline Altidor','c.altidor@stlukehaiti.org','Auditor','Administration','Head office','Internal control officer','+509 3400 0130','email',true],
            ['Gerald Moise','g.moise@stlukehaiti.org','Sector manager','Production','Production centre','Workshop supervisor','+509 3400 0141','sms',true],
            ['Nadege Charles','n.charles@stlukehaiti.org','Staff','Education','Ecole St. Luc, Cite Soleil','Teacher','+509 3400 0155','email',true],
            ['Frantz Delva','f.delva@stlukehaiti.org','Staff','Projects','Field office','Project officer','+509 3400 0166','email',true],
            ['Yolette Saint-Fleur','y.saintfleur@stlukehaiti.org','Staff','Health','Clinic, Fontamara','Community health agent','+509 3400 0178','sms',true],
            ['Wilner Etienne','w.etienne@stlukehaiti.org','Staff','Production','Production centre','Quality control technician','+509 3400 0182','email',false],
        ];

        foreach ($people as [$name,$email,$role,$sector,$site,$position,$phone,$channel,$active]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'name'        => $name,
                'role_id'     => Role::where('name', $role)->value('id'),
                'sector_id'   => $sectors[$sector]->id,
                'site_id'     => $sites->firstWhere('name', $site)?->id,
                'position'    => $position,
                'mfa_channel' => $channel,
                'is_active'   => $active,
            ]);
            $user->password = 'Demo!Passw0rd2026';   // hashed by the model cast
            $user->phone    = $phone;                // encrypted by the mutator
            $user->password_changed_at = now();
            $user->save();
        }

        $admin   = User::where('email','j.dorsainvil@stlukehaiti.org')->first();
        $manager = User::where('email','m.pierre@stlukehaiti.org')->first();

        collect([
            ['Payroll cut-off moves to the 22nd','Sector managers should submit attendance sheets by the 22nd of each month so head office can close payroll on time.','all',$admin],
            ['School reopening schedule, Tabarre','Classes resume on Monday. Teachers are asked to confirm availability with the coordinator before Friday.','Education',$manager],
            ['New document classification rules','Any file holding staff personal details must be uploaded as restricted. Internal remains the default for operational documents.','all',$admin],
        ])->each(fn ($n) => Announcement::firstOrCreate(
            ['title' => $n[0]],
            ['body' => $n[1], 'audience' => $n[2], 'author_id' => $n[3]->id, 'published_at' => now()]
        ));

        collect([
            ['Staff handbook 2026','public','Administration',$admin],
            ['Education sector work plan','internal','Education',$manager],
            ['Salary scale review','restricted','Administration',$admin],
            ['Donor report, Q1','restricted','Projects',$admin],
        ])->each(fn ($d) => Document::firstOrCreate(
            ['title' => $d[0]],
            [
                'stored_path'    => 'documents/seed/'.str($d[0])->slug().'.pdf',
                'original_name'  => $d[0].'.pdf',
                'mime'           => 'application/pdf',
                'size_bytes'     => random_int(80_000, 2_400_000),
                'sector_id'      => $sectors[$d[2]]->id,
                'classification' => $d[1],
                'owner_id'       => $d[3]->id,
            ]
        ));
    }
}
