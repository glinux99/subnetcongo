<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Affiliate;
use App\Models\Referral;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AffiliateSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::role('instructor')->get();
        $students = User::role('student')->get();
        $courses = Course::where('status', 'published')->get();

        if ($instructors->isEmpty() || $students->isEmpty() || $courses->isEmpty()) {
            $this->command->info('Données insuffisantes pour créer des affiliés de test.');
            return;
        }

        $tiers = ['bronze', 'silver', 'gold', 'platinum'];
        $commissionRates = ['bronze' => 10, 'silver' => 15, 'gold' => 25, 'platinum' => 40];
        $statuses = ['active', 'pending', 'inactive'];

        foreach ($instructors->take(3) as $index => $instructor) {
            $tier = $tiers[$index % count($tiers)];
            
            $affiliate = Affiliate::create([
                'user_id' => $instructor->id,
                'referral_code' => strtoupper(Str::slug($instructor->name)) . rand(100, 999),
                'commission_rate' => $commissionRates[$tier],
                'total_earnings' => rand(500, 5000),
                'unpaid_earnings' => rand(100, 1000),
                'clicks' => rand(500, 5000),
                'conversions' => rand(10, 150),
                'status' => $statuses[array_rand($statuses)],
                'payment_email' => $instructor->email,
                'tier' => $tier,
                'notes' => 'Affilié créé automatiquement lors du seeding',
            ]);

            // Create some referrals for this affiliate
            $referralCount = rand(5, 20);
            for ($i = 0; $i < $referralCount; $i++) {
                $student = $students->random();
                $course = $courses->random();
                $amount = rand(29, 199);
                $commission = round($amount * ($commissionRates[$tier] / 100), 2);

                Referral::create([
                    'affiliate_id' => $affiliate->id,
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'amount' => $amount,
                    'commission' => $commission,
                    'status' => ['pending', 'approved', 'paid'][array_rand(['pending', 'approved', 'paid'])],
                    'clicked_at' => now()->subDays(rand(1, 30)),
                    'converted_at' => rand(0, 1) ? now()->subDays(rand(1, 15)) : null,
                ]);
            }
        }

        // Create a few more affiliates from students
        foreach ($students->take(5) as $index => $student) {
            $tier = $tiers[array_rand($tiers)];
            
            $affiliate = Affiliate::create([
                'user_id' => $student->id,
                'referral_code' => strtoupper(Str::slug($student->name)) . rand(100, 999),
                'commission_rate' => $commissionRates[$tier],
                'total_earnings' => rand(100, 2000),
                'unpaid_earnings' => rand(50, 500),
                'clicks' => rand(100, 2000),
                'conversions' => rand(5, 50),
                'status' => 'active',
                'payment_email' => $student->email,
                'tier' => $tier,
                'notes' => 'Étudiant devenu affilié',
            ]);

            // Create some referrals
            $referralCount = rand(3, 15);
            for ($i = 0; $i < $referralCount; $i++) {
                $referredStudent = $students->where('id', '!=', $student->id)->random();
                $course = $courses->random();
                $amount = rand(29, 199);
                $commission = round($amount * ($commissionRates[$tier] / 100), 2);

                Referral::create([
                    'affiliate_id' => $affiliate->id,
                    'user_id' => $referredStudent->id,
                    'course_id' => $course->id,
                    'amount' => $amount,
                    'commission' => $commission,
                    'status' => 'approved',
                    'clicked_at' => now()->subDays(rand(1, 30)),
                    'converted_at' => now()->subDays(rand(1, 10)),
                ]);
            }
        }

        $this->command->info('Affiliés et parrainages créés avec succès !');
    }
}