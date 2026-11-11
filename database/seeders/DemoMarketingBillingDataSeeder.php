<?php

namespace Database\Seeders;

use App\Models\Affiliate;
use App\Models\Coupon;
use App\Models\EmailCampaign;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoMarketingBillingDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@subnetcongo.net')->first()
            ?? User::first();

        $users = User::where('id', '!=', $admin?->id)->limit(12)->pluck('id')->all();
        $students = array_slice($users, 0, 6);
        $partners = array_slice($users, 6, 5);

        // ---------------- Campagnes email ----------------
        $campaigns = [
            ['name' => 'Newsletter octobre 2026', 'subject' => 'Les nouveautés SubNetCongo Academy', 'audience' => 'all', 'status' => 'sent', 'sent' => 1280, 'opens' => 742, 'clicks' => 318, 'bounces' => 12, 'days' => -20],
            ['name' => 'Relance panier abandonné', 'subject' => 'Votre panier vous attend encore…', 'audience' => 'students', 'status' => 'sent', 'sent' => 342, 'opens' => 201, 'clicks' => 96, 'bounces' => 4, 'days' => -9],
            ['name' => 'Webinaire certification CCNA', 'subject' => 'Webinaire gratuit : préparez votre certification', 'audience' => 'students', 'status' => 'queued', 'sent' => 0, 'opens' => 0, 'clicks' => 0, 'bounces' => 0, 'days' => 1],
            ['name' => 'Offre rentrée instructeurs', 'subject' => 'Devenez instructeur certifié SubNetCongo', 'audience' => 'instructors', 'status' => 'scheduled', 'sent' => 0, 'opens' => 0, 'clicks' => 0, 'bounces' => 0, 'days' => 3],
            ['name' => 'Enquête satisfaction T4', 'subject' => 'Votre avis compte : enquête de satisfaction', 'audience' => 'all', 'status' => 'draft', 'sent' => 0, 'opens' => 0, 'clicks' => 0, 'bounces' => 0, 'days' => null],
        ];

        foreach ($campaigns as $c) {
            EmailCampaign::updateOrCreate(
                ['name' => $c['name']],
                [
                    'from_name' => 'SubNetCongo Academy',
                    'from_email' => 'academy@subnetcongo.net',
                    'subject' => $c['subject'],
                    'body' => '<p>Bonjour {{name}},</p><p>Découvrez nos actualités et offres du moment sur la plateforme SubNetCongo Academy.</p><p>Cordialement,<br>L\'équipe SubNetCongo</p>',
                    'target_audience' => $c['audience'],
                    'status' => $c['status'],
                    'user_id' => $admin?->id,
                    'total_recipients' => $c['sent'] ?: 450,
                    'sent_count' => $c['sent'],
                    'opens_count' => $c['opens'],
                    'clicks_count' => $c['clicks'],
                    'bounces_count' => $c['bounces'],
                    'sent_at' => $c['status'] === 'sent' ? now()->addDays($c['days']) : null,
                    'scheduled_at' => $c['status'] === 'scheduled' ? now()->addDays($c['days']) : null,
                ]
            );
        }

        // ---------------- Affiliés ----------------
        $affiliates = [
            ['name' => 'Grâce Mutombo', 'email' => 'grace.mutombo@example.cd', 'tier' => 'gold', 'rate' => 15, 'status' => 'active', 'clicks' => 4210, 'conv' => 187, 'earn' => 4210.50, 'unpaid' => 640.00, 'notes' => 'Ambassadrice régionale Kinshasa. Excellente conversion sur les cours réseaux.'],
            ['name' => 'Junior Kasongo', 'email' => 'j.kasongo@example.cd', 'tier' => 'silver', 'rate' => 10, 'status' => 'active', 'clicks' => 1890, 'conv' => 74, 'earn' => 1332.00, 'unpaid' => 0, 'notes' => null],
            ['name' => 'Sarah Ilunga', 'email' => 's.ilunga@example.cd', 'tier' => 'bronze', 'rate' => 7, 'status' => 'pending', 'clicks' => 320, 'conv' => 8, 'earn' => 96.00, 'unpaid' => 96.00, 'notes' => 'Candidature récente, en attente de validation du premier payout.'],
            ['name' => 'David Mbuyi', 'email' => 'd.mbuyi@example.cd', 'tier' => 'platinum', 'rate' => 20, 'status' => 'active', 'clicks' => 9800, 'conv' => 512, 'earn' => 15360.00, 'unpaid' => 2100.00, 'notes' => 'Top affilié — programme VIP avec supports marketing fournis.'],
            ['name' => 'Naomi Tshibanda', 'email' => 'n.tshibanda@example.cd', 'tier' => 'bronze', 'rate' => 5, 'status' => 'inactive', 'clicks' => 145, 'conv' => 2, 'earn' => 24.00, 'unpaid' => 0, 'notes' => 'Compte désactivé temporairement pour inactivité.'],
        ];

        foreach ($affiliates as $i => $a) {
            $uid = $partners[$i] ?? $students[0] ?? $admin?->id;
            $user = $uid ? User::find($uid) : null;
            Affiliate::updateOrCreate(
                ['payment_email' => $a['email']],
                [
                    'user_id' => $user?->id,
                    'referral_code' => 'AFF-' . strtoupper(substr(md5($a['email']), 0, 6)),
                    'commission_rate' => $a['rate'],
                    'total_earnings' => $a['earn'],
                    'unpaid_earnings' => $a['unpaid'],
                    'clicks' => $a['clicks'],
                    'conversions' => $a['conv'],
                    'status' => $a['status'],
                    'tier' => $a['tier'],
                    'notes' => $a['notes'],
                ]
            );
        }

        // ---------------- Abonnements ----------------
        $plans = SubscriptionPlan::all()->keyBy('code');
        $subs = [
            ['user' => 0, 'plan' => 'professional', 'status' => 'active', 'interval' => 'monthly', 'paid' => 2400, 'days' => -45],
            ['user' => 1, 'plan' => 'starter', 'status' => 'active', 'interval' => 'yearly', 'paid' => 3600, 'days' => -200],
            ['user' => 2, 'plan' => 'enterprise', 'status' => 'trialing', 'interval' => 'monthly', 'paid' => 0, 'days' => -5],
            ['user' => 3, 'plan' => 'professional', 'status' => 'canceled', 'interval' => 'monthly', 'paid' => 1200, 'days' => -120],
            ['user' => 4, 'plan' => 'starter', 'status' => 'past_due', 'interval' => 'monthly', 'paid' => 600, 'days' => -70],
        ];

        foreach ($subs as $s) {
            $uid = $students[$s['user']] ?? null;
            if (! $uid) continue;
            $plan = $plans[$s['plan']] ?? null;
            if (! $plan) continue;
            $user = User::find($uid);
            Subscription::updateOrCreate(
                ['user_id' => $uid, 'plan_code' => $plan->code],
                [
                    'name' => $user?->name,
                    'plan_name' => $plan->name,
                    'plan_price' => $plan->price,
                    'currency' => 'USD',
                    'interval' => $s['interval'],
                    'quantity' => 1,
                    'stripe_id' => 'sub_demo_' . strtoupper(substr(md5($uid . $plan->code), 0, 10)),
                    'stripe_status' => $s['status'],
                    'stripe_price' => $plan->stripe_price_id ?? null,
                    'starts_at' => now()->addDays($s['days']),
                    'ends_at' => now()->addDays($s['days'] + ($s['interval'] === 'yearly' ? 365 : 30)),
                    'status' => $s['status'],
                    'total_paid' => $s['paid'],
                ]
            );
        }

        // ---------------- Coupons ----------------
        $coupons = [
            ['code' => 'RENTREE2026', 'type' => 'percent', 'value' => 20, 'min' => 50, 'limit' => 500, 'perUser' => 1, 'used' => 137, 'days' => -10, 'exp' => 40, 'desc' => 'Campagne rentrée 2026 — 20% sur tous les cours'],
            ['code' => 'STUDENT10', 'type' => 'percent', 'value' => 10, 'min' => 0, 'limit' => null, 'perUser' => 3, 'used' => 58, 'days' => -60, 'exp' => 120, 'desc' => 'Réduction fidélité étudiants'],
            ['code' => 'BLACKFRIDAY25', 'type' => 'percent', 'value' => 25, 'min' => 100, 'limit' => 200, 'perUser' => 1, 'used' => 200, 'days' => -30, 'exp' => -1, 'desc' => 'Offre Black Friday — épuisée'],
            ['code' => 'PARTNER50', 'type' => 'fixed', 'value' => 50, 'min' => 200, 'limit' => null, 'perUser' => 2, 'used' => 23, 'days' => -5, 'exp' => 60, 'desc' => 'Coupon partenaires — 50$ de remise'],
            ['code' => 'BIENVENUE15', 'type' => 'percent', 'value' => 15, 'min' => 0, 'limit' => 1000, 'perUser' => 1, 'used' => 412, 'days' => -90, 'exp' => 200, 'desc' => 'Offre de bienvenue nouveaux inscrits'],
        ];

        foreach ($coupons as $cp) {
            Coupon::updateOrCreate(
                ['code' => $cp['code']],
                [
                    'description' => $cp['desc'],
                    'discount_type' => $cp['type'],
                    'discount_value' => $cp['value'],
                    'min_amount' => $cp['min'],
                    'usage_limit' => $cp['limit'],
                    'max_uses_per_user' => $cp['perUser'],
                    'used_count' => $cp['used'],
                    'status' => 'active',
                    'is_active' => true,
                    'starts_at' => now()->addDays($cp['days']),
                    'expires_at' => now()->addDays($cp['exp']),
                ]
            );
        }
    }
}
