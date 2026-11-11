<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    /** Création d'un plan d'abonnement (admin). */
    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        SubscriptionPlan::create($validated);

        return redirect()->route('billing.subscriptions.index', ['tab' => 'plans'])
            ->with('success', 'Plan créé avec succès.');
    }

    /** Mise à jour / activation-désactivation d'un plan. */
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $this->validatePlan($request, $plan);

        $plan->update($validated);

        return redirect()->route('billing.subscriptions.index', ['tab' => 'plans'])
            ->with('success', 'Plan mis à jour.');
    }

    /** Suppression : refusée si des abonnements référencent le plan (désactiver plutôt). */
    public function destroy(SubscriptionPlan $plan)
    {
        $usedBy = $plan->subscriptions()->count();

        if ($usedBy > 0) {
            return redirect()->route('billing.subscriptions.index', ['tab' => 'plans'])
                ->with('error', "Ce plan est utilisé par {$usedBy} abonnement(s) : désactivez-le plutôt que le supprimer.");
        }

        $plan->delete();

        return redirect()->route('billing.subscriptions.index', ['tab' => 'plans'])
            ->with('success', 'Plan supprimé.');
    }

    private function validatePlan(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $codeUnique = 'unique:subscription_plans,code' . ($plan ? ",{$plan->id}" : '');

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_-]*$/', $codeUnique],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'sometimes|required|integer|min:0',
            'currency' => 'sometimes|string|size:3',
            'interval' => 'sometimes|required|in:month,year',
            'duration_months' => 'sometimes|required|integer|min:1|max:120',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        return $validated;
    }
}
